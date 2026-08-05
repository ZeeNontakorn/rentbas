@extends('layouts.app')

@section('title', 'คำขอเติมเครดิต')

@php
    $statusMeta = [
        'pending' => ['label' => 'รอตรวจสอบ', 'bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
        'approved' => ['label' => 'อนุมัติแล้ว', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
        'rejected' => ['label' => 'ปฏิเสธแล้ว', 'bg' => 'bg-red-100', 'text' => 'text-red-600'],
    ];
    $methodMeta = [
        'promptpay' => 'PromptPay',
        'line' => 'LINE',
        'cash_counter' => 'เงินสด/เคาน์เตอร์',
    ];
@endphp

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-6xl">

        <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
            <div>
                <h1 class="text-xl font-bold text-gray-800">คำขอเติมเครดิต</h1>
                <p class="text-sm text-gray-400 mt-0.5">ตรวจสอบสลิป/หลักฐานการชำระเงิน แล้วอนุมัติหรือปฏิเสธคำขอของผู้ใช้</p>
            </div>
            <a href="{{ route('admin.credit-topup-packages.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">
                จัดการแพ็กเกจ/โปรโมชั่น →
            </a>
        </div>

        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
                @foreach ($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
            </div>
        @endif

        <div class="flex gap-2 mb-5">
            @foreach(['pending' => 'รอตรวจสอบ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ปฏิเสธแล้ว', 'all' => 'ทั้งหมด'] as $key => $label)
                <a href="{{ route('admin.credit-topups.index', ['status' => $key]) }}"
                   class="px-4 py-1.5 rounded-full text-xs font-semibold transition {{ $status === $key ? 'bg-gray-900 text-white' : 'bg-white border border-gray-200 text-gray-500 hover:border-gray-400' }}">
                    {{ $label }}
                    @if($key === 'pending' && $pendingCount > 0)
                        <span class="ml-1 bg-amber-400 text-amber-900 rounded-full px-1.5">{{ $pendingCount }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 font-medium">วันที่ยื่น</th>
                            <th class="px-6 py-3 font-medium">ผู้ใช้</th>
                            <th class="px-6 py-3 font-medium">เติมโดย</th>
                            <th class="px-6 py-3 font-medium">ช่องทาง</th>
                            <th class="px-6 py-3 font-medium text-right">ยอดชำระ</th>
                            <th class="px-6 py-3 font-medium text-right">เครดิตที่ได้</th>
                            <th class="px-6 py-3 font-medium">สถานะ</th>
                            <th class="px-6 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($requests as $req)
                            @php $meta = $statusMeta[$req->status]; @endphp
                            <tr>
                                <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $req->user->name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $req->topper_name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $methodMeta[$req->payment_method] ?? $req->payment_method }}</td>
                                <td class="px-6 py-3 text-right text-gray-700">฿{{ number_format($req->price_satang / 100, 2) }}</td>
                                <td class="px-6 py-3 text-right font-medium text-emerald-600">฿{{ number_format($req->credit_satang / 100, 2) }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $meta['bg'] }} {{ $meta['text'] }}">{{ $meta['label'] }}</span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('admin.credit-topups.show', $req) }}" class="text-emerald-600 hover:text-emerald-700 font-medium text-xs">ดูรายละเอียด →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-10 text-center text-gray-400">ไม่มีคำขอในหมวดนี้</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
