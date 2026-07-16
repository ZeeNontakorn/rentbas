<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'court_status',
        'closed_from',
        'closed_until',
        'slot_interval_minutes',
        'min_booking_minutes',
    ];

    protected function casts(): array
    {
        return [
            'closed_from' => 'datetime',
            'closed_until' => 'datetime',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourtSection::class);
    }

    /**
     * ทุก section ของสนาม (รวมที่ปิดใช้งานอยู่) เรียง เต็มสนาม > ครึ่ง A > ครึ่ง B > อื่นๆ
     * ใช้ในหน้าแอดมินสำหรับจัดการ section (ต่างจาก activeSections() ที่ใช้ฝั่งจอง)
     */
    public function allSectionsOrdered()
    {
        return $this->sections()
            ->get()
            ->sortBy(fn ($s) => match ($s->code) {
                'full' => 0,
                'a' => 1,
                'b' => 2,
                default => 3,
            })
            ->values();
    }

    /**
     * Section ที่เปิดใช้งานอยู่ เรียงให้ "เต็มสนาม" ขึ้นก่อนเสมอ แล้วตามด้วยครึ่งสนามตามชื่อ
     * ใช้แสดงเป็นคอลัมน์/เลนในหน้าเลือกเวลา (grid และ calendar view)
     */
    public function activeSections()
    {
        return $this->sections()
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($s) => $s->code === 'full' ? 0 : 1)
            ->values();
    }

    /**
     * Section "เต็มสนาม" ของ court นี้ — ใช้เป็นค่า default เวลา booking ไม่ได้ระบุ
     * section มา (เช่น สนามที่ยังไม่ถูกแบ่งครึ่ง หรือ flow เก่าที่ยังไม่ส่ง section_id)
     */
    public function defaultSection(): ?CourtSection
    {
        return $this->sections()->where('code', 'full')->first();
    }

    /**
     * Return true if this court (or a specific section of it) is unavailable
     * for the given [from, to] window. A court/section is unavailable if the
     * court status is closed, the window overlaps closed_from..closed_until,
     * or there's a matching court_closures row.
     *
     * เมื่อระบุ $section มาด้วย จะเช็คทั้ง closure ที่ปิดทั้งสนาม (court_section_id = null)
     * และ closure ที่ปิดเฉพาะ section นั้น
     */
    public function isClosedAt(CarbonInterface $from, CarbonInterface $to, ?CourtSection $section = null): bool
    {
        if ($this->court_status === 'closed') {
            return true;
        }

        if ($this->closed_from && $this->closed_until) {
            if ($from->lt($this->closed_until) && $to->gt($this->closed_from)) {
                return true;
            }
        }

        // Check court_closures
        $dateStr = $from->toDateString();
        $startTime = $from->toTimeString();
        $endTime = $to->toTimeString();

        $query = CourtClosure::where('court_id', $this->id)
            ->whereDate('date', $dateStr)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($section) {
            $query->where(function ($q) use ($section) {
                $q->whereNull('court_section_id')
                    ->orWhere('court_section_id', $section->id);
            });
        } else {
            $query->whereNull('court_section_id');
        }

        return $query->exists();
    }
    public function getClosureType(string $from, string $to, string $date): ?string
    {

        if ($this->court_status === 'closed') {
            return 'closed';
        }


        // Check court_closures
        $dateStr = $date;
        $startTime = $from;
        $endTime = $to;

        $closure = CourtClosure::where('court_id', $this->id)
            ->whereDate('date', $dateStr)
            ->where('start_time', '<=', $endTime)
            ->where('end_time', '>=', $startTime)
            ->first();


        return $closure?->type;
    }
}
