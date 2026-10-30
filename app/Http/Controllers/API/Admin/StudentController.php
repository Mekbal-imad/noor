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
        $request->validate([
            'name'              => 'required|string',
            'grade_id'          => 'required|exists:grades,id',
            'health_condition'  => 'nullable|string',
            'parent_name'       => 'nullable|string',
            'parent_email'      => 'nullable|email',
            'parent_phone'      => 'nullable|string',
        ]);

        $parentId = null;
        if ($request->filled('parent_email')) {
            $parent = ParentModel::firstOrCreate(
                ['email' => $request->parent_email],
                [
                    'name'     => $request->parent_name ?: 'ولي أمر',
                    'password' => Hash::make('noor1234'),
                    'phone'    => $request->parent_phone,
                ]
            );
            $parentId = $parent->id;
        }

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

        if ($student->parent_id && $student->parent) {
            $parent = $student->parent;
            if ($request->filled('parent_name')) {
                $parent->name = $request->parent_name;
            }
            if ($request->filled('parent_email')) {
                $parent->email = $request->parent_email;
            }
            if ($request->has('parent_phone')) {
                $parent->phone = $request->parent_phone ?: null;
            }
            $parent->save();
        } elseif ($request->filled('parent_email')) {
            $parent = ParentModel::firstOrCreate(
                ['email' => $request->parent_email],
                [
                    'name'     => $request->parent_name ?: 'ولي أمر',
                    'password' => Hash::make('noor1234'),
                    'phone'    => $request->parent_phone,
                ]
            );

            if ($request->filled('parent_name')) {
                $parent->name = $request->parent_name;
            }
            if ($request->has('parent_phone')) {
                $parent->phone = $request->parent_phone ?: null;
            }
            $parent->save();

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

            $parentId = null;
            if (!empty($row['parent_email'])) {
                $parent = ParentModel::firstOrCreate(
                    ['email' => $row['parent_email']],
                    [
                        'name'     => $row['parent_name'] ?: 'ولي أمر',
                        'password' => Hash::make('noor1234'),
                        'phone'    => $row['parent_phone'] ?? null,
                    ]
                );
                $parentId = $parent->id;
            }

            $student = Student::create([
                'parent_id'        => $parentId,
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
}