<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTopupPackage;
use App\Models\Setting;
use Illuminate\Http\Request;

class CreditTopupPackageController extends Controller
{
    /**
     * รายการแพ็กเกจเติมเครดิตทั้งหมด + ฟอร์มตั้งค่าลิงก์ LINE OA สำหรับปุ่ม "เติมผ่าน LINE ไวกว่า"
     */
    public function index()
    {
        $packages = CreditTopupPackage::orderBy('sort_order')->orderBy('price_satang')->get();
        $lineUrl = Setting::getVal('line_topup_url');
        $promptpayNumber = Setting::getVal('promptpay_number');

        return view('admin.credit-topup-packages.index', compact('packages', 'lineUrl', 'promptpayNumber'));
    }

    protected function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'credit' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        CreditTopupPackage::create([
            'label' => $data['label'],
            'price_satang' => (int) round($data['price'] * 100),
            'credit_satang' => (int) round($data['credit'] * 100),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'เพิ่มแพ็กเกจเติมเครดิตเรียบร้อยแล้ว');
    }

    public function update(Request $request, CreditTopupPackage $creditTopupPackage)
    {
        $data = $request->validate($this->rules());

        $creditTopupPackage->update([
            'label' => $data['label'],
            'price_satang' => (int) round($data['price'] * 100),
            'credit_satang' => (int) round($data['credit'] * 100),
            // ไม่ใส่ default true ตรงนี้ — ถ้าแอดมินไม่ติ๊กช่อง แปลว่าตั้งใจปิดการแสดงผล (checkbox ที่ไม่ติ๊ก
            // จะไม่ถูกส่งมาใน request เลย ต่างจากตอนสร้างใหม่ที่ยังไม่มี checkbox ให้เลือก)
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'บันทึกแพ็กเกจเติมเครดิตเรียบร้อยแล้ว');
    }

    public function destroy(CreditTopupPackage $creditTopupPackage)
    {
        $creditTopupPackage->delete();

        return back()->with('success', 'ลบแพ็กเกจเติมเครดิตเรียบร้อยแล้ว');
    }

    /**
     * บันทึกลิงก์ LINE OA ที่ใช้กับปุ่ม "เติมผ่าน LINE ไวกว่า" ในหน้าเติมเครดิตของผู้ใช้
     */
    public function updateLineUrl(Request $request)
    {
        $data = $request->validate([
            'line_topup_url' => ['nullable', 'url', 'max:255'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'line_topup_url'],
            ['value' => $data['line_topup_url'] ?? null]
        );

        return back()->with('success', 'บันทึกลิงก์ LINE เรียบร้อยแล้ว');
    }

    public function updatePromptpayNumber(Request $request)
    {
        $data = $request->validate([
            'promptpay_number' => ['required', 'string', 'max:20'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'promptpay_number'],
            ['value' => $data['promptpay_number']]
        );

        return back()->with('success', 'บันทึกเบอร์ PromptPay เรียบร้อยแล้ว');
    }
}
