<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
   public function index()
{
    $teachers = Teacher::with('grades')->get();
    return response()->json($teachers);
}

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string',
            'email'          => 'required|email|unique:teachers',
            'password'       => 'required|min:6',
            'phone'          => 'nullable|string',
            'gender'         => 'required|in:male,female',
            'specialization' => 'nullable|string',
        ]);

        $teacher = Teacher::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'phone'          => $request->phone,
            'gender'         => $request->gender,
            'specialization' => $request->specialization,
        ]);

        return response()->json([
            'message' => 'تم إضافة المعلم بنجاح',
            'teacher' => $teacher
        ], 201);
    }

    public function show($id)
    {
        $teacher = Teacher::with('classes')->findOrFail($id);
        return response()->json($teacher);
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $request->validate([
            'name'           => 'sometimes|string',
            'email'          => 'sometimes|email|unique:teachers,email,' . $id,
            'phone'          => 'nullable|string',
            'gender'         => 'sometimes|in:male,female',
            'specialization' => 'nullable|string',
            'is_active'      => 'sometimes|boolean',
        ]);

        $teacher->update($request->except('password'));

        if ($request->filled('password')) {
            $teacher->update(['password' => Hash::make($request->password)]);
        }

        return response()->json([
            'message' => 'تم تحديث المعلم بنجاح',
            'teacher' => $teacher
        ]);
    }

    public function destroy($id)
    {
        Teacher::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف المعلم بنجاح']);
    }
}