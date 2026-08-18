@extends('layouts.app')

@section('title', 'จัดการเครดิต: ' . $user->name)

@php
    $typeMeta = [
        'topup' => ['label' => 'เติมเครดิต', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'sign' => '+'],
        'deduct' => ['label' => 'หักค่าจอง', 'bg' => 'bg-red-100', 'text' => 'text-red-600', 'sign' => '-'],
        'refund' => ['label' => 'คืนเครดิต', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'sign' => '+'],
    ];
@endphp

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

        @include('components.mail-loading-overlay')

        {{-- Breadcrumb --}}
        <a href="{{ route('admin.users.show', $user) }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-orange-500 mb-6 transition font-medium group">
            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับไปหน้าข้อมูลผู้ใช้
        </a>

        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
                @foreach ($errors->all() as $err)
                    <div>• {{ $err }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid md:grid-cols-3 gap-6 mb-6">

            {{-- Balance + user summary --}}
            <div class="md:col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-t-4 border-t-emerald-500">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-11 h-11 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-orange-600 text-lg font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <h1 class="font-semibold text-gray-800 leading-tight">{{ $user->name }}</h1>
                        <p class="text-xs text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>

                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">ยอดเครดิตคงเหลือ</p>
                <p class="text-3xl font-bold text-emerald-600">฿{{ number_format($user->credit_balance / 100, 2) }}</p>
            </div>

            {{-- Top-up form --}}
            <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="font-medium text-gray-700 text-sm mb-4">เติมเครดิตให้ผู้ใช้รายนี้</h2>

                <form method="POST" action="{{ route('admin.credits.topup', $user) }}" class="flex flex-col sm:flex-row items-end gap-3"
                      onsubmit="showMailLoadingOverlay('กำลังเติมเครดิตและส่งอีเมลใบเสร็จให้ลูกค้า...'); this.querySelector('button[type=submit]').disabled = true;">
                    @csrf
                    <div class="flex flex-col sm:flex-row gap-6 w-full">
                        <div>
                            <div class="flex-1 w-full">
                                <label class="block text-xs font-medium text-gray-500 mb-1">จำนวนเงิน (บาท)</label>
                                <input type="number" step="0.01" min="1" name="amount" required
                                    placeholder="เช่น 500"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                            </div>
                            <div class="flex-1 w-full mx-auto mt-3">
                                <label class="block text-xs font-medium text-gray-500 mb-1">หมายเหตุ (ถ้ามี)</label>
                                <input type="text" name="note" maxlength="255" placeholder="เช่น เติมเงินสดหน้าเคาน์เตอร์"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                            </div>
                        </div>
                        <div class="flex-1 w-full">
                            <div class="flex-1 w-full">
                                <label class="block text-xs font-medium text-gray-500 mb-1">ช่องทางชำระเงิน</label>
                                <select name="payment_method" required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none cursor-pointer">
                                    <option value="">-- เลือกช่องทาง --</option>
                                    <option value="line">LINE / QR Code</option>
                                    <option value="cash_counter">ชำระเงินสดที่เคาน์เตอร์</option>
                                </select>
                            </div>
                            <div class="flex-1 w-full mx-auto mt-3">
                                <label class="block text-xs font-medium text-gray-500 mb-1">ชื่อ-นามสกุลผู้ดำเนินการ </label>
                                <input type="text" name="processed_by_name" required maxlength="100" placeholder="เช่น สมชาย ใจดี"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-5 py-2 transition whitespace-nowrap cursor-pointer">
                        เติมเครดิต
                    </button>
                </form>
            </div>
        </div>

        {{-- Transaction history --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <h2 class="font-medium text-gray-700 text-sm">ประวัติธุรกรรมเครดิต</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 font-medium">วันที่</th>
                            <th class="px-6 py-3 font-medium">ประเภท</th>
                            <th class="px-6 py-3 font-medium">การจองที่เกี่ยวข้อง</th>
                            <th class="px-6 py-3 font-medium">รายละเอียด</th>
                            <th class="px-6 py-3 font-medium text-right">จำนวนเงิน</th>
                            <th class="px-6 py-3 font-medium text-right">คงเหลือหลังทำรายการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $tx)
                            @php $meta = $typeMeta[$tx->type] ?? ['label' => $tx->type, 'bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'sign' => ''] @endphp
                            <tr>
                                <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $meta['bg'] }} {{ $meta['text'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-500">
                                    @if($tx->booking_id)
                                        #{{ str_pad($tx->booking_id, 6, '0', STR_PAD_LEFT) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-500 max-w-[220px] truncate" title="{{ $tx->note }}">
                                    @if($tx->admin)
                                        <span class="block text-[11px] text-gray-400">โดย {{ $tx->admin->name }}</span>
                                        <span class="block text-[11px] text-gray-400">ช่องทาง: {{ $tx->payment_method ?? '—' }}</span>
                                        <span class="block text-[11px] text-gray-400">ดำเนินการโดย: {{ $tx->processed_by_name ?? '—' }}</span>
                                    @endif
                                    <span class="block text-[11px] text-gray-400">หมายเหตุ: {{ $tx->note ?? '—' }}</span>
                                </td>
                                <td class="px-6 py-3 text-right font-medium {{ $meta['text'] }}">
                                    {{ $meta['sign'] }}฿{{ number_format($tx->amount / 100, 2) }}
                                </td>
                                <td class="px-6 py-3 text-right text-gray-700 font-medium">฿{{ number_format($tx->balance_after / 100, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400">ยังไม่มีประวัติธุรกรรมเครดิต</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
