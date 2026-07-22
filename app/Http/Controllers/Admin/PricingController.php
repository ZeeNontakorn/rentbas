<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use App\Models\PromotionPackage;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    /**
     * หน้ารวมตั้งค่าราคา: ราคาตามช่วงเวลา (pricing_rules) + แพ็กเกจโปรโมชั่น (promotion_packages)
     */
    public function index()
    {
        $pricingRules = PricingRule::orderBy('day_type')->orderBy('court_type')->get();
        $promotionPackages = PromotionPackage::orderBy('category')->get();

        return view('admin.pricing.index', compact('pricingRules', 'promotionPackages'));
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
        ]);

        $pricingRule->update([
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'price_per_hour' => (int) round($data['price_per_hour'] * 100),
            'is_active' => $request->boolean('is_active', $pricingRule->is_active),
        ]);

        return back()->with('success', "อัปเดตราคา {$pricingRule->label} เรียบร้อย");
    }

    /**
     * กฎ validation ที่ใช้ร่วมกันทั้งตอนสร้างและแก้ไขแพ็กเกจ
     * (แยกเป็น method ให้เรียกซ้ำได้ ป้องกัน store/update เพี้ยนไปจากกัน)
     */
    protected function packageRules(?PromotionPackage $existing = null): array
    {
        return [
            'code' => [
                'required', 'string', 'max:50', 'alpha_dash',
                $existing ? "unique:promotion_packages,code,{$existing->id}" : 'unique:promotion_packages,code',
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
     * แปลง input จากฟอร์ม (หน่วยบาท) เป็นค่าที่จะบันทึกลง DB (หน่วยสตางค์ + normalize ค่าว่าง)
     */
    protected function packagePayload(array $data): array
    {
        return [
            'code' => $data['code'],
            'label' => $data['label'],
            'category' => $data['category'],
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
        $data = $request->validate($this->packageRules());

        $package = PromotionPackage::create($this->packagePayload($data));

        return back()->with('success', "เพิ่มแพ็กเกจ \"{$package->label}\" เรียบร้อยแล้ว");
    }

    /**
     * แก้ไขแพ็กเกจโปรโมชั่น — แก้ได้ทุกฟิลด์ (ชื่อ, ราคา, เงื่อนไขประเภทสนาม/วัน/ช่วงเวลา/ระยะเวลา ฯลฯ)
     */
    public function updatePackage(Request $request, PromotionPackage $promotionPackage)
    {
        $data = $request->validate($this->packageRules($promotionPackage));

        $promotionPackage->update($this->packagePayload($data));

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
