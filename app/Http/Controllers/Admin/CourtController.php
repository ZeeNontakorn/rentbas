<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingCancelledByAdminMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\Setting;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CourtController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('courts', 'name')],
            'court_status' => ['required', 'in:open,closed'],
            'return_date' => ['nullable', 'date'],
            'return_court_id' => ['nullable', 'integer', 'exists:courts,id'],
        ], [
            'name.required' => 'กรอกชื่อสนาม',
            'name.unique' => 'ชื่อสนามถูกใช้แล้ว',
            'court_status.required' => 'เลือกสถานะ',
        ]);

        $court = Court::create([
            'name' => $data['name'],
            'court_status' => $data['court_status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'เพิ่มสนามเรียบร้อยแล้ว',
                'court' => $court,
                'redirect' => route('admin.courts', [
                    'court_id' => $court->id,
                    'date' => $data['return_date'] ?? now()->toDateString(),
                ]),
            ]);
        }

        return redirect()->route('admin.courts', [
            'court_id' => $court->id,
            'date' => $data['return_date'] ?? now()->toDateString(),
        ])->with('success', 'เพิ่มสนามเรียบร้อยแล้ว');
    }

    public function update(Request $request, Court $court)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('courts', 'name')->ignore($court->id)],
            'court_status' => ['required', 'in:open,closed'],
            'return_date' => ['nullable', 'date'],
            'return_court_id' => ['nullable', 'integer', 'exists:courts,id'],
        ], [
            'name.required' => 'กรอกชื่อสนาม',
            'name.unique' => 'ชื่อสนามซ้ำ',
            'court_status.required' => 'เลือกสถานะ',
        ]);

        $court->update([
            'name' => $data['name'],
            'court_status' => $data['court_status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'อัปเดตข้อมูลสนามเรียบร้อยแล้ว',
                'court' => $court,
                'redirect' => route('admin.courts', [
                    'court_id' => $court->id,
                    'date' => $data['return_date'] ?? now()->toDateString(),
                ]),
            ]);
        }

        return redirect()->route('admin.courts', [
            'court_id' => $court->id,
            'date' => $data['return_date'] ?? now()->toDateString(),
        ])->with('success', 'อัปเดตข้อมูลสนามเรียบร้อยแล้ว');
    }

    public function index(Request $request)
    {
        $courts = Court::all()->sortBy(function ($court) {
            return $court->name;
        }, SORT_NATURAL | SORT_FLAG_CASE)->values();
        $dateParam = $request->query('date', now()->toDateString());
        try {
            $date = Carbon::parse($dateParam)->toDateString();
        } catch (\Exception $e) {
            $date = now()->toDateString();
        }
        $courtId = $request->query('court_id', $courts->first()?->id);
        $selectedCourt = $courts->firstWhere('id', $courtId);

        $slots = [];
        if ($selectedCourt) {
            $dateCarbon = Carbon::parse($date);

            // Check if court is globally closed
            $isGloballyClosed = ($selectedCourt->court_status === 'closed');

            for ($h = 6; $h < 22; $h++) {
                $start = sprintf('%02d:00:00', $h);
                $end = sprintf('%02d:00:00', $h + 1);

                $booking = Booking::where('court_id', $selectedCourt->id)
                    ->whereDate('booking_date', $date)
                    ->whereIn('status', ['pending', 'approved'])
                    ->where('start_time', $start)
                    ->where('end_time', $end)
                    ->first();

                $closure = CourtClosure::where('court_id', $selectedCourt->id)
                    ->whereDate('date', $date)
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->first();

                $status = 'available';

                if ($isGloballyClosed) {
                    $status = 'unavailable'; // Or a specific 'closed' status
                } elseif ($closure) {
                    $status = $closure->type; // 'maintenance' or 'unavailable'
                } elseif ($booking) {
                    $status = 'booking_' . $booking->status; // 'booking_pending' or 'booking_approved'
                }

                $slots[] = [
                    'label' => sprintf('%02d:00 - %02d:00', $h, $h + 1),
                    'start' => $start,
                    'end'   => $end,
                    'status' => $status,
                ];
            }
        }

        return view('admin.courts', compact('courts', 'date', 'selectedCourt', 'slots'));
    }

    public function updateStatus(Request $request, Court $court)
    {
        $data = $request->validate([
            'court_status' => 'required|in:open,closed',
        ]);

        $court->update([
            'court_status' => $data['court_status']
        ]);

        return back()->with('success', 'อัปเดตสถานะสนามเรียบร้อยแล้ว');
    }

    public function updateSlot(Request $request)
    {
        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s'],
            'status' => ['required', 'in:available,unavailable,maintenance'],
        ]);

        $statusLabel = match ($data['status']) {
            'unavailable' => 'ไม่ว่าง',
            'maintenance' => 'ปิดปรับปรุง',
            default => 'ว่าง',
        };

        // หา booking ที่ทับซ้อนช่วงเวลานี้ (pending/approved)
        $overlappingBookings = Booking::where('court_id', $data['court_id'])
            ->whereDate('booking_date', $data['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->get();

        // Delete existing closure if any
        CourtClosure::where('court_id', $data['court_id'])
            ->where('date', $data['date'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->delete();

        if ($data['status'] !== 'available') {
            CourtClosure::create([
                'court_id' => $data['court_id'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'type' => $data['status'],
            ]);
        }

        foreach ($overlappingBookings as $booking) {
            $reason = "ช่วงเวลานี้ถูกตั้งเป็น '{$statusLabel}' โดยผู้ดูแลระบบ";
                $booking->update([
                    'status' => 'rejected',
                    'rejection_reason' => $reason,
                ]);


            Notification::create([
                'user_id' => $booking->user_id,
                'title' => 'การจองถูกยกเลิกโดยระบบ',
                'message' => "การจอง {$booking->court->name} วันที่ {$data['date']} เวลา "
                    . substr($booking->start_time, 0, 5) . '-' . substr($booking->end_time, 0, 5)
                    . " ถูกยกเลิก เนื่องจาก{$reason}",
            ]);

            if ($booking->user?->email) {
                Mail::to($booking->user->email)
                    ->send(new BookingCancelledByAdminMail($booking, $reason));
            }
        }

        $message = 'อัปเดตสถานะช่วงเวลาเรียบร้อยแล้ว';
        if ($overlappingBookings->isNotEmpty()) {
            $message .= " (ยกเลิกการจองลูกค้า {$overlappingBookings->count()} รายการ และแจ้งเตือนแล้ว)";
        }

        return back()->with('success', $message);
    }

    public function updateImages(Request $request)
    {
        $request->validate([
            'court_images' => ['nullable', 'array'],
            'court_images.*' => ['nullable', 'image', 'max:5120'],
        ]);

        foreach ($request->file('court_images', []) as $courtId => $file) {
            if (!$file) {
                continue;
            }

            $court = Court::find($courtId);
            if (!$court) {
                continue;
            }

            $path = $file->store('site', 'public');

            Setting::updateOrCreate(
                ['key' => 'court_img_' . $court->id],
                ['value' => 'media/' . $path]
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'บันทึกรูปสนามเรียบร้อยแล้ว',
            ]);
        }

        return back();
    }
}
