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
     * แก้ไขราคาแพ็กเกจโปรโมชั่น (Personal / Group / Private)
     */
    public function updatePackage(Request $request, PromotionPackage $promotionPackage)
    {
        $data = $request->validate([
            'base_price' => ['required', 'numeric', 'min:0'],
            'holiday_price' => ['nullable', 'numeric', 'min:0'],
            'weekend_special_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $promotionPackage->update([
            'base_price' => (int) round($data['base_price'] * 100),
            'holiday_price' => isset($data['holiday_price']) ? (int) round($data['holiday_price'] * 100) : null,
            'weekend_special_price' => isset($data['weekend_special_price']) ? (int) round($data['weekend_special_price'] * 100) : null,
            'is_active' => $request->boolean('is_active', $promotionPackage->is_active),
        ]);

        return back()->with('success', "อัปเดตแพ็กเกจ {$promotionPackage->label} เรียบร้อย");
    }
}
