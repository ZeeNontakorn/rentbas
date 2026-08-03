<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Facility;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    /**
     * Show edit form
     */
    public function edit()
    {
        $courts = Court::all();
        $facilities = Facility::orderBy('sort_order')->orderBy('name')->get();
        $reviews = Review::with(['user:id,name,email', 'ratings.facility:id,name', 'images'])
            ->latest()
            ->paginate(10, ['*'], 'reviews_page');

        $courtKeys = $courts->map(fn ($c) => 'court_img_'.$c->id)->all();
        $settings = Setting::values(array_merge(array_keys(Setting::DEFAULTS), $courtKeys));

        return view('edit-text', compact('settings', 'courts', 'facilities', 'reviews'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'about_title' => ['nullable', 'string', 'max:255'],
            'about_desc' => ['nullable', 'string', 'max:1000'],
            'promo_subtitle' => ['nullable', 'string', 'max:255'],
            'promo_title' => ['nullable', 'string', 'max:255'],
            'promo_card_title' => ['nullable', 'string', 'max:255'],
            'promo_card_sub' => ['nullable', 'string', 'max:255'],
            'promo_image_file' => ['nullable', 'image', 'max:5120'],
            'hero_img_1_file' => ['nullable', 'image', 'max:5120'],
            'hero_img_2_file' => ['nullable', 'image', 'max:5120'],
            'hero_img_3_file' => ['nullable', 'image', 'max:5120'],
            'about_img_1_file' => ['nullable', 'image', 'max:5120'],
            'about_img_2_file' => ['nullable', 'image', 'max:5120'],
            'about_img_3_file' => ['nullable', 'image', 'max:5120'],
            'courts_bg_file' => ['nullable', 'image', 'max:10240'],
            'community_img_file' => ['nullable', 'image', 'max:5120'],
        ]);

        $imageFields = [
            'promo_image_file' => 'promo_image',
            'hero_img_1_file' => 'hero_img_1',
            'hero_img_2_file' => 'hero_img_2',
            'hero_img_3_file' => 'hero_img_3',
            'about_img_1_file' => 'about_img_1',
            'about_img_2_file' => 'about_img_2',
            'about_img_3_file' => 'about_img_3',
            'courts_bg_file' => 'courts_bg',
            'community_img_file' => 'community_img',
        ];

        foreach ($imageFields as $fileInput => $settingKey) {
            if ($request->hasFile($fileInput)) {
                // Delete the previous file (if any) before storing the new one
                $this->deleteOldSettingFile($settingKey);

                $path = $request->file($fileInput)->store('site', 'public');
                // Store the path that can be used with asset() or Storage::url()
                $data[$settingKey] = 'media/'.$path;
            }
            unset($data[$fileInput]);
        }

        // Persist all settings
        foreach ($data as $key => $value) {
            if ($value !== null) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'บันทึกข้อมูลและรูปภาพหน้าเว็บเรียบร้อยแล้ว',
            ]);
        }

        return back();
    }

    /**
     * Delete the previously stored local file for a given setting key,
     * if one exists and it is not an external URL (e.g. Unsplash).
     */
    protected function deleteOldSettingFile(string $settingKey): void
    {
        $old = Setting::where('key', $settingKey)->value('value');

        if (! $old) {
            return;
        }

        // Skip external URLs (http/https) — nothing local to delete
        if (Str::startsWith($old, ['http://', 'https://'])) {
            return;
        }

        // Normalize legacy storage prefixes such as media/, storage/app/public/, etc.
        // so the file can be deleted from the public disk correctly.
        $relativePath = Setting::normalizeStoragePath($old);

        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
