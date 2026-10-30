<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\ParentModel;
use App\Models\Grade;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['parent', 'grade'])->get();
        return response()->json($students);
    }

    public function pending()
    {
        $students = Student::with(['parent', 'grade'])
            ->where('status', 'pending')
            ->get();
        return response()->json($students);
    }

    public function approve($id)
    {
        $student = Student::findOrFail($id);
        $student->update(['status' => 'approved']);
        return response()->json(['message' => 'تم قبول الطالب بنجاح']);
    }

    public function reject($id)
    {
        $student = Student::findOrFail($id);
        $student->update(['status' => 'rejected']);
        return response()->json(['message' => 'تم رفض الطالب']);
    }

    public function show($id)
    {
        $student = Student::with(['parent', 'grade', 'classes', 'memorizationRecords'])
            ->findOrFail($id);
        return response()->json($student);
    }

    public function destroy($id)
    {
        Student::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف الطالب بنجاح']);
    }

    // Manual add by admin
    public function store(Request $request)
    {
        $this->normalizeParentFields($request);

        $request->validate([
            'name'              => 'required|string',
            'grade_id'          => 'required|exists:grades,id',
            'health_condition'  => 'nullable|string',
            'parent_name'       => 'nullable|string',
            'parent_email'      => 'nullable|email|unique:parents,email',
            'parent_phone'      => 'nullable|string',
        ]);

        $parent = $this->syncParent($request);
        $parentId = $parent?->id;

        $student = Student::create([
            'parent_id'        => $parentId,
            'grade_id'         => $request->grade_id,
            'name'             => $request->name,
            'gender'           => 'male',
            'health_condition' => $request->health_condition,
            'status'           => 'approved',
        ]);

        // Auto-enroll in all classes of the grade
        $classIds = ClassRoom::where('grade_id', $request->grade_id)->pluck('id');
        $student->classes()->syncWithoutDetaching($classIds);

        return response()->json([
            'message' => 'تم إضافة الطالب بنجاح',
            'student' => $student->load('parent', 'grade')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $student = Student::with('parent')->findOrFail($id);
        $this->normalizeParentFields($request);

        $request->validate([
            'name'              => 'required|string',
            'grade_id'          => 'required|exists:grades,id',
            'health_condition'  => 'nullable|string',
            'parent_name'       => 'nullable|string',
            'parent_email'      => [
                'nullable',
                'email',
                Rule::unique('parents', 'email')->ignore($student->parent_id),
            ],
            'parent_phone'      => 'nullable|string',
        ]);

        $oldGradeId = $student->grade_id;

        $parent = $this->syncParent($request, $student->parent);
        if ($parent && !$student->parent_id) {
            $student->parent_id = $parent->id;
        }

        $student->name = $request->name;
        $student->grade_id = $request->grade_id;
        $student->health_condition = $request->health_condition;
        $student->save();

        if ((int) $oldGradeId !== (int) $request->grade_id) {
            $classIds = ClassRoom::where('grade_id', $request->grade_id)->pluck('id');
            $student->classes()->syncWithoutDetaching($classIds);
        }

        return response()->json([
            'message' => 'تم تحديث بيانات الطالب بنجاح',
            'student' => $student->fresh()->load('parent', 'grade'),
        ]);
    }

    // Bulk import from spreadsheet
    public function import(Request $request)
    {
        $request->validate([
            'students'                       => 'required|array|min:1',
            'students.*.name'                => 'required|string',
            'students.*.grade_name'          => 'required|string',
            'students.*.parent_name'         => 'nullable|string',
            'students.*.parent_email'        => 'nullable|email',
            'students.*.parent_phone'        => 'nullable|string',
            'students.*.health_condition'    => 'nullable|string',
        ]);

        $results = ['success' => 0, 'failed' => []];

        foreach ($request->students as $row) {
            $grade = Grade::where('name', trim($row['grade_name']))->first();

            if (!$grade) {
                $results['failed'][] = [
                    'name'   => $row['name'],
                    'reason' => "المرحلة '{$row['grade_name']}' غير موجودة"
                ];
                continue;
            }

            $parent = $this->resolveParentFromValues(
                $row['parent_name'] ?? null,
                $row['parent_email'] ?? null,
                $row['parent_phone'] ?? null,
            );

            $student = Student::create([
                'parent_id'        => $parent?->id,
                'grade_id'         => $grade->id,
                'name'             => $row['name'],
                'gender'           => 'male',
                'health_condition' => $row['health_condition'] ?? null,
                'status'           => 'approved',
            ]);

            $classIds = ClassRoom::where('grade_id', $grade->id)->pluck('id');
            $student->classes()->syncWithoutDetaching($classIds);

            $results['success']++;
        }

        return response()->json([
            'message' => "تم استيراد {$results['success']} طالب بنجاح",
            'results' => $results
        ]);
    }

    private function normalizeParentFields(Request $request): void
    {
        $merge = [];
        foreach (['parent_name', 'parent_email', 'parent_phone'] as $field) {
            if ($request->has($field) && is_string($request->input($field)) && trim($request->input($field)) === '') {
                $merge[$field] = null;
            }
        }
        if ($merge) {
            $request->merge($merge);
        }
    }

    private function syncParent(Request $request, ?ParentModel $existing = null): ?ParentModel
    {
        return $this->resolveParentFromValues(
            $request->input('parent_name'),
            $request->input('parent_email'),
            $request->input('parent_phone'),
            $existing,
            $request->has('parent_name'),
            $request->has('parent_email'),
            $request->has('parent_phone'),
        );
    }

    private function resolveParentFromValues(
        ?string $name,
        ?string $email,
        ?string $phone,
        ?ParentModel $existing = null,
        bool $nameProvided = true,
        bool $emailProvided = true,
        bool $phoneProvided = true,
    ): ?ParentModel {
        $name = is_string($name) ? trim($name) : null;
        $email = is_string($email) ? trim($email) : null;
        $phone = is_string($phone) ? trim($phone) : null;
        if ($name === '') $name = null;
        if ($email === '') $email = null;
        if ($phone === '') $phone = null;

        if ($existing) {
            if ($nameProvided && $name !== null) {
                $existing->name = $name;
            }
            if ($emailProvided) {
                $existing->email = $email;
            }
            if ($phoneProvided) {
                $existing->phone = $phone;
            }
            $existing->save();
            return $existing;
        }

        if ($name === null && $email === null && $phone === null) {
            return null;
        }

        if ($email !== null) {
            $parent = ParentModel::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $name ?: 'ولي أمر',
                    'password' => Hash::make('noor1234'),
                    'phone'    => $phone,
                ]
            );

            if ($name !== null) {
                $parent->name = $name;
            }
            if ($phoneProvided) {
                $parent->phone = $phone;
            }
            $parent->save();

            return $parent;
        }

        // Name and/or phone only — create a dedicated parent (not shared)
        return ParentModel::create([
            'name'     => $name ?: 'ولي أمر',
            'email'    => null,
            'password' => Hash::make('noor1234'),
            'phone'    => $phone,
        ]);
    }
}