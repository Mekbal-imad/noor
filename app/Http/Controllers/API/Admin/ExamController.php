<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::with('class')->get();
        return response()->json($exams);
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id'    => 'required|exists:classes,id',
            'title'       => 'required|string',
            'description' => 'nullable|string',
            'exam_date'   => 'required|date',
        ]);

        $exam = Exam::create($request->all());

        return response()->json([
            'message' => 'تم إنشاء الاختبار بنجاح',
            'exam'    => $exam
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Exam::with('class')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $exam->update($request->all());
        return response()->json(['message' => 'تم تحديث الاختبار بنجاح', 'exam' => $exam]);
    }

    public function destroy($id)
    {
        Exam::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف الاختبار بنجاح']);
    }
}