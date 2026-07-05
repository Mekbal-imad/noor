<?php

namespace App\Http\Controllers\API\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function index(Request $request)
    {
        $children = Student::where('parent_id', $request->user()->id)
            ->with(['classes', 'memorizationRecords', 'attendance'])
            ->get();

        return response()->json($children);
    }

    public function show(Request $request, $id)
    {
        $child = Student::where('parent_id', $request->user()->id)
            ->with([
                'classes.teacher',
                'memorizationRecords' => fn($q) => $q->latest()->take(10),
                'attendance'          => fn($q) => $q->latest()->take(30),
                'studyPlans'
            ])
            ->findOrFail($id);

        return response()->json($child);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string',
            'gender'      => 'required|in:male,female',
            'birth_date'  => 'nullable|date',
            'grade_level' => 'required',
        ]);

        $child = Student::create([
            ...$request->all(),
            'parent_id' => $request->user()->id,
            'status'    => 'pending',
        ]);

        return response()->json([
            'message' => 'تم تسجيل الطالب بنجاح، في انتظار موافقة الإدارة',
            'child'   => $child
        ], 201);
    }
}