@extends('layouts.app')

@section('title', 'จัดการลิงก์เว็บไซต์')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-4 sm:px-6 max-w-4xl">

        <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">จัดการลิงก์เว็บไซต์</h1>
        <p class="font-sarabun text-sm text-gray-400 mb-6">ตั้งค่าลิงก์สำหรับแต่ละส่วนของเว็บไซต์</p>

        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @php
            $groups = [
                'LINE' => [
                    [
                        'key' => 'line_footer_url', 'bag' => 'footer', 'route' => 'admin.line-links.footer', 'type' => 'url',
                        'title' => 'LINE ที่ Footer',
                        'desc' => 'ไอคอน LINE ในแถบท้ายเว็บ (footer) หน้าแรก',
                    ],
                    [
                        'key' => 'line_topup_url', 'bag' => 'topup', 'route' => 'admin.line-links.topup', 'type' => 'url',
                        'title' => 'LINE หน้าเติมเครดิต',
                        'desc' => 'ปุ่ม "เติมผ่านไลน์ — แอดไลน์แอดมิน" ในหน้าเติมเครดิตของผู้ใช้',
                    ],
                    [
                        'key' => 'line_course_url', 'bag' => 'course', 'route' => 'admin.line-links.course', 'type' => 'url',
                        'title' => 'LINE หน้าคอร์สเรียน',
                        'desc' => 'ปุ่ม "แอดไลน์สอบถามและสมัครเรียน" ในหน้ารายละเอียดคอร์ส',
                    ],
                    [
                        'key' => 'line_official_url', 'bag' => 'official', 'route' => 'admin.line-links.official', 'type' => 'url',
                        'title' => 'LINE ทางการ (จองผ่าน LINE)',
                        'desc' => 'ปุ่ม "จองผ่าน LINE" บนการ์ดรอบจองกลุ่ม (group session) ในหน้าแรก',
                    ],
                ],
                'โซเชียลมีเดีย' => [
                    [
                        'key' => 'facebook_url', 'bag' => 'facebook', 'route' => 'admin.line-links.facebook', 'type' => 'url',
                        'title' => 'Facebook',
                        'desc' => 'ไอคอน Facebook ที่ footer และลิงก์ "ติดตามเรา" ด้านล่างสุดของหน้าแรก',
                    ],
                    [
                        'key' => 'youtube_url', 'bag' => 'youtube', 'route' => 'admin.line-links.youtube', 'type' => 'url',
                        'title' => 'YouTube',
                        'desc' => 'ไอคอน YouTube ที่ footer หน้าแรก',
                    ],
                    [
                        'key' => 'instagram_url', 'bag' => 'instagram', 'route' => 'admin.line-links.instagram', 'type' => 'url',
                        'title' => 'Instagram',
                        'desc' => 'ไอคอน Instagram ที่ footer หน้าแรก',
                    ],
                ],
                'ข้อมูลติดต่อ' => [
                    [
                        'key' => 'contact_phone', 'bag' => 'phone', 'route' => 'admin.line-links.phone', 'type' => 'tel',
                        'title' => 'เบอร์โทรศัพท์',
                        'desc' => 'เบอร์ที่ปุ่ม "คัดลอกเบอร์" ในหน้าแรก และลิงก์โทรออกในหน้าคอร์สเรียน',
                    ],
                    [
                        'key' => 'contact_email', 'bag' => 'email', 'route' => 'admin.line-links.email', 'type' => 'email',
                        'title' => 'อีเมลติดต่อ',
                        'desc' => 'ลิงก์ "ติดต่อ" ด้านล่างสุดของหน้าแรก (เปิดโปรแกรมส่งอีเมล)',
                    ],
                ],
            ];
        @endphp

        @foreach($groups as $groupTitle => $rows)
            <h2 class="font-bold text-[15px] {{ $groupTitle === 'LINE' ? 'text-emerald-500' : 'text-gray-900' }} mb-3 mt-8 first:mt-0">{{ $groupTitle }}</h2>
            <div class="flex flex-col gap-4 mb-2">
                @foreach($rows as $row)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="font-semibold text-gray-700 text-sm">{{ $row['title'] }}</h3>
                        <p class="font-sarabun text-xs text-gray-400 mb-4 mt-1">{{ $row['desc'] }}</p>

                        <form method="POST" action="{{ route($row['route']) }}" class="flex flex-col sm:flex-row gap-3 items-start">
                            @csrf
                            <div class="flex-1 w-full">
                                <input type="{{ $row['type'] }}" name="{{ $row['key'] }}" value="{{ old($row['key'], $links[$row['key']] ?? null) }}"
                                       placeholder="{{ match($row['type']) {
                                           'tel' => 'เช่น 081-246-0000',
                                           'email' => 'เช่น contact@example.com',
                                           default => 'https://...',
                                       } }}"
                                       class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                              {{ $errors->{$row['bag']}->has($row['key'])
                                                  ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                                  : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                                @error($row['key'], $row['bag'])
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="text-sm font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg px-5 py-2 transition whitespace-nowrap cursor-pointer">
                                บันทึก
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endforeach

    </div>
</div>
@endsection
