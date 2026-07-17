<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\PricingRule;
use App\Models\PromotionPackage;
use Carbon\Carbon;
use InvalidArgumentException;

class PricingService
{
    /**
     * คำนวณราคาจอง คืนค่าเป็น array:
     * [
     *   'total' => int (สตางค์),
     *   'total_baht' => float,
     *   'breakdown' => [ ['label' => ..., 'minutes' => ..., 'price' => ...], ... ],
     *   'pricing_rule_id' => ?int,
     *   'promotion_package_id' => ?int,
     * ]
     *
     * $params:
     *   date          string  Y-m-d
     *   start_time    string  H:i
     *   end_time      string  H:i
     *   court_type    string  full|half
     *   promotion_code string|null  รหัสแพ็กเกจ (personal_general, personal_student, group_full, group_half, private_group)
     */
    public function calculate(array $params): array
    {
        $date = $params['date'];
        $start = $params['start_time'];
        $end = $params['end_time'];
        $courtType = $params['court_type'] ?? 'full';
        $promoCode = $params['promotion_code'] ?? null;

        if ($promoCode) {
            return $this->calculatePromotion($promoCode, $date, $start, $end);
        }

        return $this->calculateHourly($date, $start, $end, $courtType);
    }

    /**
     * ราคาปกติแบบตามชั่วโมง โดยแบ่งช่วงเวลาที่จองเป็นช่วงย่อยทุก 15 นาที
     * แล้วหา PricingRule ที่ตรงกับแต่ละช่วงย่อย (วันประเภทไหน + อยู่ในช่วง start-end ของ rule)
     * เพื่อรองรับกรณีจองข้ามช่วงเวลา เช่น 15:00-17:00 ที่คาบเกี่ยวทั้ง Weekday และ Sunset
     */
    protected function calculateHourly(string $date, string $start, string $end, string $courtType): array
    {
        $dayType = $this->classifyDayType($date);

        $stepMinutes = 15;
        $startAt = Carbon::parse("$date $start");
        $endAt = Carbon::parse("$date $end");

        if ($endAt->lte($startAt)) {
            throw new InvalidArgumentException('เวลาสิ้นสุดต้องอยู่หลังเวลาเริ่ม');
        }

        // สะสมนาทีต่อ rule ที่ match (rule_id => minutes)
        $rules = PricingRule::where('court_type', $courtType)
            ->where('is_active', true)
            ->whereIn('day_type', [$dayType, 'everyday'])
            ->orderByDesc('priority')
            ->get();

        if ($rules->isEmpty()) {
            throw new InvalidArgumentException("ไม่พบราคาสำหรับวันประเภท {$dayType} / สนาม {$courtType}");
        }

        $minutesPerRule = [];
        $cursor = $startAt->copy();

        while ($cursor->lt($endAt)) {
            $segmentEnd = $cursor->copy()->addMinutes($stepMinutes)->min($endAt);
            $timeOfDay = $cursor->format('H:i:s');

            // เลือก rule ที่ครอบคลุมเวลานี้ โดยให้ priority สูงกว่าชนะ (เผื่อมีกฎเฉพาะซ้อนกฎทั่วไป)
            $matched = $rules->first(function (PricingRule $rule) use ($timeOfDay) {
                return $timeOfDay >= $rule->start_time && $timeOfDay < $rule->end_time;
            });

            if (! $matched) {
                throw new InvalidArgumentException(
                    "ไม่มีอัตราค่าบริการรองรับเวลา {$cursor->format('H:i')} ({$timeOfDay}) กรุณาติดต่อแอดมิน"
                );
            }

            $segmentMinutes = $cursor->diffInMinutes($segmentEnd);
            $minutesPerRule[$matched->id] = ($minutesPerRule[$matched->id] ?? 0) + $segmentMinutes;

            $cursor = $segmentEnd;
        }

        $breakdown = [];
        $total = 0;
        $lastRuleId = null;

        foreach ($minutesPerRule as $ruleId => $minutes) {
            $rule = $rules->firstWhere('id', $ruleId);
            $price = (int) round($rule->price_per_hour * $minutes / 60);
            $total += $price;
            $lastRuleId = $ruleId;
            $breakdown[] = [
                'label' => $rule->label,
                'minutes' => $minutes,
                'price_per_hour' => $rule->price_per_hour,
                'price' => $price,
            ];
        }

        return [
            'total' => $total,
            'total_baht' => $total / 100,
            'breakdown' => $breakdown,
            'pricing_rule_id' => count($minutesPerRule) === 1 ? $lastRuleId : null,
            'promotion_package_id' => null,
        ];
    }

    /**
     * ราคาโปรโมชั่นแบบแพ็กเกจคงที่ (Personal Shooting / Group Court / Private Group)
     * - ตรวจว่าโปรใช้ได้เฉพาะวันจันทร์-ศุกร์ (ยกเว้นวันหยุดนักขัตฤกษ์) ตามเงื่อนไขใน image.png
     *   ยกเว้นแพ็กเกจ private ที่ไม่ผูกกับวันในตาราง (คอร์สแบบนัดเรียน)
     * - ระยะเวลาการจองต้องตรงกับ duration_hours ของแพ็กเกจ
     */
    protected function calculatePromotion(string $promoCode, string $date, string $start, string $end): array
    {
        $package = PromotionPackage::where('code', $promoCode)->where('is_active', true)->firstOrFail();

        $startAt = Carbon::parse("$date $start");
        $endAt = Carbon::parse("$date $end");
        $durationHours = round($startAt->diffInMinutes($endAt) / 60, 2);

        if ($package->category !== 'private' && $durationHours != $package->duration_hours) {
            throw new InvalidArgumentException(
                "แพ็กเกจ {$package->label} ต้องจองต่อเนื่อง {$package->duration_hours} ชั่วโมงเท่านั้น"
            );
        }

        $isHoliday = Holiday::isHoliday($date);
        $dayOfWeek = $startAt->dayOfWeekIso; // 1=Mon ... 7=Sun
        $isWeekend = $dayOfWeek >= 6;

        if ($package->category !== 'private' && ! $isHoliday && $dayOfWeek > 5) {
            throw new InvalidArgumentException("แพ็กเกจ {$package->label} ใช้ได้เฉพาะวันจันทร์-ศุกร์เท่านั้น");
        }

        $price = $package->base_price;
        $label = $package->label;

        if ($isHoliday && $package->holiday_price !== null) {
            $price = $package->holiday_price;
            $label .= ' (ราคาวันหยุดนักขัตฤกษ์)';
        } elseif (
            $isWeekend
            && $package->weekend_special_price !== null
            && $package->weekend_special_start
            && $package->weekend_special_end
            && $start >= substr($package->weekend_special_start, 0, 5)
            && $end <= substr($package->weekend_special_end, 0, 5)
        ) {
            $price = $package->weekend_special_price;
            $label .= ' (ราคาเสาร์-อาทิตย์ 07.00-11.00)';
        }

        return [
            'total' => $price,
            'total_baht' => $price / 100,
            'breakdown' => [[
                'label' => $label,
                'minutes' => $durationHours * 60,
                'price_per_hour' => null,
                'price' => $price,
            ]],
            'pricing_rule_id' => null,
            'promotion_package_id' => $package->id,
        ];
    }

    /**
     * จำแนกประเภทวัน: holiday (มีใน holidays table) > weekend (เสาร์-อาทิตย์) > weekday
     */
    public function classifyDayType(string $date): string
    {
        if (Holiday::isHoliday($date)) {
            return 'holiday';
        }

        return Carbon::parse($date)->dayOfWeekIso >= 6 ? 'weekend' : 'weekday';
    }
}
