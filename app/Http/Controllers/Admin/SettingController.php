<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Show edit form
     */
    public function edit()
    {
        $settings = Setting::values();

        return view('edit-text', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'about_title'      => ['nullable', 'string', 'max:255'],
            'about_desc'       => ['nullable', 'string', 'max:1000'],
            'promo_subtitle'   => ['nullable', 'string', 'max:255'],
            'promo_title'      => ['nullable', 'string', 'max:255'],
            'promo_card_title' => ['nullable', 'string', 'max:255'],
            'promo_card_sub'   => ['nullable', 'string', 'max:255'],
            'promo_image_file' => ['nullable', 'image', 'max:5120'],
            'hero_img_1_file'  => ['nullable', 'image', 'max:5120'],
            'hero_img_2_file'  => ['nullable', 'image', 'max:5120'],
            'hero_img_3_file'  => ['nullable', 'image', 'max:5120'],
            'about_img_1_file' => ['nullable', 'image', 'max:5120'],
            'about_img_2_file' => ['nullable', 'image', 'max:5120'],
            'about_img_3_file' => ['nullable', 'image', 'max:5120'],
            'courts_bg_file'   => ['nullable', 'image', 'max:10240'],
            'community_img_file'=> ['nullable', 'image', 'max:5120'],
        ]);

        $imageFields = [
            'promo_image_file' => 'promo_image',
            'hero_img_1_file'  => 'hero_img_1',
            'hero_img_2_file'  => 'hero_img_2',
            'hero_img_3_file'  => 'hero_img_3',
            'about_img_1_file' => 'about_img_1',
            'about_img_2_file' => 'about_img_2',
            'about_img_3_file' => 'about_img_3',
            'courts_bg_file'   => 'courts_bg',
            'community_img_file'=>'community_img',
        ];

        foreach ($imageFields as $fileInput => $settingKey) {
            if ($request->hasFile($fileInput)) {
                $path = $request->file($fileInput)->store('site', 'public');
                // Store the path that can be used with asset() or Storage::url()
                $data[$settingKey] = 'storage/' . $path;
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

        return back()->with('success', 'บันทึกข้อมูลและรูปภาพหน้าเว็บเรียบร้อยแล้ว');
    }
}
