<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebsiteReviewController extends Controller
{
    public function storeFacility(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], $this->facilityValidationMessages());

        if ($validator->fails()) {
            return redirect()->to(route('admin.edit.text').'#facility-management')
                ->withErrors($validator, 'facilityCreate')
                ->withInput();
        }

        $data = $validator->validated();

        Facility::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'image_path' => $request->file('image')->store('site/facilities', 'public'),
            'is_active' => true,
            'sort_order' => $data['sort_order'] ?? (Facility::max('sort_order') + 1),
        ]);

        return redirect()->to(route('admin.edit.text').'#facility-management')
            ->with('success', 'เพิ่มสิ่งอำนวยความสะดวกเรียบร้อยแล้ว');
    }

    public function updateFacility(Request $request, Facility $facility)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ], $this->facilityValidationMessages());

        if ($validator->fails()) {
            return redirect()->to(route('admin.edit.text').'#facility-'.$facility->id)
                ->withErrors($validator, 'facilityUpdate'.$facility->id)
                ->withInput();
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $this->deleteUploadedFacilityImage($facility);
            $data['image_path'] = $request->file('image')->store('site/facilities', 'public');
        }

        unset($data['image']);
        $data['is_active'] = $request->boolean('is_active');
        $facility->update($data);

        return redirect()->to(route('admin.edit.text').'#facility-management')
            ->with('success', 'อัปเดตสิ่งอำนวยความสะดวกเรียบร้อยแล้ว');
    }

    public function updateReviewStatus(Request $request, Review $review)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['published', 'hidden'])],
        ]);

        $review->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published'
                ? ($review->published_at ?? now())
                : $review->published_at,
        ]);

        Notification::where('title', 'มีรีวิวใหม่รอตรวจสอบ')
            ->where('message', 'like', "รีวิว #{$review->id} จาก%")
            ->update(['is_read' => true]);

        return redirect()->to(route('admin.edit.text').'#review-moderation')
            ->with('success', $data['status'] === 'published'
                ? 'เผยแพร่รีวิวบนหน้าเว็บไซต์แล้ว'
                : 'ซ่อนรีวิวจากหน้าเว็บไซต์แล้ว');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'facility';
        $slug = $base;
        $suffix = 2;

        while (Facility::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function deleteUploadedFacilityImage(Facility $facility): void
    {
        if (! $facility->image_path || str_starts_with($facility->image_path, 'images/')) {
            return;
        }

        $path = Setting::normalizeStoragePath($facility->image_path);
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function facilityValidationMessages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อหัวข้อ',
            'name.max' => 'ชื่อหัวข้อต้องไม่เกิน 100 ตัวอักษร',
            'description.max' => 'รายละเอียดต้องไม่เกิน 500 ตัวอักษร',
            'image.required' => 'กรุณาเลือกรูปภาพ',
            'image.image' => 'ไฟล์ที่เลือกต้องเป็นรูปภาพ',
            'image.mimes' => 'รองรับเฉพาะไฟล์ JPG, PNG และ WebP',
            'image.max' => 'รูปภาพต้องมีขนาดไม่เกิน 5 MB',
            'sort_order.required' => 'กรุณากรอกลำดับ',
            'sort_order.integer' => 'ลำดับต้องเป็นจำนวนเต็ม',
            'sort_order.min' => 'ลำดับต้องไม่น้อยกว่า 0',
            'sort_order.max' => 'ลำดับต้องไม่เกิน 999',
        ];
    }
}
