@extends('layouts.app')

@section('title', 'จัดการเทรนเนอร์ส่วนตัว')

@php
    // เก็บ Config ข้อมูล Status ไว้รวมกันเพื่อให้แก้ไขจุดเดียว
    $statusMap = [
        'pending' => ['label' => 'รออนุมัติ', 'bg' => 'bg-orange-100', 'text' => 'text-orange-500', 'pill' => 'bg-orange-500'],
        'approved' => ['label' => 'อนุมัติแล้ว', 'bg' => 'bg-green-100', 'text' => 'text-green-500', 'pill' => 'bg-green-500'],
        'rejected' => ['label' => 'ปฏิเสธแล้ว', 'bg' => 'bg-red-100', 'text' => 'text-red-500', 'pill' => 'bg-red-500'],
        'cancelled' => ['label' => 'ยกเลิก', 'bg' => 'bg-red-100', 'text' => 'text-red-500', 'pill' => 'bg-red-500'],
    ];

    // ลดความซ้ำซ้อนของ Array สำหรับวนลูปแสดง Tabs ด้านบน
    $tabs = [
        'pending' => 'รออนุมัติ',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ปฏิเสธแล้ว',
        'cancelled' => 'ยกเลิก',
        'all' => 'ทั้งหมด',
    ];
@endphp

@section('content')
    <div class="bg-slate-50 text-gray-900 min-h-screen py-8">
        <div class="container mx-auto px-6 max-w-6xl">

            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">จัดการเทรนเนอร์ส่วนตัว</h1>
                <p class="text-sm text-gray-500 mt-1">ตรวจสอบและอนุมัติคำขอจองเทรนเนอร์ส่วนตัวของลูกค้า</p>
            </div>

            {{-- ส่วนแสดง Tabs สถานะ --}}
            <div class="flex gap-2 -mb-px overflow-x-auto select-none border-b border-gray-200 mb-6">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('admin.private-training.index', ['status' => $key]) }}"
                        class="px-5 py-2.5 text-sm font-medium border-b-2 transition-all cursor-pointer {{ $status === $key ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="divide-y divide-gray-100">
                    @forelse($bookings as $b)
                        @php $sInfo = $statusMap[$b->status] ?? $statusMap['pending']; @endphp
                        <div class="p-5">
                            <div class="flex justify-between items-start flex-wrap gap-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs px-2 py-0.5 border border-gray-300 rounded">โค้ช
                                            {{ $b->coach->name }}</span>
                                        <span
                                            class="text-xs px-2 py-0.5 {{ $sInfo['text'] }} {{ $sInfo['bg'] }} rounded flex items-center">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current mr-1"></span>
                                            {{ $sInfo['label'] }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-800">ลูกค้า: {{ $b->user->name }}
                                        ({{ $b->user->email }})</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{-- ใช้ประโยชน์จาก Model Casts ($b->date เป็น Carbon Object อัตโนมัติแล้ว จึงเรียก
                                        format() ได้เลย) --}}
                                        วันที่ {{ $b->date->format('d/m/Y') }}
                                        &nbsp;•&nbsp; เวลา {{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }}
                                        น.
                                    </p>
                                    @if($b->note)
                                        <p class="text-xs text-gray-500 mt-1">หมายเหตุ: {{ $b->note }}</p>
                                    @endif
                                    @if($b->reject_reason)
                                        <p class="text-xs text-red-500 mt-1">เหตุผลที่ปฏิเสธ: {{ $b->reject_reason }}</p>
                                    @endif
                                </div>

                                @if($b->status === 'pending')
                                    <div class="flex gap-2">
                                        <button type="button"
                                            onclick="openRejectModal('{{ route('admin.private-training.reject', $b) }}')"
                                            class="bg-red-500 text-white text-xs px-3 py-1.5 rounded cursor-pointer transition duration-200 hover:scale-105 hover:bg-red-600">ปฏิเสธ</button>

                                        <form method="POST" action="{{ route('admin.private-training.approve', $b) }}">
                                            @csrf
                                            <button type="submit"
                                                class="bg-green-500 text-white text-xs px-3 py-1.5 rounded cursor-pointer transition duration-200 hover:scale-105 hover:bg-green-600">อนุมัติ</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-16 text-center">
                            <p class="text-gray-400 font-medium text-sm">ไม่พบคำขอจองเทรนเนอร์ส่วนตัวในหมวดหมู่นี้</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination จะแสดงก็ต่อเมื่อมีหลายหน้าเท่านั้น ($bookings->hasPages()) --}}
                @if($bookings->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 bg-slate-50">
                        {{ $bookings->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal เหตุผลการปฏิเสธ --}}
    <div id="rejectModal"
        class="fixed inset-0 z-[60] hidden bg-gray-900/60 backdrop-blur-sm items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">ระบุเหตุผลที่ปฏิเสธ</h3>
            </div>
            <form id="rejectForm" method="POST" class="p-6 space-y-4">
                @csrf
                <textarea name="reject_reason" rows="3" required placeholder="เช่น โค้ชไม่ว่างในช่วงเวลานี้"
                    class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-gray-900 bg-white resize-none"></textarea>
                <div class="flex gap-2">
                    <button type="button" onclick="closeRejectModal()"
                        class="w-1/2 px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium">ยกเลิก</button>
                    <button type="submit"
                        class="w-1/2 px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg transition font-medium shadow-sm">ยืนยันปฏิเสธ</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        /**
         * ฟังก์ชันเปิด Modal พร้อมแนบ URL สำหรับกดยืนยันการปฏิเสธ
         * @param {string} actionUrl - URL ของ Route สำหรับปฏิเสธรายการที่ถูกคลิก
         */
        function openRejectModal(actionUrl) {
            // setAttribute: เป็นการเปลี่ยนเป้าหมายการส่งฟอร์ม (action) แบบไดนามิก 
            // ทำให้เราใช้ฟอร์มเดียวสำหรับทุกๆ รายการได้ ไม่ต้องสร้างฟอร์มซ้ำในลูป
            const form = document.getElementById('rejectForm');
            form.setAttribute('action', actionUrl);

            // แสดง Modal โดยการลบคลาส hidden (ที่ซ่อนไว้) และเพิ่ม flex (เพื่อจัดกึ่งกลาง)
            const modal = document.getElementById('rejectModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        /**
         * ฟังก์ชันปิด Modal (แยกออกมาให้เรียกใช้ง่ายและโค้ดสะอาดขึ้น)
         */
        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        /**
         * Swal.mixin: เป็นการสร้าง Template หรือ Instance ของ SweetAlert2 ที่มี Config เริ่มต้น 
         * ทำให้เราเรียกใช้คำสั่งสั้นๆ เช่น Toast.fire() ได้เลย ไม่ต้องเซ็ตค่าใหม่ซ้ำๆ
         */
        const Toast = Swal.mixin({
            toast: true,               // ให้แสดงในรูปแบบ Popup เล็กๆ (Toast) แทนที่จะบังเต็มจอ
            position: 'top-end',       // ตำแหน่งที่โผล่ (มุมขวาบน)
            showConfirmButton: false,  // ซ่อนปุ่ม "ตกลง" 
            timer: 3000,               // หน่วงเวลาให้หายไปเอง 3 วินาที (3000 ms)
            timerProgressBar: true,    // แสดงแถบเวลาโหลดลดลงด้านล่างของ Popup
        });

        document.addEventListener('DOMContentLoaded', function () {
            // @js() เป็น Blade Directive ของ Laravel ใช้แปลงตัวแปร PHP ให้เป็น Data Type ของ JavaScript อัตโนมัติ
            // ข้อดีคือ ปลอดภัยจาก XSS Attack (Cross-Site Scripting) และจัดการเรื่องเครื่องหมายคำพูด (" หรือ ') ให้อัตโนมัติ
            @if (session()->has('success'))
                Toast.fire({ icon: 'success', title: @js(session('success')) });
            @endif

            @if ($errors->any())
                Toast.fire({ icon: 'error', title: @js($errors->first()) });
            @endif
        });
    </script>
@endsection