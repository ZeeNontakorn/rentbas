<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingCancelledByAdminMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\CourtSection;
use App\Models\Setting;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
            'min_booking_minutes' => 30, // default
            'slot_interval_minutes' => 30, // default
        ]);

        // สร้าง section "เต็มสนาม" (code = full) ให้อัตโนมัติเสมอทุกครั้งที่เพิ่มสนามใหม่
        // ถ้าไม่มี section นี้ หน้าเลือกเวลาจะหา section ไม่เจอเลย แล้วโชว์
        // "สนามนี้ยังไม่เปิดให้จอง" ทั้งที่ยังไม่ได้ปิดอะไรจริง (ดู buildAvailabilityMatrix())
        $fullSection = CourtSection::create([
            'court_id' => $court->id,
            'code' => 'full',
            'name' => 'เต็มสนาม',
            'is_active' => true,
        ]);

        // เต็มสนามต้องบล็อกตัวเอง (กันจองซ้อนช่วงเวลาเดียวกัน) — จะเพิ่มการบล็อกกับ
        // ครึ่ง a/b ทีหลังตอนแอดมินไปสร้าง section เหล่านั้นเพิ่มเอง
        DB::table('court_section_blocks')->insert([
            'court_section_id' => $fullSection->id,
            'blocks_section_id' => $fullSection->id,
            'created_at' => now(),
            'updated_at' => now(),
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
        $sections = $selectedCourt ? $selectedCourt->allSectionsOrdered() : collect();

        $slots = [];
        if ($selectedCourt) {
            $now = now();

            // Check if court is globally closed
            $isGloballyClosed = ($selectedCourt->court_status === 'closed');

            $fullSection = $selectedCourt->defaultSection();
            $conflictIds = $fullSection ? $fullSection->conflictingSectionIds() : [];

            // ดึงการจองของวันนี้ทั้งหมดมาครั้งเดียว แล้วเช็ค overlap เอง (แทนการ query แบบ exact-match ต่อชั่วโมง
            // ซึ่งพลาดเคสจองครึ่งสนาม/เวลาไม่เต็มชั่วโมง และไม่รู้จัก court_section conflict)
            $dayBookings = Booking::where('court_id', $selectedCourt->id)
                ->whereDate('booking_date', $date)
                ->where(function ($query) {
                    $query->whereIn('status', ['pending', 'approved'])
                        ->orWhere(function ($lockQuery) {
                            $lockQuery->where('status', 'pending_payment')
                                ->where('locked_until', '>', now());
                        });
                })
                ->with('user:id,name')
                ->get(['id', 'court_section_id', 'start_time', 'end_time', 'status', 'user_id']);

            for ($h = 6; $h < 22; $h++) {
                $start = sprintf('%02d:00:00', $h);
                $end = sprintf('%02d:00:00', $h + 1);

                $booking = $dayBookings->first(function ($b) use ($conflictIds, $start, $end) {
                    return in_array($b->court_section_id, $conflictIds, true)
                        && $b->start_time < $end && $b->end_time > $start;
                });

                $closureType = $selectedCourt->getClosureType($start, $end, $date, $fullSection);

                $status = 'available';

                if ($isGloballyClosed) {
                    $status = 'unavailable'; // Or a specific 'closed' status
                } elseif ($closureType) {
                    $status = $closureType; // 'maintenance' or 'unavailable'
                } elseif ($booking) {
                    $status = 'booking_' . $booking->status; // 'booking_pending' or 'booking_approved'
                }

                $slots[] = [
                    'label' => sprintf('%02d:00 - %02d:00', $h, $h + 1),
                    'start' => $start,
                    'end'   => $end,
                    'status' => $status,
                    'customer_name' => $booking?->user?->name,
                ];
            }
        }

        return view('admin.courts', compact('courts', 'date', 'selectedCourt', 'slots', 'sections'));
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

    /**
     * แบ่งสนามออกเป็นครึ่ง A/B (สร้าง section ใหม่ถ้ายังไม่มี หรือเปิดใช้งานใหม่ถ้าเคยปิดไว้)
     * แล้ว sync blocking matrix ให้ถูกต้องเสมอ: full บล็อก full+a+b, a บล็อก a+full, b บล็อก b+full
     */
    public function splitSection(Request $request, Court $court)
    {
        $data = $request->validate([
            'name_a' => ['nullable', 'string', 'max:100'],
            'name_b' => ['nullable', 'string', 'max:100'],
            'name_full' => ['nullable', 'string', 'max:100'],
            'return_date' => ['nullable', 'date'],
        ]);

        $full = $court->sections()->where('code', 'full')->first();
        if (!$full) {
            $full = CourtSection::create([
                'court_id' => $court->id,
                'code' => 'full',
                'name' => 'เต็มสนาม',
                'is_active' => true,
            ]);
        } elseif (!empty($data['name_full'])) {
            $full->update(['name' => $data['name_full']]);
        }

        // อัปเดต/สร้าง ครึ่ง A (เช็คว่ามีการส่งค่าเปิดใช้งานมาหรือไม่)
        $a = CourtSection::updateOrCreate(
            ['court_id' => $court->id, 'code' => 'a'],
            [
                'name' => $data['name_a'] ?: 'ครึ่ง A',
                'is_active' => $request->has('is_active_a'),
            ]
        );

        // อัปเดต/สร้าง ครึ่ง B (เช็คว่ามีการส่งค่าเปิดใช้งานมาหรือไม่)
        $b = CourtSection::updateOrCreate(
            ['court_id' => $court->id, 'code' => 'b'],
            [
                'name' => $data['name_b'] ?: 'ครึ่ง B',
                'is_active' => $request->has('is_active_b'),
            ]
        );

        $this->syncHalfCourtBlockingMatrix($full, $a, $b);

        return redirect()->route('admin.courts', [
            'court_id' => $court->id,
            'date' => $data['return_date'] ?? now()->toDateString(),
        ])->with('success', 'บันทึกข้อมูลส่วนของสนามเรียบร้อยแล้ว');
    }

    /**
     * ยกเลิกการแบ่งครึ่งสนาม — ปิดใช้งาน section a/b (ไม่ลบ เพื่อรักษาประวัติการจองเดิมที่อ้างอิงอยู่)
     * เต็มสนามจะถูกเปิดใช้งานกลับเสมอ เพื่อให้ยังจองได้ต่อ
     */
    public function mergeSections(Request $request, Court $court)
    {
        $data = $request->validate([
            'return_date' => ['nullable', 'date'],
        ]);

        $court->sections()->whereIn('code', ['a', 'b'])->update(['is_active' => false]);
        $court->sections()->where('code', 'full')->update(['is_active' => true]);

        return redirect()->route('admin.courts', [
            'court_id' => $court->id,
            'date' => $data['return_date'] ?? now()->toDateString(),
        ])->with('success', 'ยกเลิกการแบ่งครึ่งสนามเรียบร้อยแล้ว (กลับเป็นจองเต็มสนามเท่านั้น)');
    }

    /**
     * แก้ชื่อ/สถานะเปิด-ปิดของ section หนึ่งรายการ (เต็มสนาม/ครึ่ง A/ครึ่ง B)
     */
    public function updateSection(Request $request, CourtSection $courtSection)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'return_date' => ['nullable', 'date'],
        ]);

        // เต็มสนามต้องเปิดใช้งานเสมอ เพราะเป็น section หลักที่ระบบ fallback ไปใช้เวลาไม่ได้ระบุ section
        $isActive = $courtSection->code === 'full' ? true : $request->boolean('is_active');

        $courtSection->update([
            'name' => $data['name'],
            'is_active' => $isActive,
        ]);

        return redirect()->route('admin.courts', [
            'court_id' => $courtSection->court_id,
            'date' => $data['return_date'] ?? now()->toDateString(),
        ])->with('success', 'อัปเดตส่วนของสนามเรียบร้อยแล้ว');
    }

    /**
     * ตั้งค่าความละเอียดของเวลา (ทุกกี่นาที) และระยะเวลาจองขั้นต่ำต่อสนาม
     */
    // public function updateSlotSettings(Request $request, Court $court)
    // {
    //     $data = $request->validate([
    //         'slot_interval_minutes' => ['required', 'integer', 'min:5', 'max:120'],
    //         'min_booking_minutes' => ['required', 'integer', 'min:5', 'max:480'],
    //         'return_date' => ['nullable', 'date'],
    //     ]);

    //     if ($data['min_booking_minutes'] < $data['slot_interval_minutes']) {
    //         return back()->withErrors(['min_booking_minutes' => 'ระยะเวลาจองขั้นต่ำต้องไม่น้อยกว่าความละเอียดของเวลา']);
    //     }

    //     $court->update([
    //         'slot_interval_minutes' => $data['slot_interval_minutes'],
    //         'min_booking_minutes' => $data['min_booking_minutes'],
    //     ]);

    //     return redirect()->route('admin.courts', [
    //         'court_id' => $court->id,
    //         'date' => $data['return_date'] ?? now()->toDateString(),
    //     ])->with('success', 'อัปเดตการตั้งค่าเวลาเรียบร้อยแล้ว');
    // }

    /**
     * เติมแถว court_section_blocks ให้ครบตาม "blocking matrix" มาตรฐานของการแบ่งครึ่งสนาม:
     *   full บล็อก full, a, b   |   a บล็อก a, full   |   b บล็อก b, full
     * ใช้ exists-check ก่อน insert ทีละคู่ (กัน unique constraint ชนตอนเรียกซ้ำ/reactivate)
     */
    protected function syncHalfCourtBlockingMatrix(CourtSection $full, CourtSection $a, CourtSection $b): void
    {
        $pairs = [
            [$full->id, $full->id],
            [$full->id, $a->id],
            [$full->id, $b->id],
            [$a->id, $a->id],
            [$a->id, $full->id],
            [$b->id, $b->id],
            [$b->id, $full->id],
        ];

        foreach ($pairs as [$sectionId, $blocksId]) {
            $exists = \Illuminate\Support\Facades\DB::table('court_section_blocks')
                ->where('court_section_id', $sectionId)
                ->where('blocks_section_id', $blocksId)
                ->exists();

            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('court_section_blocks')->insert([
                    'court_section_id' => $sectionId,
                    'blocks_section_id' => $blocksId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
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

            $settingKey = 'court_img_' . $court->id;
            $old = Setting::where('key', $settingKey)->value('value');

            if ($old && !preg_match('#^https?://#i', $old)) {
                $relativePath = Setting::normalizeStoragePath($old);

                if ($relativePath && Storage::disk('public')->exists($relativePath)) {
                    Storage::disk('public')->delete($relativePath);
                }
            }

            $path = $file->store('site', 'public');

            Setting::updateOrCreate(
                ['key' => $settingKey],
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

    public function destroy(Court $court)
    {
        // ตรวจสอบว่าสนามนี้มีการจองในระบบหรือไม่
        $hasBookings = Booking::where('court_id', $court->id)->exists();

        if ($hasBookings) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถลบได้ เนื่องจากสนามนี้มีประวัติการจองในระบบแล้ว'
            ], 403);
        }

        // ลบข้อมูลที่เกี่ยวข้อง (เช่น ข้อมูลการปิดปรับปรุงของสนามนี้)
        CourtClosure::where('court_id', $court->id)->delete();

        // ลบรูปภาพที่ตั้งค่าไว้ (ถ้ามี)
        Setting::where('key', 'court_img_' . $court->id)->delete();

        // ลบข้อมูลสนาม
        $court->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'ลบสนามเรียบร้อยแล้ว'
        ], 200);
    }
}
