<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class LineLinkController extends Controller
{
    /**
     * ค่าเดิมที่เคย hardcode ไว้ตรงๆ ในหน้าเว็บ (footer/หน้าคอร์ส ฯลฯ) — ใช้เป็นค่าเริ่มต้นให้ฟอร์มนี้
     * แสดงของจริงที่กำลังใช้งานอยู่ตอนนี้ (ไม่ใช่ช่องว่าง) ก่อนแอดมินจะเข้ามาแก้ไขครั้งแรก
     * ต้องตรงกับ fallback ที่ใช้ตอนเรียก Setting::getVal(...) ในหน้าเว็บจริงเสมอ
     */
    protected const FALLBACKS = [
        'line_footer_url' => 'https://line.me/R/ti/p/%40THATA-HC',
        'line_topup_url' => null,
        'line_course_url' => 'https://line.me/R/ti/p/%40THATA-HC',
        'line_official_url' => 'https://line.me/R/ti/p/%40THATA-HC',
        'facebook_url' => 'https://www.facebook.com/thatahomecourts/',
        'youtube_url' => 'https://www.youtube.com/THATASPORT',
        'instagram_url' => 'https://www.instagram.com/thata_homecourt',
        'contact_phone' => '081-246-0000',
        'contact_email' => 'thatahomecourt@gmail.com',
    ];

    /**
     * key => [validation rules, ป้ายชื่อ error message]
     * ทุกลิงก์แยกคีย์/แยกฟอร์มกันเพื่อให้แก้จุดไหนก็กระทบเฉพาะจุดนั้น ไม่ต้องกลัวเปลี่ยนแล้วพัง
     */
    protected function fieldRules(string $key): array
    {
        return match ($key) {
            'contact_phone' => ['nullable', 'string', 'regex:/^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{4}$/'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            default => ['nullable', 'url', 'max:255'],
        };
    }

    protected function fieldMessage(string $key): string
    {
        return match ($key) {
            'contact_phone' => 'กรุณากรอกเบอร์โทรให้ถูกต้อง เช่น 081-246-0000',
            'contact_email' => 'กรุณากรอกอีเมลให้ถูกต้อง',
            default => 'กรุณากรอกลิงก์ให้ถูกต้อง (ต้องขึ้นต้นด้วย http:// หรือ https://)',
        };
    }

    public function index()
    {
        $stored = Setting::values(array_keys(self::FALLBACKS));

        // ยังไม่เคยตั้งค่าใน DB (ค่า null) ให้โชว์ค่าที่หน้าเว็บใช้อยู่จริงตอนนี้แทน จะได้ไม่เห็นเป็นช่องว่าง
        $links = [];
        foreach (self::FALLBACKS as $key => $fallback) {
            $links[$key] = $stored[$key] ?? $fallback;
        }

        return view('admin.line-links.index', compact('links'));
    }

    public function updateFooter(Request $request)
    {
        return $this->updateOne($request, 'footer', 'line_footer_url');
    }

    public function updateTopup(Request $request)
    {
        return $this->updateOne($request, 'topup', 'line_topup_url');
    }

    public function updateCourse(Request $request)
    {
        return $this->updateOne($request, 'course', 'line_course_url');
    }

    public function updateOfficial(Request $request)
    {
        return $this->updateOne($request, 'official', 'line_official_url');
    }

    public function updateFacebook(Request $request)
    {
        return $this->updateOne($request, 'facebook', 'facebook_url');
    }

    public function updateYoutube(Request $request)
    {
        return $this->updateOne($request, 'youtube', 'youtube_url');
    }

    public function updateInstagram(Request $request)
    {
        return $this->updateOne($request, 'instagram', 'instagram_url');
    }

    public function updatePhone(Request $request)
    {
        return $this->updateOne($request, 'phone', 'contact_phone');
    }

    public function updateEmail(Request $request)
    {
        return $this->updateOne($request, 'email', 'contact_email');
    }

    protected function updateOne(Request $request, string $bag, string $key)
    {
        $data = $request->validateWithBag($bag, [
            $key => $this->fieldRules($key),
        ], [
            "{$key}.url" => $this->fieldMessage($key),
            "{$key}.regex" => $this->fieldMessage($key),
            "{$key}.email" => $this->fieldMessage($key),
        ]);

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $data[$key] ?? null]
        );

        return back()->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }
}
