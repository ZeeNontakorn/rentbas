@extends('layouts.app')

@section('title', 'จัดการการจอง')

@php
    $statusMap = [
        'pending' => ['label' => 'รออนุมัติ', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-500'],
        'approved' => ['label' => 'อนุมัติแล้ว', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-500'],
        'rejected' => ['label' => 'ปฏิเสธ', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-500'],
        'cancelled' => ['label' => 'ยกเลิก', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-500'],
    ];

    $selectedBookingId = request('selected_booking_id');
    $selectedBooking = $selectedBookingId ? $bookings->firstWhere('id', $selectedBookingId) : $bookings->first();
@endphp

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-7xl">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-800">จัดการการจอง</h1>
            <p class="text-sm text-gray-500">ดูสถิติ จัดการสนาม และดูการจองทั้งหมด</p>
        </div>

        <!-- Summary Cards -->
        @include('admin.partials.summary_cards')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Column 1: Date/Court filter & Bookings List -->
            <div class="lg:col-span-4 flex flex-col gap-4">

                <!-- Filters -->
                <form method="GET" action="{{ route('admin.bookings') }}" class="flex gap-4">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <div class="flex-1">
                        <label class="text-xs text-blue-500 font-medium ml-2">Date</label>
                        <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                               class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:border-blue-500 outline-none bg-white">
                        <div class="text-[10px] text-gray-400 mt-1 ml-2">MM/DD/YYYY</div>
                    </div>
                    <div class="flex-1">
                        <label class="text-xs text-blue-500 font-medium ml-2">สนาม</label>
                        <select name="court_id" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm appearance-none bg-white">
                            <option value="">ทุกสนาม</option>
                            @foreach($courts as $court)
                                <option value="{{ $court->id }}" @selected($court_id == $court->id)>{{ $court->name }}</option>
                            @endforeach
                        </select>
                        <div class="text-[10px] mt-1 ml-2 invisible">x</div>
                    </div>
                </form>

                <!-- Bookings List -->
                <div>
                    <h3 class="font-medium text-sm mb-3">
                        @if($status === 'pending')
                            คำขอจอง (รอดำเนินการ)
                        @elseif($status === 'approved')
                            การจองที่อนุมัติแล้ว (ใช้งานอยู่)
                        @elseif($status === 'cancelled')
                            การจองที่ถูกยกเลิก/ปฏิเสธ
                        @else
                            รายการจองทั้งหมด
                        @endif
                        {{ $court_id ? ('สนาม ' . $court_id) : '' }}
                    </h3>
                    <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                        @forelse($bookings as $b)
                            @php $sInfo = $statusMap[$b->status] ?? $statusMap['pending']; @endphp
                            <a href="{{ request()->fullUrlWithQuery(['selected_booking_id' => $b->id]) }}" class="block bg-white border {{ $selectedBooking?->id === $b->id ? 'border-orange-500 shadow-md' : 'border-gray-200' }} rounded-xl p-4 hover:border-orange-300 transition">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex gap-2">
                                         <span class="text-xs px-2 py-0.5 border border-gray-300 rounded">{{ $b->court->name }}</span>
                                         <span class="text-xs px-2 py-0.5 {{ $sInfo['text'] }} {{ $sInfo['bg'] }} rounded flex items-center">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current mr-1"></span> {{ $sInfo['label'] }}
                                         </span>
                                    </div>
                                    @if($b->status === 'pending')
                                        <div class="flex gap-1">
                                            <form method="POST" action="{{ route('admin.bookings.reject', $b) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="reject_reason" value="Admin rejected from Dashboard">
                                                <button class="bg-red-500 text-white text-xs px-3 py-1 rounded">ปฏิเสธ</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.bookings.approve', $b) }}" class="inline">
                                                @csrf
                                                <button class="bg-green-500 text-white text-xs px-3 py-1 rounded">อนุมัติ</button>
                                            </form>
                                        </div>
                                    @else
                                        <!-- If not pending, it shows status like 'ยกเลิก' in red pill in Figma -->
                                        <div class="bg-{{ $sInfo['color'] }}-500 text-white text-xs px-3 py-1 rounded">
                                            {{ $sInfo['label'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 text-xs text-gray-600 mt-4 border-t border-gray-100 pt-3 gap-2">
                                     <div>
                                         <div class="text-gray-400 mb-1">วัน / เวลา : ที่จอง</div>
                                         <div class="font-medium text-gray-800">{{ $b->booking_date->translatedFormat('d F Y') }}</div>
                                     </div>
                                     <div class="text-right">
                                         <div class="text-gray-400 mb-1">&nbsp;</div> <!-- spacing -->
                                         <div class="font-medium text-gray-800">{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }}</div>
                                     </div>

                                     <div class="mt-2">
                                         <div class="text-gray-400 mb-1">Email / ชื่อผู้ใช้ :</div>
                                         <div class="font-medium text-gray-800 truncate">{{ $b->user->email }}</div>
                                     </div>
                                     <div class="mt-2 text-right">
                                         <div class="text-gray-400 mb-1">&nbsp;</div>
                                         <div class="font-medium text-gray-800">{{ $b->user->name }}</div>
                                     </div>

                                     <div class="col-span-2 mt-2">
                                         <div class="text-gray-400">วันที่ดำเนินการ : {{ $b->created_at->translatedFormat('d F Y') }} <span class="float-right">{{ $b->created_at->format('H:i:s') }}</span></div>
                                     </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-10 text-gray-400 bg-white rounded-xl border border-gray-200">ไม่มีรายการจอง</div>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        {{ $bookings->links() }}
                    </div>
                </div>
            </div>

            <!-- Column 2: Booking Details -->
            <div class="lg:col-span-5">
                @if($selectedBooking)
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 sticky top-4">
                    <!-- Title -->
                    <div class="flex items-center justify-between mb-5 border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            รายละเอียดการจอง
                        </h3>
                        <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-1 rounded font-bold tracking-wider">#{{ str_pad($selectedBooking->id, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    <!-- User Informaion (Secondary Data) -->
                    <div class="space-y-3 text-xs mb-5 px-1">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-medium">ผู้จอง</span>
                            <span class="text-gray-900 font-semibold">{{ $selectedBooking->user->name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-medium">อีเมล</span>
                            <span class="text-gray-800">{{ $selectedBooking->user->email }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-medium">ติดต่อ</span>
                            <span class="text-gray-800">{{ $selectedBooking->user->phone ?? '081-xxx-xxxx' }}</span>
                        </div>
                    </div>

                    <!-- Core Booking Data (Primary Data Highlight) -->
                    <div class="bg-orange-50/50 border border-orange-100 rounded-lg p-4 mb-6 space-y-3">
                         <div>
                             <div class="text-orange-600/80 font-medium mb-1 text-[11px] uppercase tracking-wide">สนามที่จอง</div>
                             <div class="text-orange-950 font-semibold text-base">{{ $selectedBooking->court->name }}</div>
                         </div>
                         <div class="h-px w-full bg-orange-100"></div>
                         <div>
                             <div class="text-orange-600/80 font-medium mb-1 text-[11px] uppercase tracking-wide">เวลาระบุ</div>
                             <div class="text-orange-950 font-semibold text-base">
                                 {{ substr($selectedBooking->start_time, 0, 5) }} - {{ substr($selectedBooking->end_time, 0, 5) }}
                             </div>
                             <div class="text-orange-800/80 mt-1 text-xs">
                                 {{ $selectedBooking->booking_date->translatedFormat('d M Y') }}
                             </div>
                         </div>
                    </div>

                    <!-- User Behavior Context -->
                    <h3 class="font-medium text-gray-700 text-xs mb-3 border-b border-gray-100 pb-2">ความน่าเชื่อถือของผู้ใช้</h3>
                    <div class="space-y-2.5 text-xs bg-gray-50 rounded-lg p-3 border border-gray-100">
                        @php
                            $userStats = [
                                'success' => \App\Models\Booking::where('user_id', $selectedBooking->user_id)->where('status', 'approved')->count(),
                                'self_cancel' => \App\Models\Booking::where('user_id', $selectedBooking->user_id)->where('status', 'cancelled')->count(),
                                'admin_reject' => \App\Models\Booking::where('user_id', $selectedBooking->user_id)->where('status', 'rejected')->count(),
                            ];
                        @endphp
                        <div class="flex justify-between items-center">
                            <div class="text-gray-600">ใช้งานสำเร็จ</div>
                            <div class="font-bold text-green-600">{{ $userStats['success'] }}</div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="text-gray-600">ยกเลิกรายการด้วยตัวเอง</div>
                            <div class="font-bold {{ $userStats['self_cancel'] > 2 ? 'text-orange-500' : 'text-gray-700' }}">{{ $userStats['self_cancel'] }}</div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="text-gray-600">ถูกปฏิเสธคำขอจอง</div>
                            <div class="font-bold {{ $userStats['admin_reject'] > 0 ? 'text-red-500' : 'text-gray-700' }}">{{ $userStats['admin_reject'] }}</div>
                        </div>
                    </div>

                    @if($selectedBooking->status === 'pending')
                    <div class="mt-6 flex gap-3">
                        <form method="POST" action="{{ route('admin.bookings.reject', $selectedBooking) }}" class="flex-1">
                           @csrf
                           <input type="hidden" name="reject_reason" value="Admin checked details">
                           <button class="w-full flex items-center justify-center gap-1 bg-white border-2 border-red-100 text-red-500 px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-50 hover:border-red-200 transition">
                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                               ปฏิเสธ
                           </button>
                        </form>
                        <form method="POST" action="{{ route('admin.bookings.approve', $selectedBooking) }}" class="flex-1">
                           @csrf
                           <button class="w-full flex items-center justify-center gap-1 bg-green-500 border-2 border-green-500 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-green-600 hover:border-green-600 transition shadow-sm shadow-green-200">
                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                               อนุมัติ
                           </button>
                        </form>
                    </div>
                    @endif
                </div>
                @else
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 text-center text-gray-400 text-sm italic">
                    คลิกที่รายการจองเพื่อดูรายละเอียด
                </div>
                @endif
            </div>

            <!-- Column 3: Stats sidebar -->
            <div class="lg:col-span-3">
                <div class="flex bg-white rounded-lg p-1 border border-gray-200 mb-6 text-xs font-medium text-center shadow-sm">
                   <a href="{{ request()->fullUrlWithQuery(['range' => 7]) }}" class="flex-1 rounded-md py-2 transition {{ (!isset($range) || $range == 7) ? 'bg-gray-100 text-gray-700' : 'text-gray-500 hover:bg-gray-50' }}">7 วันที่ผ่านมา</a>
                   <a href="{{ request()->fullUrlWithQuery(['range' => 30]) }}" class="flex-1 rounded-md py-2 transition {{ (isset($range) && $range == 30) ? 'bg-gray-100 text-gray-700' : 'text-gray-500 hover:bg-gray-50' }}">30 วันที่ผ่านมา</a>
                </div>

                <div class="space-y-4">
                    <!-- Total -->
                    <a href="{{ route('admin.bookings', ['date' => request('date'), 'court_id' => request('court_id')]) }}"
                       class="bg-white rounded-xl p-4 shadow-sm border {{ !$status ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }} flex justify-between items-center transition block">
                        <div>
                            <div class="text-xs text-gray-500 mb-1 font-medium">รายการทั้งหมด</div>
                            <div class="text-xl font-semibold text-gray-800">{{ $sideStats['total'] }}</div>
                        </div>
                        <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-5H3v5a2 2 0 002 2z"></path></svg>
                        </div>
                    </a>

                    <!-- Pending -->
                    <a href="{{ route('admin.bookings', ['status' => 'pending', 'date' => request('date'), 'court_id' => request('court_id')]) }}"
                       class="bg-white rounded-xl p-4 shadow-sm border {{ $status === 'pending' ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }} flex justify-between items-center transition block">
                        <div>
                            <div class="text-xs text-gray-500 mb-1 font-medium">การจองที่รออนุมัติ</div>
                            <div class="text-xl font-semibold text-gray-800">{{ $sideStats['pending'] }}</div>
                        </div>
                        <div class="w-10 h-10 bg-green-50 text-green-500 rounded-full flex items-center justify-center">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </a>

                    <!-- Approved -->
                    <a href="{{ route('admin.bookings', ['status' => 'approved', 'date' => request('date'), 'court_id' => request('court_id')]) }}"
                       class="bg-white rounded-xl p-4 shadow-sm border {{ $status === 'approved' ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }} flex justify-between items-center transition block">
                        <div>
                            <div class="text-xs text-gray-500 mb-1 font-medium">การจองที่อนุมัติ</div>
                            <div class="text-xl font-semibold text-gray-800">{{ $sideStats['approved'] }}</div>
                        </div>
                        <div class="w-10 h-10 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </a>

                    <!-- Cancelled/Rejected -->
                    <a href="{{ route('admin.bookings', ['status' => 'rejected', 'date' => request('date'), 'court_id' => request('court_id')]) }}"
                       class="bg-white rounded-xl p-4 shadow-sm border {{ $status === 'rejected' ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }} flex justify-between items-center transition block">
                        <div>
                            <div class="text-xs text-gray-500 mb-1 font-medium">การจองที่ยกเลิก</div>
                            <div class="text-xl font-semibold text-gray-800">{{ $sideStats['cancelled'] }}</div>
                        </div>
                        <div class="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </a>
                </div>

            </div>

        </div>
    </div>
</div>
@endsection
