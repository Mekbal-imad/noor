<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Controllers\Controller;
use App\Models\MemorizationRecord;
use Illuminate\Http\Request;

class MemorizationController extends Controller
{
    public function index(Request $request)
    {
        $records = MemorizationRecord::with('student')
            ->where('teacher_id', $request->user()->id)
            ->whereDate('date', $request->date ?? today())
            ->get();

        return response()->json($records);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'type'       => 'required|in:memorization,revision,confirmation',
            'from_surah' => 'required|string',
            'from_ayah'  => 'required|integer',
            'to_surah'   => 'required|string',
            'to_ayah'    => 'required|integer',
            'grade'      => 'nullable|numeric|min:0|max:20',
            'notes'      => 'nullable|string',
            'date'       => 'required|date',
        ]);

        $record = MemorizationRecord::create([
            ...$request->all(),
            'teacher_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'تم تسجيل الحفظ بنجاح',
            'record'  => $record
        ], 201);
    }

    public function studentHistory($studentId)
    {
        $records = MemorizationRecord::where('student_id', $studentId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($records);
    }

    public function update(Request $request, $id) {}
    public function destroy($id)
    {
        MemorizationRecord::findOrFail($id)->delete();
        return response()->json(['message' => 'تم الحذف بنجاح']);
    }
    public function show($id) {}
    public function create() {}
    public function edit($id) {}
}