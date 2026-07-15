@php
    // เช็คว่าสถานะปัจจุบันคืออะไร ถ้าไม่มี (เข้ามาหน้าแรก) ให้ถือว่าเป็น pending
    $currentStatus = $status ?: 'pending';
@endphp

<!-- resources/views/admin/partials/summary_cards.blade.php -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-8">
    @php $isPending = $currentStatus === 'pending'; @endphp
    <!-- ทั้งหมด -->
    <!-- <a href="{{ route('admin.bookings', ['date' => now()->toDateString()]) }}" class="bg-white rounded-2xl p-6 shadow-sm flex items-center justify-between border border-gray-100 hover:shadow-md hover:border-blue-200 transition group cursor-pointer">
        <div>
            <div class="text-sm text-gray-500 font-medium mb-1 group-hover:text-blue-500 transition">รายการทั้งหมดในวันนี้</div>
            <div class="text-3xl font-bold text-gray-900 group-hover:text-blue-600 transition">{{ $stats['today_total'] ?? 0 }}</div>
        </div>
        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-5H3v5a2 2 0 002 2z"></path>
            </svg>
        </div>
    </a> -->

    <a href="{{ route('admin.bookings', ['status' => 'pending']) }}" 
        class="bg-white rounded-2xl p-6 flex items-center justify-between border transition group cursor-pointer
        {{ $isPending ? 'border-orange-500 ring-1 ring-orange-500 shadow-md' : 'border-gray-100 shadow-sm hover:shadow-md hover:border-orange-200' }}">
        <div>
            <div class="text-sm font-medium mb-1 transition {{ $isPending ? 'text-orange-500' : 'text-gray-500 group-hover:text-orange-500' }}">
                คำขอการจองทั้งหมด
            </div>
            <div class="text-3xl font-bold transition {{ $isPending ? 'text-orange-600' : 'text-gray-900 group-hover:text-orange-600' }}">
                {{ $stats['today_pending'] ?? 0 }}
            </div>
        </div>
        <div class="w-12 h-12 rounded-full flex items-center justify-center transition 
            {{ $isPending ? 'bg-orange-500 text-white' : 'bg-orange-50 text-orange-500 group-hover:bg-orange-500 group-hover:text-white' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </a>

    @php $isApproved = $currentStatus === 'approved'; @endphp
    <a href="{{ route('admin.bookings', ['status' => 'approved']) }}" 
        class="bg-white rounded-2xl p-6 flex items-center justify-between border transition group cursor-pointer
        {{ $isApproved ? 'border-green-500 ring-1 ring-green-500 shadow-md' : 'border-gray-100 shadow-sm hover:shadow-md hover:border-green-200' }}">
        <div>
            <div class="text-sm font-medium mb-1 transition {{ $isApproved ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}">
                การจองที่อนุมัติทั้งหมด
            </div>
            <div class="text-3xl font-bold transition {{ $isApproved ? 'text-green-600' : 'text-gray-900 group-hover:text-green-600' }}">
                {{ $stats['today_approved'] ?? 0 }}
            </div>
        </div>
        <div class="w-12 h-12 rounded-full flex items-center justify-center transition 
            {{ $isApproved ? 'bg-green-500 text-white' : 'bg-green-50 text-green-500 group-hover:bg-green-500 group-hover:text-white' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </a>

    @php $isRejected = $currentStatus === 'rejected'; @endphp
    <a href="{{ route('admin.bookings', ['status' => 'rejected']) }}" 
        class="bg-white rounded-2xl p-6 flex items-center justify-between border transition group cursor-pointer
        {{ $isRejected ? 'border-red-500 ring-1 ring-red-500 shadow-md' : 'border-gray-100 shadow-sm hover:shadow-md hover:border-red-200' }}">
        <div>
            <div class="text-sm font-medium mb-1 transition {{ $isRejected ? 'text-red-500' : 'text-gray-500 group-hover:text-red-500' }}">
                การจองที่ถูกปฏิเสธทั้งหมด
            </div>
            <div class="text-3xl font-bold transition {{ $isRejected ? 'text-red-600' : 'text-gray-900 group-hover:text-red-600' }}">
                {{ $stats['today_rejected'] ?? 0 }}
            </div>
        </div>
        <div class="w-12 h-12 rounded-full flex items-center justify-center transition 
            {{ $isRejected ? 'bg-red-500 text-white' : 'bg-red-50 text-red-500 group-hover:bg-red-500 group-hover:text-white' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </a>
</div>
