<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = Message::where(function ($q) use ($request) {
            $q->where('sender_id', $request->user()->id)
              ->where('sender_type', get_class($request->user()));
        })->orWhere(function ($q) use ($request) {
            $q->where('receiver_id', $request->user()->id)
              ->where('receiver_type', get_class($request->user()));
        })->latest()->get();

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id'   => 'required|integer',
            'receiver_type' => 'required|in:teacher,parent',
            'body'          => 'required|string',
        ]);

        $receiverClass = $request->receiver_type === 'teacher'
            ? 'App\Models\Teacher'
            : 'App\Models\ParentModel';

        $message = Message::create([
            'sender_id'     => $request->user()->id,
            'sender_type'   => get_class($request->user()),
            'receiver_id'   => $request->receiver_id,
            'receiver_type' => $receiverClass,
            'body'          => $request->body,
        ]);

        return response()->json([
            'message' => 'تم إرسال الرسالة بنجاح',
            'data'    => $message
        ], 201);
    }
}