<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use App\Models\PromotionPackage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PricingController extends Controller
{
    /**
     * หน้ารวมตั้งค่าราคา: ราคาตามช่วงเวลา (pricing_rules) + แพ็กเกจโปรโมชั่น (promotion_packages)
     */
    public function index()
    {
        $pricingRules = PricingRule::orderBy('day_type')->orderBy('court_type')->get();
        $promotionPackages = PromotionPackage::orderBy('category')->get();
        $existingPackageCategories = $promotionPackages->pluck('category')->filter()->unique()->sort()->values()->all();

        return view('admin.pricing.index', compact('pricingRules', 'promotionPackages', 'existingPackageCategories'));
    }

    protected function normalizePackageCode(?string $code, ?string $label = null, ?PromotionPackage $existing = null): string
    {
        $candidate = trim((string) ($code ?? ''));

        if ($candidate === '') {
            $candidate = PromotionPackage::generateCodeFromLabel($label ?? 'package', $existing?->code);
        } else {
            $candidate = PromotionPackage::generateCodeFromLabel($candidate, $existing?->code);
        }

        return $candidate;
    }

    /**
     * ช่วงเวลาที่อนุญาตให้ตั้งราคาได้ ตาม day_type ของ pricing rule นั้นๆ — ไม่ผูกกับชื่อ/id ของ
     * rule รายตัวใดๆ เลย (ไม่ hardcode) ดังนั้นถ้าในอนาคตมีการเพิ่ม day_type ใหม่ ระบบจะ fallback
     * ไปใช้ช่วงกลางวันให้อัตโนมัติโดยไม่ต้องมาแก้โค้ดจุดนี้เพิ่ม
     * - ช่วงกลางวัน (day_type ใดๆ ที่ไม่ใช่ 'everyday' เช่น weekday/weekend/holiday): 06:00–16:00
     * - ช่วงค่ำ/พระอาทิตย์ตก (day_type = 'everyday' — ดู $dayTypeLabel ในหน้า admin.pricing.index
     *   ที่ label ไว้ว่า "ทุกวัน (ช่วงค่ำ)"): 16:00–23:00
     */
    protected function allowedTimeWindow(string $dayType): array
    {
        return $dayType === 'everyday'
            ? ['16:00', '23:00']
            : ['06:00', '16:00'];
    }

    protected function assertWithinTimeWindow(string $dayType, string $start, string $end): void
    {
        [$min, $max] = $this->allowedTimeWindow($dayType);

        if ($start < $min || $end > $max) {
            $periodLabel = $dayType === 'everyday' ? 'ช่วงค่ำ' : 'ช่วงกลางวัน';

            throw ValidationException::withMessages([
                'start_time' => "ช่วงเวลาสำหรับ \"{$periodLabel}\" ต้องอยู่ระหว่าง {$min}–{$max} น. เท่านั้น",
            ]);
        }
    }

    /**
     * แก้ไขราคาต่อชั่วโมงของ pricing rule หนึ่งรายการ (เช่น Sunset Full Court)
     */
    public function updateRule(Request $request, PricingRule $pricingRule)
    {
        $data = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'price_per_hour' => ['required', 'numeric', 'min:0', 'max:100000'], // หน่วยบาท
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'start_time.required' => 'กรุณากรอกเวลาเริ่มต้น',
            'start_time.date_format' => 'รูปแบบเวลาเริ่มต้นไม่ถูกต้อง (ต้องเป็น HH:MM)',
            'end_time.required' => 'กรุณากรอกเวลาสิ้นสุด',
            'end_time.date_format' => 'รูปแบบเวลาสิ้นสุดไม่ถูกต้อง (ต้องเป็น HH:MM)',
            'end_time.after' => 'เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น',
            'price_per_hour.required' => 'กรุณากรอกราคาต่อชั่วโมง',
            'price_per_hour.numeric' => 'ราคาต่อชั่วโมงต้องเป็นตัวเลข',
            'price_per_hour.min' => 'ราคาต่อชั่วโมงต้องไม่น้อยกว่า 0 บาท',
            'price_per_hour.max' => 'ราคาต่อชั่วโมงต้องไม่เกิน 100,000 บาท',
        ]);

        $this->assertWithinTimeWindow($pricingRule->day_type, $data['start_time'], $data['end_time']);

        $pricingRule->update([
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'price_per_hour' => (int) round($data['price_per_hour'] * 100),
            'is_active' => $request->boolean('is_active', $pricingRule->is_active),
        ]);

        return back()->with('success', "อัปเดตราคา {$pricingRule->label} เรียบร้อย");
    }

    /**
     * แก้ไขราคาต่อชั่วโมงของ pricing rule หลายรายการพร้อมกัน (การ์ดเดียว = 1 form = 1 ปุ่มบันทึก)
     * ใช้แทน updateRule ในหน้า index ที่รวมทุกแถวในการ์ดเดียวกันไว้ในฟอร์มเดียว
     */
    public function bulkUpdateRules(Request $request)
    {
        $data = $request->validate([
            'rules' => ['required', 'array'],
            'rules.*.start_time' => ['required', 'date_format:H:i'],
            'rules.*.end_time' => ['required', 'date_format:H:i', 'after:rules.*.start_time'],
            'rules.*.price_per_hour' => ['required', 'numeric', 'min:0', 'max:100000'], // หน่วยบาท
            'rules.*.is_active' => ['sometimes', 'boolean'],
        ],[
            'rules.*.start_time.required' => 'กรุณากรอกเวลาเริ่มต้น',
            'rules.*.start_time.date_format' => 'รูปแบบเวลาเริ่มต้นไม่ถูกต้อง (ต้องเป็น HH:MM)',
            'rules.*.end_time.required' => 'กรุณากรอกเวลาสิ้นสุด',
            'rules.*.end_time.date_format' => 'รูปแบบเวลาสิ้นสุดไม่ถูกต้อง (ต้องเป็น HH:MM)',
            'rules.*.end_time.after' => 'เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น',
            'rules.*.price_per_hour.required' => 'กรุณากรอกราคาต่อชั่วโมง',
            'rules.*.price_per_hour.numeric' => 'ราคาต่อชั่วโมงต้องเป็นตัวเลข',
            'rules.*.price_per_hour.min' => 'ราคาต่อชั่วโมงต้องไม่น้อยกว่า 0 บาท',
            'rules.*.price_per_hour.max' => 'ราคาต่อชั่วโมงต้องไม่เกิน 100,000 บาท',
        ]);

        $rules = PricingRule::whereIn('id', array_keys($data['rules']))->get()->keyBy('id');

        // ตรวจสอบช่วงเวลาของทุกแถวให้ผ่านก่อน แล้วค่อย update จริง (กันเซฟไปแล้วครึ่งเดียวถ้าแถว
        // หลังๆ ในการ์ดเดียวกัน validation ไม่ผ่าน)
        foreach ($data['rules'] as $ruleId => $fields) {
            $pricingRule = $rules->get($ruleId);
            if (! $pricingRule) {
                abort(404);
            }
            $this->assertWithinTimeWindow($pricingRule->day_type, $fields['start_time'], $fields['end_time']);
        }

        foreach ($data['rules'] as $ruleId => $fields) {
            $rules[$ruleId]->update([
                'start_time' => $fields['start_time'],
                'end_time' => $fields['end_time'],
                'price_per_hour' => (int) round($fields['price_per_hour'] * 100),
                'is_active' => $request->boolean("rules.{$ruleId}.is_active", $rules[$ruleId]->is_active),
            ]);
        }

        return back()->with('success', 'บันทึกราคาตามช่วงเวลาเรียบร้อยแล้ว');
    }

    /**
     * กฎ validation ที่ใช้ร่วมกันทั้งตอนสร้างและแก้ไขแพ็กเกจ
     * (แยกเป็น method ให้เรียกซ้ำได้ ป้องกัน store/update เพี้ยนไปจากกัน)
     */
    protected function packageRules(?PromotionPackage $existing = null): array
    {
        return [
            'code' => [
                'nullable', 'string', 'max:50', 'alpha_dash',
                $existing ? "unique:promotion_packages,code,{$existing->id}" : 'nullable',
            ],
            'label' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:50'],
            'court_type' => ['nullable', 'in:full,half'],
            'available_days' => ['nullable', 'array'],
            'available_days.*' => ['in:weekday,weekend,holiday'],
            'available_start_time' => ['nullable', 'date_format:H:i'],
            'available_end_time' => ['nullable', 'date_format:H:i'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'max_people' => ['required', 'integer', 'min:1', 'max:1000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'holiday_price' => ['nullable', 'numeric', 'min:0'],
            'weekend_special_price' => ['nullable', 'numeric', 'min:0'],
            'weekend_special_start' => ['nullable', 'date_format:H:i', 'required_with:weekend_special_price'],
            'weekend_special_end' => ['nullable', 'date_format:H:i', 'after:weekend_special_start', 'required_with:weekend_special_price'],
            'requires_verification' => ['sometimes', 'boolean'],
            'session_count' => ['nullable', 'integer', 'min:1', 'max:255'],
            'validity_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * ข้อความ error ภาษาไทยของฟอร์มแพ็กเกจ (แยกเป็น method ให้เรียกซ้ำได้ทั้งตอนสร้างและแก้ไข
     * เช่นเดียวกับ packageRules())
     */
    protected function packageMessages(): array
    {
        return [
            'code.string' => 'รหัสภายในต้องเป็นตัวอักษร',
            'code.max' => 'รหัสภายในต้องไม่เกิน 50 ตัวอักษร',
            'code.alpha_dash' => 'รหัสภายในใช้ได้เฉพาะตัวอักษร ตัวเลข ขีดกลาง (-) และขีดล่าง (_) เท่านั้น',
            'code.unique' => 'รหัสภายในนี้ถูกใช้ไปแล้ว กรุณาตั้งรหัสอื่น',
            'label.required' => 'กรุณากรอกชื่อแพ็กเกจ',
            'label.string' => 'ชื่อแพ็กเกจต้องเป็นตัวอักษร',
            'label.max' => 'ชื่อแพ็กเกจต้องไม่เกิน 150 ตัวอักษร',
            'category.required' => 'กรุณากรอกหมวดหมู่',
            'category.string' => 'หมวดหมู่ต้องเป็นตัวอักษร',
            'category.max' => 'หมวดหมู่ต้องไม่เกิน 50 ตัวอักษร',
            'court_type.in' => 'กรุณาเลือกประเภทสนามให้ถูกต้อง',
            'available_days.array' => 'รูปแบบวันที่ใช้โปรได้ไม่ถูกต้อง',
            'available_days.*.in' => 'วันที่เลือกไม่ถูกต้อง กรุณาเลือกใหม่',
            'available_start_time.date_format' => 'รูปแบบเวลาเริ่มต้นไม่ถูกต้อง (ต้องเป็น HH:MM)',
            'available_end_time.date_format' => 'รูปแบบเวลาสิ้นสุดไม่ถูกต้อง (ต้องเป็น HH:MM)',
            'duration_hours.integer' => 'ระยะเวลาที่ต้องจองต้องเป็นตัวเลขจำนวนเต็ม',
            'duration_hours.min' => 'ระยะเวลาที่ต้องจองต้องไม่น้อยกว่า 1 ชั่วโมง',
            'duration_hours.max' => 'ระยะเวลาที่ต้องจองต้องไม่เกิน 24 ชั่วโมง',
            'max_people.required' => 'กรุณากรอกจำนวนคนสูงสุด',
            'max_people.integer' => 'จำนวนคนสูงสุดต้องเป็นตัวเลขจำนวนเต็ม',
            'max_people.min' => 'จำนวนคนสูงสุดต้องไม่น้อยกว่า 1 คน',
            'max_people.max' => 'จำนวนคนสูงสุดต้องไม่เกิน 1,000 คน',
            'base_price.required' => 'กรุณากรอกราคาปกติ',
            'base_price.numeric' => 'ราคาปกติต้องเป็นตัวเลข',
            'base_price.min' => 'ราคาปกติต้องไม่น้อยกว่า 0 บาท',
            'holiday_price.numeric' => 'ราคาวันหยุดนักขัตฤกษ์ต้องเป็นตัวเลข',
            'holiday_price.min' => 'ราคาวันหยุดนักขัตฤกษ์ต้องไม่น้อยกว่า 0 บาท',
            'weekend_special_price.numeric' => 'ราคาพิเศษเสาร์-อาทิตย์ต้องเป็นตัวเลข',
            'weekend_special_price.min' => 'ราคาพิเศษเสาร์-อาทิตย์ต้องไม่น้อยกว่า 0 บาท',
            'weekend_special_start.date_format' => 'รูปแบบเวลาเริ่มต้นราคาพิเศษไม่ถูกต้อง (ต้องเป็น HH:MM)',
            'weekend_special_start.required_with' => 'กรุณากรอกเวลาเริ่มต้น เนื่องจากมีการตั้งราคาพิเศษเสาร์-อาทิตย์',
            'weekend_special_end.date_format' => 'รูปแบบเวลาสิ้นสุดราคาพิเศษไม่ถูกต้อง (ต้องเป็น HH:MM)',
            'weekend_special_end.after' => 'เวลาสิ้นสุดราคาพิเศษต้องมากกว่าเวลาเริ่มต้น',
            'weekend_special_end.required_with' => 'กรุณากรอกเวลาสิ้นสุด เนื่องจากมีการตั้งราคาพิเศษเสาร์-อาทิตย์',
            'session_count.integer' => 'จำนวนครั้ง/เซสชันต้องเป็นตัวเลขจำนวนเต็ม',
            'session_count.min' => 'จำนวนครั้ง/เซสชันต้องไม่น้อยกว่า 1 ครั้ง',
            'session_count.max' => 'จำนวนครั้ง/เซสชันต้องไม่เกิน 255 ครั้ง',
            'validity_days.integer' => 'อายุแพ็กเกจต้องเป็นตัวเลขจำนวนเต็ม',
            'validity_days.min' => 'อายุแพ็กเกจต้องไม่น้อยกว่า 1 วัน',
            'validity_days.max' => 'อายุแพ็กเกจต้องไม่เกิน 3,650 วัน',
        ];
    }

    /**
     * แปลง input จากฟอร์ม (หน่วยบาท) เป็นค่าที่จะบันทึกลง DB (หน่วยสตางค์ + normalize ค่าว่าง)
     */
    protected function packagePayload(array $data, ?PromotionPackage $existing = null): array
    {
        $code = $this->normalizePackageCode($data['code'] ?? null, $data['label'] ?? null, $existing);

        return [
            'code' => $code,
            'label' => $data['label'],
            'category' => trim((string) ($data['category'] ?? '')) !== '' ? trim((string) $data['category']) : 'general',
            'court_type' => $data['court_type'] ?: null,
            'available_days' => ! empty($data['available_days']) ? array_values($data['available_days']) : null,
            'available_start_time' => $data['available_start_time'] ?: null,
            'available_end_time' => $data['available_end_time'] ?: null,
            'duration_hours' => isset($data['duration_hours']) && $data['duration_hours'] !== '' ? $data['duration_hours'] : null,
            'max_people' => $data['max_people'],
            'base_price' => (int) round($data['base_price'] * 100),
            'holiday_price' => isset($data['holiday_price']) && $data['holiday_price'] !== '' ? (int) round($data['holiday_price'] * 100) : null,
            'weekend_special_price' => isset($data['weekend_special_price']) && $data['weekend_special_price'] !== '' ? (int) round($data['weekend_special_price'] * 100) : null,
            'weekend_special_start' => $data['weekend_special_start'] ?: null,
            'weekend_special_end' => $data['weekend_special_end'] ?: null,
            'requires_verification' => (bool) ($data['requires_verification'] ?? false),
            'session_count' => $data['session_count'] ?? null,
            'validity_days' => $data['validity_days'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    /**
     * สร้างแพ็กเกจโปรโมชั่นใหม่ทั้งหมดเอง (ไม่ต้องแก้โค้ด/seeder) — กำหนดเงื่อนไขการจองจริง
     * (ประเภทสนาม/วัน/ช่วงเวลา/ระยะเวลา) ได้ครบ ไม่ใช่แค่ตั้งชื่อ+ราคาเฉยๆ
     */
    public function storePackage(Request $request)
    {
        $data = $request->validate($this->packageRules(), $this->packageMessages());

        $package = PromotionPackage::create($this->packagePayload($data));

        return back()->with('success', "เพิ่มแพ็กเกจ \"{$package->label}\" เรียบร้อยแล้ว");
    }

    /**
     * แก้ไขแพ็กเกจโปรโมชั่น — แก้ได้ทุกฟิลด์ (ชื่อ, ราคา, เงื่อนไขประเภทสนาม/วัน/ช่วงเวลา/ระยะเวลา ฯลฯ)
     */
    public function updatePackage(Request $request, PromotionPackage $promotionPackage)
    {
        $data = $request->validate($this->packageRules($promotionPackage), $this->packageMessages());

        $promotionPackage->update($this->packagePayload($data, $promotionPackage));

        return back()->with('success', "อัปเดตแพ็กเกจ \"{$promotionPackage->label}\" เรียบร้อย");
    }

    /**
     * ลบแพ็กเกจโปรโมชั่น — booking เก่าที่เคยอ้างอิงแพ็กเกจนี้จะไม่หายไปไหน (price/price_breakdown
     * ที่บันทึกไว้ตอนจองยังอยู่ครบ แค่ promotion_package_id จะถูกตั้งเป็น null อัตโนมัติ - nullOnDelete)
     */
    public function destroyPackage(PromotionPackage $promotionPackage)
    {
        $label = $promotionPackage->label;
        $promotionPackage->delete();

        return back()->with('success', "ลบแพ็กเกจ \"{$label}\" เรียบร้อยแล้ว");
    }
}
