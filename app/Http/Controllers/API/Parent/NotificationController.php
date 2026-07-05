<?php

namespace App\Http\Controllers\API\Parent;

use App\Http\Controllers\Controller;
use App\Models\NoorNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = NoorNotification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', 'App\Models\ParentModel')
            ->latest()
            ->get();

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = NoorNotification::findOrFail($id);
        $notification->update(['read_at' => now()]);
        return response()->json(['message' => 'تم تحديث الإشعار']);
    }

    public function markAllAsRead(Request $request)
    {
        NoorNotification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', 'App\Models\ParentModel')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'تم تحديث جميع الإشعارات']);
    }
}