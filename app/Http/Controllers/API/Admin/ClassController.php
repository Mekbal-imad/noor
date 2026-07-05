<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index()
    {
        $classes = ClassRoom::with('teacher')->get();
        return response()->json($classes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'teacher_id'  => 'required|exists:teachers,id',
            'name'        => 'required|string',
            'type'        => 'required|in:quran,sira,adab',
            'grade_level' => 'required',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i',
            'days'        => 'nullable|string',
        ]);

        $class = ClassRoom::create($request->all());

        return response()->json([
            'message' => 'تم إنشاء الفصل بنجاح',
            'class'   => $class
        ], 201);
    }

    public function show($id)
    {
        $class = ClassRoom::with(['teacher', 'students'])->findOrFail($id);
        return response()->json($class);
    }

    public function assignStudent(Request $request, $id)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id'
        ]);

        $class = ClassRoom::findOrFail($id);
        $class->students()->syncWithoutDetaching([$request->student_id]);

        return response()->json(['message' => 'تم إضافة الطالب للفصل بنجاح']);
    }

    public function update(Request $request, $id)
    {
        $class = ClassRoom::findOrFail($id);
        $class->update($request->all());
        return response()->json(['message' => 'تم تحديث الفصل بنجاح', 'class' => $class]);
    }

    public function destroy($id)
    {
        ClassRoom::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف الفصل بنجاح']);
    }
}