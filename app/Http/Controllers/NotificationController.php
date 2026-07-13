<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        // ตรวจสอบสิทธิ์ผู้ใช้
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->noContent(); // 204 ไม่ redirect
        }

        return back();
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้วทั้งหมดในครั้งเดียว
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->noContent(); // 204 ไม่ redirect
        }

        return back();
    }
}