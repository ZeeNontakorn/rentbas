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

    public function open(Notification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->update(['is_read' => true]);

        return redirect()->to($this->destination($notification));
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

    private function destination(Notification $notification): string
    {
        if ($notification->action_url) {
            $parts = parse_url($notification->action_url);
            $appParts = parse_url(config('app.url'));
            if (! isset($parts['host']) || ($parts['host'] ?? null) === ($appParts['host'] ?? null)) {
                return $notification->action_url;
            }
        }

        return match ($notification->title) {
            'มีรีวิวใหม่เข้ามา', 'มีรีวิวใหม่รอตรวจสอบ' => route('admin.edit.text').'#review-moderation',
            'เติมเครดิตสำเร็จ' => route('credits.topup.index'),
            'เครดิตของคุณถูกเติมแล้ว' => route('admin.credits.show', $notification->user_id),
            'ยืนยันการซื้อแพ็กเกจ' => route('admin.private-training.index'),
            'มีคำขอเติมเครดิตใหม่' => route('admin.credit-topups.index'),
            'มีการจองสนามบาสใหม่' => route('admin.bookings'),
            'คำขอจองเทรนเนอร์ส่วนตัวใหม่' => route('admin.private-training.index', ['status' => 'pending']),
            'การจองได้รับการอนุมัติ', 'การจองถูกปฏิเสธ' => route('history'),
            default => in_array(auth()->user()->role, ['admin', 'superadmin', 'staff'], true)
                ? route('admin.bookings')
                : route('history'),
        };
    }
}
