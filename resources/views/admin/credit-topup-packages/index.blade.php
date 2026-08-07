@extends('layouts.app')

@section('title', 'แพ็กเกจเติมเครดิต')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-4xl">

        <a href="{{ route('admin.credit-topups.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-orange-500 mb-6 transition font-medium group">
            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับไปหน้าคำขอเติมเครดิต
        </a>

        <h1 class="text-xl font-bold text-gray-800 mb-1">แพ็กเกจเครดิต &amp; โปรโมชั่น</h1>
        <p class="text-sm text-gray-400 mb-6">กำหนดราคาแพ็กเกจที่ผู้ใช้เลือกได้ในหน้าเติมเครดิต — ถ้าตั้งเครดิตที่ได้รับมากกว่ายอดชำระ ระบบจะถือเป็นโบนัส/โปรโมชั่นให้อัตโนมัติ</p>

        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
                @foreach ($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
            </div>
        @endif
        @if (auth()->user()->role === 'superadmin')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <p class="mb-2">ตั้งค่าเบอร์มือถือ PromptPay เพื่อรับเงิน</p>
                <form method="POST" action="{{ route('admin.credit-topup-packages.promptpay') }}">
                    @csrf
                    <input type="text" name="promptpay_number" value="{{ $promptpayNumber }}" placeholder="เช่น 0123456789"
                           class="w-48 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                    <button type="submit" class="text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg px-5 py-2 transition whitespace-nowrap">บันทึกเบอร์ PromptPay</button>
                </form>
            </div>
        @endif

        {{-- LINE URL --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-medium text-gray-700 text-sm mb-4">ลิงก์ LINE สำหรับปุ่ม "เติมผ่าน LINE ไวกว่า"</h2>
            <form method="POST" action="{{ route('admin.credit-topup-packages.line-url') }}" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="url" name="line_topup_url" value="{{ $lineUrl }}" placeholder="https://line.me/R/ti/p/@yourlineid"
                       class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                <button type="submit" class="text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg px-5 py-2 transition whitespace-nowrap">บันทึกลิงก์</button>
            </form>
        </div>

        {{-- Add package --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-medium text-gray-700 text-sm mb-4">เพิ่มแพ็กเกจใหม่</h2>
            <form method="POST" action="{{ route('admin.credit-topup-packages.store') }}" class="grid sm:grid-cols-5 gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ป้ายชื่อ</label>
                    <input type="text" name="label" required placeholder="เช่น 250" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ราคา (บาท)</label>
                    <input type="number" step="0.01" min="1" name="price" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">เครดิตที่ได้ (บาท)</label>
                    <input type="number" step="0.01" min="1" name="credit" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ลำดับ</label>
                    <input type="number" min="0" name="sort_order" value="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-5 py-2 transition">+ เพิ่ม</button>
            </form>
        </div>

        {{-- Package list --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 font-medium">ป้ายชื่อ</th>
                            <th class="px-6 py-3 font-medium text-right">ราคา</th>
                            <th class="px-6 py-3 font-medium text-right">เครดิตที่ได้</th>
                            <th class="px-6 py-3 font-medium text-right">โบนัส</th>
                            <th class="px-6 py-3 font-medium text-center">แสดงผล</th>
                            <th class="px-6 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($packages as $pkg)
                            <tr>
                                <td class="px-6 py-3"><input form="editPkg{{ $pkg->id }}" type="text" name="label" value="{{ $pkg->label }}" class="w-24 rounded border border-gray-200 px-2 py-1 text-sm"></td>
                                <td class="px-6 py-3 text-right"><input form="editPkg{{ $pkg->id }}" type="number" step="0.01" name="price" value="{{ $pkg->price_satang / 100 }}" class="w-24 rounded border border-gray-200 px-2 py-1 text-sm text-right"></td>
                                <td class="px-6 py-3 text-right"><input form="editPkg{{ $pkg->id }}" type="number" step="0.01" name="credit" value="{{ $pkg->credit_satang / 100 }}" class="w-24 rounded border border-gray-200 px-2 py-1 text-sm text-right"></td>
                                <td class="px-6 py-3 text-right text-amber-600 font-medium">
                                    @if($pkg->bonus_satang > 0) +฿{{ number_format($pkg->bonus_satang / 100, 0) }} @else — @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <input type="hidden" form="editPkg{{ $pkg->id }}" name="sort_order" value="{{ $pkg->sort_order }}">
                                    <input form="editPkg{{ $pkg->id }}" type="checkbox" name="is_active" value="1" {{ $pkg->is_active ? 'checked' : '' }} class="accent-emerald-500 w-4 h-4">
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <button type="submit" form="editPkg{{ $pkg->id }}" class="text-emerald-600 hover:text-emerald-700 font-medium text-xs mr-3">บันทึก</button>
                                    <form method="POST" action="{{ route('admin.credit-topup-packages.destroy', $pkg) }}" class="inline" onsubmit="return confirm('ลบแพ็กเกจนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-600 font-medium text-xs">ลบ</button>
                                    </form>
                                </td>
                            </tr>
                            <form id="editPkg{{ $pkg->id }}" method="POST" action="{{ route('admin.credit-topup-packages.update', $pkg) }}" class="hidden">
                                @csrf
                                @method('PUT')
                            </form>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">ยังไม่มีแพ็กเกจ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
