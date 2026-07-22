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
            return $this->calculatePromotion($promoCode, $date, $start, $end, $courtType);
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
     * ราคาโปรโมชั่นแบบแพ็กเกจคงที่ (Personal Shooting / Group Court / Private Group / อื่นๆ ที่แอดมินสร้างเอง)
     *
     * ทุกเงื่อนไขด้านล่างมาจากคอลัมน์ที่แอดมินตั้งค่าได้จริงในหน้าตั้งราคา (ไม่มีการ hardcode
     * ตาม category/code ของแพ็กเกจอีกต่อไป) — ทุกฟิลด์เงื่อนไข "null/ว่าง" หมายถึง "ไม่จำกัด":
     *   - court_type            : เต็มสนาม/ครึ่งสนาม/ทั้งสองแบบ (null)
     *   - duration_hours        : ต้องจองต่อเนื่องกี่ชั่วโมง / null = ไม่บังคับ (เช่น private นัดเอง)
     *   - available_days        : ['weekday','weekend','holiday'] วันไหนใช้โปรนี้ได้บ้าง / null = ทุกวัน
     *   - available_start_time/available_end_time : ช่วงเวลาที่อนุญาตให้ใช้โปรนี้ / null = ทั้งวัน
     */
    protected function calculatePromotion(string $promoCode, string $date, string $start, string $end, string $courtType): array
    {
        $package = PromotionPackage::where('code', $promoCode)->where('is_active', true)->firstOrFail();

        // 1) ประเภทสนาม: เต็มสนาม/ครึ่งสนาม ต้องตรงกับที่แพ็กเกจกำหนดไว้ (ถ้ากำหนด)
        if ($package->court_type !== null && $package->court_type !== $courtType) {
            $need = $package->court_type === 'full' ? 'เต็มสนาม' : 'ครึ่งสนาม';
            throw new InvalidArgumentException("แพ็กเกจ {$package->label} ใช้ได้เฉพาะ{$need}เท่านั้น");
        }

        $startAt = Carbon::parse("$date $start");
        $endAt = Carbon::parse("$date $end");
        $durationHours = round($startAt->diffInMinutes($endAt) / 60, 2);

        // 2) ระยะเวลาการจองต้องตรงกับที่แพ็กเกจกำหนด (ถ้ากำหนด)
        if ($package->duration_hours !== null && $durationHours != $package->duration_hours) {
            throw new InvalidArgumentException(
                "แพ็กเกจ {$package->label} ต้องจองต่อเนื่อง {$package->duration_hours} ชั่วโมงเท่านั้น"
            );
        }

        // 3) วันที่ที่อนุญาตให้ใช้โปรนี้ (ถ้ากำหนด) — เทียบกับประเภทวันจริง (holiday > weekend > weekday)
        $dayType = $this->classifyDayType($date);
        if (! empty($package->available_days) && ! in_array($dayType, $package->available_days, true)) {
            $dayLabels = ['weekday' => 'จันทร์-ศุกร์', 'weekend' => 'เสาร์-อาทิตย์', 'holiday' => 'วันหยุดนักขัตฤกษ์'];
            $allowed = implode(', ', array_map(fn ($d) => $dayLabels[$d] ?? $d, $package->available_days));
            throw new InvalidArgumentException("แพ็กเกจ {$package->label} ใช้ได้เฉพาะวัน: {$allowed}");
        }

        // 4) ช่วงเวลาของวันที่อนุญาตให้ใช้โปรนี้ (ถ้ากำหนด) — เวลาที่จองต้องอยู่ในกรอบนี้ทั้งหมด
        if ($package->available_start_time && $start < substr($package->available_start_time, 0, 5)) {
            throw new InvalidArgumentException(
                "แพ็กเกจ {$package->label} เริ่มใช้ได้ตั้งแต่เวลา " . substr($package->available_start_time, 0, 5) . ' น. เป็นต้นไป'
            );
        }
        if ($package->available_end_time && $end > substr($package->available_end_time, 0, 5)) {
            throw new InvalidArgumentException(
                "แพ็กเกจ {$package->label} ใช้ได้ถึงเวลา " . substr($package->available_end_time, 0, 5) . ' น. เท่านั้น'
            );
        }

        $isHoliday = $dayType === 'holiday';
        $isWeekend = $startAt->dayOfWeekIso >= 6;

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
            $label .= ' (ราคาเสาร์-อาทิตย์ ' . substr($package->weekend_special_start, 0, 5) . '-' . substr($package->weekend_special_end, 0, 5) . ')';
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
