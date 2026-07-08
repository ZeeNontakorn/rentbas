<!-- resources/views/admin/partials/summary_cards.blade.php -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
    <!-- ทั้งหมด -->
    <a href="{{ route('admin.bookings', ['date' => now()->toDateString()]) }}" class="bg-white rounded-2xl p-6 shadow-sm flex items-center justify-between border border-gray-100 hover:shadow-md hover:border-blue-200 transition group cursor-pointer">
        <div>
            <div class="text-sm text-gray-500 font-medium mb-1 group-hover:text-blue-500 transition">รายการทั้งหมดในวันนี้</div>
            <div class="text-3xl font-bold text-gray-900 group-hover:text-blue-600 transition">{{ $stats['today_total'] ?? 0 }}</div>
        </div>
        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-5H3v5a2 2 0 002 2z"></path>
            </svg>
        </div>
    </a>

    <!-- รออนุมัติ -->
    <a href="{{ route('admin.bookings', ['date' => now()->toDateString(), 'status' => 'pending']) }}" class="bg-white rounded-2xl p-6 shadow-sm flex items-center justify-between border border-gray-100 hover:shadow-md hover:border-green-200 transition group cursor-pointer">
        <div>
            <div class="text-sm text-gray-500 font-medium mb-1 group-hover:text-green-500 transition">คำขอการจองในวันนี้</div>
            <div class="text-3xl font-bold text-gray-900 group-hover:text-green-600 transition">{{ $stats['today_pending'] ?? 0 }}</div>
        </div>
        <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center group-hover:bg-green-500 group-hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </a>

    <!-- อนุมัติ -->
    <a href="{{ route('admin.bookings', ['date' => now()->toDateString(), 'status' => 'approved']) }}" class="bg-white rounded-2xl p-6 shadow-sm flex items-center justify-between border border-gray-100 hover:shadow-md hover:border-orange-200 transition group cursor-pointer">
        <div>
            <div class="text-sm text-gray-500 font-medium mb-1 group-hover:text-orange-500 transition">การจองที่อนุมัติในวันนี้</div>
            <div class="text-3xl font-bold text-gray-900 group-hover:text-orange-600 transition">{{ $stats['today_approved'] ?? 0 }}</div>
        </div>
        <div class="w-12 h-12 bg-green-50 text-green-500 rounded-full flex items-center justify-center group-hover:bg-orange-500 group-hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </a>

    <!-- ยกเลิก -->
    <a href="{{ route('admin.bookings', ['date' => now()->toDateString(), 'status' => 'rejected']) }}" class="bg-white rounded-2xl p-6 shadow-sm flex items-center justify-between border border-gray-100 hover:shadow-md hover:border-red-200 transition group cursor-pointer">
        <div>
            <div class="text-sm text-gray-500 font-medium mb-1 group-hover:text-red-500 transition">การจองที่ถูกปฏิเสธในวันนี้</div>
            <div class="text-3xl font-bold text-gray-900 group-hover:text-red-600 transition">{{ $stats['today_rejected'] ?? 0 }}</div>
        </div>
        <div class="w-12 h-12 bg-red-50 text-red-500 rounded-full flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </a>
</div>
