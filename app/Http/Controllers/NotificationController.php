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
    if ($notification->user_id !== auth()->id()) {
        abort(403);
    }

    $notification->update(['is_read' => true]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'redirect' => $this->destination($notification),
        ]);
    }

    return redirect()->to($this->destination($notification));
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

    /**
     * ดึงแจ้งเตือนล่าสุดเป็น JSON (สำหรับการอัดเรต polling)
     */
    public function getNotifications(Request $request)
    {
        $user = auth()->user();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->take(200)
            ->get();

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'unreadCount' => $unreadCount,
            'notifications' => $notifications->map(function ($n) {
                $visualType = $n->visualType();
                $visual = match ($visualType) {
                    'success' => [
                        'border' => 'border-l-4 border-l-emerald-500',
                        'surface' => 'bg-gradient-to-r from-emerald-500/10 to-transparent hover:from-emerald-500/15',
                        'title' => 'text-emerald-300',
                        'accent' => 'text-emerald-400',
                        'iconBg' => 'bg-emerald-500/15 ring-emerald-500/25',
                        'iconColor' => 'text-emerald-400',
                        'path' => 'M5 13l4 4L19 7',
                    ],
                    'danger' => [
                        'border' => 'border-l-4 border-l-rose-500',
                        'surface' => 'bg-gradient-to-r from-rose-500/10 to-transparent hover:from-rose-500/15',
                        'title' => 'text-rose-300',
                        'accent' => 'text-rose-400',
                        'iconBg' => 'bg-rose-500/15 ring-rose-500/25',
                        'iconColor' => 'text-rose-400',
                        'path' => 'M6 18L18 6M6 6l12 12',
                    ],
                    'warning' => [
                        'border' => 'border-l-4 border-l-amber-500',
                        'surface' => 'bg-gradient-to-r from-amber-500/10 to-transparent hover:from-amber-500/15',
                        'title' => 'text-amber-300',
                        'accent' => 'text-amber-400',
                        'iconBg' => 'bg-amber-500/15 ring-amber-500/25',
                        'iconColor' => 'text-amber-400',
                        'path' => 'M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
                    ],
                    default => [
                        'border' => 'border-l-4 border-l-sky-500',
                        'surface' => 'bg-gradient-to-r from-sky-500/10 to-transparent hover:from-sky-500/15',
                        'title' => 'text-sky-300',
                        'accent' => 'text-sky-400',
                        'iconBg' => 'bg-sky-500/15 ring-sky-500/25',
                        'iconColor' => 'text-sky-400',
                        'path' => 'M12 8h.01M11 12h1v4h1m8-4a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                };

                $isCourtBookingNotification = str_starts_with($n->title ?? '', 'คำขอจองใหม่')
                    || ($n->title ?? '') === 'มีการจองสนามบาสใหม่';

                $notifTarget = $isCourtBookingNotification
                    ? route('admin.bookings', [
                        'status'   => 'approved',
                        'date'     => now()->format('Y-m-d'),
                        'court_id' => '',
                    ])
                    : route('notifications.open', $n);

                $msgParts = explode('|', $n->message ?? ($n->data['message'] ?? ''));

                return [
                    'id' => $n->id,
                    'title' => $n->title ?? 'การจอง',
                    'message' => trim($msgParts[0] ?? ''),
                    'messagePart2' => isset($msgParts[1]) && trim($msgParts[1]) !== '' ? trim($msgParts[1]) : null,
                    'created_at' => $n->created_at->format('d M Y H:i'),
                    'target' => $notifTarget,
                    'visual' => $visual,
                ];
            })->values(),
        ]);
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

        return match (true) {
    default => match ($notification->title) {
        'มีรีวิวใหม่เข้ามา', 'มีรีวิวใหม่รอตรวจสอบ' => route('admin.edit.text').'#review-moderation',
        'เติมเครดิตสำเร็จ' => route('credits.topup.index'),
        'เครดิตของคุณถูกเติมแล้ว' => route('admin.credits.show', $notification->user_id),
        'เครดิตของคุณถูกปรับยอด' => route('credits.topup.index'),
        'เครดิตของคุณหมดอายุแล้ว' => route('credits.topup.index'),
        'เครดิตของคุณใกล้หมดอายุ' => route('credits.topup.index'),
        'ยืนยันการซื้อแพ็กเกจ' => route('private-training.index'),
        'คำขอเติมเครดิตถูกปฏิเสธ' => route('credits.topup.index'),
        'มีคำขอเติมเครดิตใหม่' => route('admin.credit-topups.index'),
        'การจองถูกปฏิเสธอัตโนมัติ', 'การจองถูกยกเลิกโดยระบบ' => route('admin.bookings'),
        'ผู้ใช้ยกเลิกการจอง' => route('admin.bookings'),
        'คำขอจองเทรนเนอร์ส่วนตัวใหม่' => route('admin.private-training.index', ['status' => 'pending']),
        'การจองได้รับการอนุมัติ', 'การจองถูกปฏิเสธ' => route('history'),
        default => in_array(auth()->user()->role, ['admin', 'superadmin', 'staff'], true)
            ? route('admin.bookings')
            : route('history'),
    },
};
    }
}
