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
            // ป้ายชื่อใช้เป็นตัวเลข (จำนวนบาท) ล้วนๆ เท่านั้น เช่น "250" — ห้ามมีตัวอักษร/สัญลักษณ์ปน
            'label' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'price' => ['required', 'numeric', 'min:20', 'max:1000000'],
            'credit' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'label.regex' => 'ป้ายชื่อต้องเป็นตัวเลขเท่านั้น (เช่น 250)',
            'price.min' => 'ราคาแพ็กเกจต้องไม่ต่ำกว่า 20 บาท',
            'sort_order.integer' => 'ลำดับต้องเป็นตัวเลขจำนวนเต็มเท่านั้น',
            'sort_order.min' => 'ลำดับต้องไม่ติดลบ',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validateWithBag('createPackage', $this->rules(), $this->messages());

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
        // แต่ละแถวมี field ชื่อเดียวกัน (label/price/credit) ซ้ำกันทั้งหน้า ถ้าไม่แยก error bag
        // ต่อแถว การกรอกผิดในแถวเดียวจะไปเด้ง error/กรอบแดงขึ้นทุกแถวพร้อมกัน
        $data = $request->validateWithBag("editPkg{$creditTopupPackage->id}", $this->rules(), $this->messages());

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
        $data = $request->validateWithBag('lineUrl', [
            'line_topup_url' => ['nullable', 'url', 'max:255'],
        ], [
            'line_topup_url.url' => 'กรุณากรอกลิงก์ให้ถูกต้อง (ต้องขึ้นต้นด้วย http:// หรือ https://)',
        ]);

        Setting::updateOrCreate(
            ['key' => 'line_topup_url'],
            ['value' => $data['line_topup_url'] ?? null]
        );

        return back()->with('success', 'บันทึกลิงก์ LINE เรียบร้อยแล้ว');
    }

    public function updatePromptpayNumber(Request $request)
    {
        $data = $request->validateWithBag('promptpay', [
            // เบอร์มือถือไทย: ขึ้นต้นด้วย 0 ตามด้วยเลข 9 หลัก (รวม 10 หลัก) ไม่รับขีด/วงเล็บ/เว้นวรรค
            'promptpay_number' => ['required', 'string', 'regex:/^0[0-9]{9}$/'],
            // ชื่อบัญชีต้องเป็นตัวอักษรไทยเท่านั้น (เว้นวรรค/จุดได้ เผื่อคำนำหน้าเช่น "น.ส.")
            'promptpay_name' => ['required', 'string', 'max:100', 'regex:/^[\x{0E00}-\x{0E7F}\s.]+$/u'],
        ], [
            'promptpay_number.regex' => 'กรุณากรอกเบอร์มือถือให้ถูกต้อง (ขึ้นต้นด้วย 0 ตามด้วยตัวเลข 9 หลัก เช่น 0812345678)',
            'promptpay_name.regex' => 'กรุณากรอกชื่อบัญชีเป็นภาษาไทยเท่านั้น',
        ]);

        Setting::updateOrCreate(
            ['key' => 'promptpay_number'],
            ['value' => $data['promptpay_number']]
        );

        return back()->with('success', 'บันทึกเบอร์ PromptPay เรียบร้อยแล้ว');
    }
}
