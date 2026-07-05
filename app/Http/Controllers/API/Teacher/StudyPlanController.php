<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Controllers\Controller;
use App\Models\StudyPlan;
use Illuminate\Http\Request;

class StudyPlanController extends Controller
{
    public function index(Request $request)
    {
        $plans = StudyPlan::with('student')
            ->where('teacher_id', $request->user()->id)
            ->get();

        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,id',
            'title'       => 'required|string',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
        ]);

        $plan = StudyPlan::create([
            ...$request->all(),
            'teacher_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'تم إنشاء خطة الدراسة بنجاح',
            'plan'    => $plan
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $plan = StudyPlan::findOrFail($id);
        $plan->update($request->all());
        return response()->json(['message' => 'تم تحديث الخطة بنجاح', 'plan' => $plan]);
    }

    public function destroy($id)
    {
        StudyPlan::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف الخطة بنجاح']);
    }

    public function show($id) {}
    public function create() {}
    public function edit($id) {}
}