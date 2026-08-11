<!-- resources/views/components/navbar.blade.php -->
@auth
    @php
        $activeCheckout = \App\Models\Booking::where('user_id', auth()->id())
            ->where('status', 'pending_payment')
            ->where('locked_until', '>', now())
            ->latest('locked_until')
            ->first();
    @endphp
@endauth
<style>
    .notif-scroll {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .notif-scroll::-webkit-scrollbar {
        display: none;
    }
</style>
<nav class="sticky top-0 z-50 bg-gray-900 shadow-md text-white">
    <div class="container mx-auto flex justify-between items-center py-4 px-4 md:px-10">
        <!-- Logo / ชื่อระบบ -->
        <a href="{{ route('home') }}" class="flex items-center font-bold text-xl md:text-2xl hover:text-gray-300 transition flex-shrink-0">
            <!-- System Name -->
            <span class="font-bold text-lg md:text-2xl leading-tight">THATA HOMECOURT</span>
        </a>

        <div class="flex items-center gap-3 xl:hidden ml-auto mr-3">
            @auth
                @if(auth()->user()->role === 'admin')
                    <button type="button" class="border border-gray-500 text-gray-300 px-3 py-1 rounded-full text-xs font-medium hover:border-orange-500 hover:text-orange-500 transition flex items-center"
                        onclick="window.location.href='{{ route('admin.credits.show', auth()->user()) }}'">
                        {{ number_format(auth()->user()->credit_balance / 100, 2) }} <span class="ml-1 text-[10px]">฿</span>
                    </button>
                @else
                    <button type="button" class="border border-gray-500 text-gray-300 px-3 py-1 rounded-full text-xs font-medium hover:border-orange-500 hover:text-orange-500 transition flex items-center"
                        onclick="window.location.href='{{ route('credits.topup.index') }}'">
                        {{ number_format(auth()->user()->credit_balance / 100, 2) }} <span class="ml-1 text-[10px]">฿</span>
                    </button>
                @endif
            @endauth
        </div>

        <!-- ปุ่มเปิด/ปิดเมนู สำหรับจอมือถือ/แท็บเล็ต -->
        <button id="mobileMenuBtn" class="xl:hidden flex items-center focus:outline-none hover:text-orange-500 transition" aria-label="เปิดเมนู">
            <svg id="mobileMenuIconOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg id="mobileMenuIconClose" class="w-7 h-7 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- เมนูหลัก (Desktop / จอกว้าง xl ขึ้นไป) -->
        <div class="hidden xl:flex flex-1 min-w-0 flex-nowrap justify-end items-center gap-3 2xl:gap-6 whitespace-nowrap overflow-visible">
            @auth
                @if($activeCheckout ?? false)
                    <a href="{{ route('checkout.show', $activeCheckout) }}" class="flex items-center gap-1 rounded-full bg-orange-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-orange-600 transition flex-shrink-0">
                        กลับไปชำระเงิน
                        <span class="inline-block h-2 w-2 rounded-full bg-white animate-pulse"></span>
                    </a>
                @endif
                @php
                    $user = auth()->user();
                    $isAdminLike = in_array($user->role, ['admin', 'superadmin'], true);
                    $canManageBookings = $isAdminLike || ($user->role === 'staff' && in_array($user->membership_type, ['permanent', 'temporary', 'intern'], true));
                @endphp
                @if($isAdminLike)
                    <!-- เมนูหลักสำหรับ Admin () -->
                     <a href="{{ route('home') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition {{ request()->routeIs('home') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        หน้าแรก
                    </a>
                    <!-- จัดการสนาม -->
                    <div class="relative flex-shrink-0" data-admin-nav-dropdown>
                        <button type="button" class="cursor-pointer admin-nav-dropdown-btn flex items-center gap-1 text-sm whitespace-nowrap hover:text-orange-500 transition focus:outline-none {{ request()->routeIs('admin.courts', 'admin.pricing.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}" aria-expanded="false">
                            จัดการสนาม
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="admin-nav-dropdown hidden absolute left-0 mt-3 w-48 overflow-hidden rounded-xl border border-gray-700 bg-gray-800 text-sm text-gray-100 shadow-lg z-50">
                            <a href="{{ route('admin.courts') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.courts') ? 'text-orange-500 font-bold' : '' }}">จัดการสนาม</a>
                            <a href="{{ route('admin.pricing.index') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.pricing.*') ? 'text-orange-500 font-bold' : '' }}">ตั้งราคา</a>
                        </div>
                    </div>

                    <!-- การสอน -->
                    <div class="relative flex-shrink-0" data-admin-nav-dropdown>
                        <button type="button" class="cursor-pointer admin-nav-dropdown-btn flex items-center gap-1 text-sm whitespace-nowrap hover:text-orange-500 transition focus:outline-none {{ request()->routeIs('admin.private-training.*', 'admin.private-schedule.*', 'admin.courses') ? 'text-orange-500 font-bold' : 'text-gray-300' }}" aria-expanded="false">
                            การสอน
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="admin-nav-dropdown hidden absolute left-0 mt-3 w-56 overflow-hidden rounded-xl border border-gray-700 bg-gray-800 text-sm text-gray-100 shadow-lg z-50">
                            <a href="{{ route('admin.private-training.index') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.private-training.*') ? 'text-orange-500 font-bold' : '' }}">จัดการ Private Training</a>
                            <a href="{{ route('admin.private-schedule.index') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.private-schedule.*') ? 'text-orange-500 font-bold' : '' }}">Schedule บุคลากร</a>
                            <a href="{{ route('admin.courses') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.courses') ? 'text-orange-500 font-bold' : '' }}">จัดการคอร์สเรียน</a>
                             <a href="{{ route('admin.packages.index') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.packages.*') ? 'text-orange-500 font-bold' : '' }}">จัดการแพ็กเกจ</a>
                        </div>
                    </div>

                    <!-- รายงานและภาพรวม -->
                    <div class="relative flex-shrink-0" data-admin-nav-dropdown>
                        <button type="button" class="cursor-pointer admin-nav-dropdown-btn flex items-center gap-1 text-sm whitespace-nowrap hover:text-orange-500 transition focus:outline-none {{ request()->routeIs('history', 'admin.dashboard') ? 'text-orange-500 font-bold' : 'text-gray-300' }}" aria-expanded="false">
                            รายงานและภาพรวม
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="admin-nav-dropdown hidden absolute left-0 mt-3 w-52 overflow-hidden rounded-xl border border-gray-700 bg-gray-800 text-sm text-gray-100 shadow-lg z-50">
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.dashboard') ? 'text-orange-500 font-bold' : '' }}">Dashboard</a>
                            <a href="{{ route('history') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('history') ? 'text-orange-500 font-bold' : '' }}">ดูประวัติการจอง</a>
                        </div>
                    </div>
                    <!-- เติมเครดิต -->
                     <div class="relative flex-shrink-0" data-admin-nav-dropdown>
                        <button type="button" class="cursor-pointer admin-nav-dropdown-btn flex items-center gap-1 text-sm whitespace-nowrap hover:text-orange-500 transition focus:outline-none {{ request()->routeIs('admin.credit-topup-packages.index', 'admin.credit-topups.index') ? 'text-orange-500 font-bold' : 'text-gray-300' }}" aria-expanded="false">
                        เติมเครดิต
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="admin-nav-dropdown hidden absolute left-0 mt-3 w-52 overflow-hidden rounded-xl border border-gray-700 bg-gray-800 text-sm text-gray-100 shadow-lg z-50">
                         <a href="{{ route('admin.credit-topup-packages.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center">แพ็กเกจเครดิต</a>
                        <a href="{{ route('admin.credit-topups.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center">คำขอเติมเครดิต</a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('home') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition flex-shrink-0 {{ request()->routeIs('home') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        หน้าแรก
                    </a>

                    <a href="{{ route('booking.index') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition flex-shrink-0 {{ request()->routeIs('booking.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        จองสนาม
                    </a>

                    <!-- เทรนเนอร์ส่วนตัว สำหรับ User -->
                    @if($user->role === 'staff' && in_array($user->membership_type, ['coach', 'court_assistant'], true))
                        <a href="{{ route('private-training.my-schedule') }}" class="flex items-center hover:text-orange-500 transition flex-shrink-0 {{ request()->routeIs('private-training.my-schedule') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            ตารางงาน
                        </a>
                    @elseif($user->role === 'staff' && in_array($user->membership_type, ['permanent', 'temporary', 'intern'], true))
                        <a href="{{ route('admin.private-training.index') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition flex-shrink-0 {{ request()->routeIs('admin.private-training.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            เทรนเนอร์ส่วนตัว
                        </a>
                    @else
                        <a href="{{ route('private-training.index') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition flex-shrink-0 {{ request()->routeIs('private-training.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            เทรนเนอร์ส่วนตัว
                        </a>
                    @endif

                    <a href="{{ route('history') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition flex-shrink-0 {{ request()->routeIs('history') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        ประวัติการจอง
                    </a>

                    {{-- @if($canManageBookings && auth()->user()->role === 'staff')
                        <!-- จัดการการจอง สำหรับ Staff -->
                        <a href="{{ route('admin.bookings') }}" class="flex items-center hover:text-orange-500 transition flex-shrink-0 {{ request()->routeIs('admin.bookings') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            จัดการการจอง
                        </a>
                    @endif --}}
                @endif

                <!-- Notification -->
                @php
                    $user = auth()->user(); // ดึงข้อมูลผู้ใช้ที่ Login อยู่
                    $unreadCount = $user->unreadNotifications()->count(); // นับจำนวนแจ้งเตือนที่ยังไม่ได้อ่าน
                    $notifications = $user->unreadNotifications()->latest()->take(200)->get(); // ดึงเฉพาะที่ยังไม่ได้อ่าน ล่าสุด 10 รายการ
                @endphp
                {{-- ปุ่มแจ้งเตือน --}}
                <div class="relative flex-shrink-0">
                    {{-- ไอคอนกระดิ่ง --}}
                    <button id="notifBtn" class="relative focus:outline-none hover:text-gray-300 transition cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span id="notifBadge" class="absolute -top-1 -right-2 bg-red-500 text-white text-xs px-1 rounded-full {{ $unreadCount ? '' : 'hidden' }}">
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                    </button>

                    {{-- Dropdown สำหรับแสดงรายการแจ้งเตือน --}}
                    <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-[26rem] max-w-[calc(100vw-2rem)] bg-gray-800 text-gray-100 rounded-2xl border border-gray-700/80 shadow-2xl overflow-hidden z-50">
                        <div id="notifDropdownHeader" class="flex justify-between items-center px-4 py-3 border-b border-gray-700 bg-gray-900/70 z-10 {{ $notifications->isEmpty() ? 'hidden' : '' }}">
                            <span class="flex items-center gap-2 text-sm text-gray-200 font-semibold">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-orange-500/15 text-orange-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h11zm0 0v1a3 3 0 11-6 0v-1"></path></svg>
                                </span>
                                แจ้งเตือนล่าสุด
                            </span>
                            <form class="mark-all-read-form" method="POST" action="{{ route('notifications.readAll') }}">
                                @csrf
                                <button type="submit" class="text-[11px] text-orange-400 hover:text-orange-300 font-medium cursor-pointer">อ่านแล้วทั้งหมด</button>
                            </form>
                        </div>

                        <div id="notifEmptyMsg" class="p-4 text-center text-gray-400 {{ $notifications->isEmpty() ? '' : 'hidden' }}">ไม่มีการแจ้งเตือนใหม่</div>

                        <div id="notifItemsWrap" class="notif-scroll max-h-[22rem] overflow-y-auto overflow-x-hidden">
                            @foreach($notifications as $n)
                            {{-- แต่ละรายการแจ้งเตือน: กดที่การ์ดเพื่อไปดูรายละเอียด --}}
                                @php
                                    $isCourtBookingNotification = str_starts_with($n->title ?? '', 'คำขอจองใหม่')
                                        || ($n->title ?? '') === 'มีการจองสนามบาสใหม่';
                                    $notifTarget = $isCourtBookingNotification ? null : route('notifications.open', $n);

                                    // สีและไอคอนตามความหมาย: สำเร็จ / ปฏิเสธ / รอดำเนินการ / ข้อมูลทั่วไป
                                    $visualType = $n->visualType();
                                    $visual = match ($visualType) {
                                        'success' => [
                                            'border' => 'border-l-4 border-l-emerald-500',
                                            'surface' => 'bg-gradient-to-r from-emerald-500/10 to-transparent hover:from-emerald-500/15',
                                            'title' => 'text-emerald-300',
                                            'accent' => 'text-emerald-400',
                                            'iconBg' => 'bg-emerald-500/15 ring-emerald-500/25',
                                            'iconColor' => 'text-emerald-400',
                                            'path' => 'M5 13l4 4L19 7',
                                        ],
                                        'danger' => [
                                            'border' => 'border-l-4 border-l-rose-500',
                                            'surface' => 'bg-gradient-to-r from-rose-500/10 to-transparent hover:from-rose-500/15',
                                            'title' => 'text-rose-300',
                                            'accent' => 'text-rose-400',
                                            'iconBg' => 'bg-rose-500/15 ring-rose-500/25',
                                            'iconColor' => 'text-rose-400',
                                            'path' => 'M6 18L18 6M6 6l12 12',
                                        ],
                                        'warning' => [
                                            'border' => 'border-l-4 border-l-amber-500',
                                            'surface' => 'bg-gradient-to-r from-amber-500/10 to-transparent hover:from-amber-500/15',
                                            'title' => 'text-amber-300',
                                            'accent' => 'text-amber-400',
                                            'iconBg' => 'bg-amber-500/15 ring-amber-500/25',
                                            'iconColor' => 'text-amber-400',
                                            'path' => 'M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
                                        ],
                                        default => [
                                            'border' => 'border-l-4 border-l-sky-500',
                                            'surface' => 'bg-gradient-to-r from-sky-500/10 to-transparent hover:from-sky-500/15',
                                            'title' => 'text-sky-300',
                                            'accent' => 'text-sky-400',
                                            'iconBg' => 'bg-sky-500/15 ring-sky-500/25',
                                            'iconColor' => 'text-sky-400',
                                            'path' => 'M12 8h.01M11 12h1v4h1m8-4a9 9 0 11-18 0 9 9 0 0118 0z',
                                        ],
                                    };
                                @endphp
                                <div class="notif-item w-full p-4 border-b border-gray-700/80 {{ $visual['border'] }} {{ $visual['surface'] }} flex items-start gap-3 cursor-pointer transition-colors"
                                    data-notif-id="{{ $n->id }}"     
                                    onclick="window.location.href='{{ $notifTarget }}'">
                                    <span class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full ring-1 {{ $visual['iconBg'] }} {{ $visual['iconColor'] }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $visual['path'] }}"></path></svg>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex min-w-0 items-start gap-2">
                                            <div class="min-w-0 font-semibold text-[15px] leading-snug whitespace-normal break-words {{ $visual['title'] }}" style="overflow-wrap: anywhere;">{{ $n->title ?? 'การจอง' }}</div>
                                        </div>
                                        <div class="min-w-0 text-[13px] text-gray-300 mt-1 leading-relaxed whitespace-normal break-words" style="overflow-wrap: anywhere;">
                                            @php
                                            // แยกข้อความโดยใช้ '|' เป็นตัวแบ่ง
                                                $msgParts = explode('|', $n->message ?? ($n->data['message'] ?? ''));
                                            @endphp

                                            {{-- แสดงส่วนแรกของข้อความ (หลัก) --}}
                                            {{ $msgParts[0] }}

                                            {{-- แสดงส่วนที่สองของข้อความ (ถ้ามี) — แปลง \n เป็นขึ้นบรรทัดใหม่จริง --}}
                                            @if(isset($msgParts[1]) && trim($msgParts[1]) !== '')
                                                <div class="mt-1 font-medium whitespace-pre-line break-words {{ $visual['accent'] }}" style="overflow-wrap: anywhere;">{{ trim($msgParts[1]) }}</div>
                                            @endif
                                        </div>
                                        {{-- แสดงวันที่สร้างแจ้งเตือน --}}
                                        <div class="text-[11px] text-gray-400 mt-2">{{ $n->created_at->format('d M Y H:i') }}</div>
                                    </div>
                                    {{-- ปุ่มสำหรับทำเครื่องหมายว่าอ่านแล้ว (ถ้ายังไม่ได้อ่าน) — กันไม่ให้คลิกทะลุไปเปิดหน้าปลายทางด้วย --}}
                                    <form class="mark-read-form flex-shrink-0" method="POST" action="{{ route('notifications.read', $n) }}" onclick="event.stopPropagation()">
                                        @csrf
                                        <button type="submit" class="text-[11px] bg-gray-700/80 hover:bg-gray-600 text-gray-200 border border-gray-600 px-2.5 py-1 rounded-full transition whitespace-nowrap cursor-pointer">อ่านแล้ว</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Divider (ใช้ร่วมกันทุก role: name dropdown pattern) -->
                <div class="h-6 w-px bg-gray-600 mx-1 2xl:mx-2 flex-shrink-0"></div>
                <div class="relative flex-shrink-0">
                    <button id="adminMenuBtn" class="flex items-center hover:text-orange-500 transition text-gray-300 focus:outline-none whitespace-nowrap cursor-pointer">
                    <span class="block max-w-[10rem] truncate" title="{{ auth()->user()->name }}">
                        {{ auth()->user()->name }}
                    </span>
                    <svg class="w-4 h-4 ml-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    </button>
                    <div id="adminMenuDropdown" class="hidden absolute right-0 mt-4 w-56 bg-gray-800 text-gray-100 rounded-xl shadow-lg z-50 border border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-700 bg-gray-900/50">
                            <div class="text-xs text-gray-400">เข้าสู่ระบบในฐานะ</div>
                            <div class="font-bold truncate text-orange-500" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                        </div>
                        @if($isAdminLike)
                            <a href="{{ route('admin.users.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                จัดการผู้ใช้งาน
                            </a>
                            <a href="{{ route('admin.staffs.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"></path></svg>
                                โค้ช และผู้ช่วย
                            </a>
                            <a href="{{ route('admin.edit.text') }}" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                แก้ไขเนื้อหาเว็บไซต์
                            </a>
                        @endif
                        <a href="{{ route('profile') }}" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center border-t border-gray-700">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            ตั้งค่าโปรไฟล์
                        </a>
                        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center border-t border-gray-700">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            ออกจากระบบ
                        </a>
                    </div>
                </div>

                <!-- Logout -->
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>

                {{-- credit --}}
                @if($isAdminLike)
                    <button type="button" class="border border-gray-500 text-gray-300 px-4 py-1.5 rounded-full text-sm hover:border-orange-500 hover:text-orange-500 transition flex items-center flex-shrink-0 whitespace-nowrap cursor-pointer"
                        onclick="window.location.href='{{ route('admin.credits.show', auth()->user()) }}'">
                        {{ number_format(auth()->user()->credit_balance / 100, 2) }} <span class="ml-1">฿</span>
                    </button>
                @else
                    <button type="button" class="border border-gray-500 text-gray-300 px-4 py-1.5 rounded-full text-sm hover:border-orange-500 hover:text-orange-500 transition flex items-center flex-shrink-0 whitespace-nowrap cursor-pointer"
                        onclick="window.location.href='{{ route('credits.topup.index') }}'">
                        {{ number_format(auth()->user()->credit_balance / 100, 2) }} <span class="ml-1">฿</span>
                    </button>
                @endif
            @endauth

            @guest
               <a href="{{ route('login') }}" class="flex items-center hover:text-gray-300 transition">
    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
    </svg>
    เข้าสู่ระบบ
</a>
<a href="{{ route('register') }}" class="flex items-center hover:text-gray-300 transition">
    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
    </svg>
    สมัครสมาชิก
</a>
            @endguest
        </div>
    </div>

    <!-- เมนู (Mobile/Tablet, แบบเลื่อนลง) -->
    <div id="mobileMenu" class="hidden xl:hidden border-t border-gray-800 bg-gray-900 px-4 pb-4">
        @auth
            @if($activeCheckout ?? false)
                <a href="{{ route('checkout.show', $activeCheckout) }}" class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 transition">
                    กลับไปชำระเงิน
                    <span class="inline-block h-2 w-2 rounded-full bg-white animate-pulse"></span>
                </a>
            @endif
            @php
                $user = auth()->user();
                $isAdminLike = in_array($user->role, ['admin', 'superadmin'], true);
                $canManageBookings = $isAdminLike || ($user->role === 'staff' && in_array($user->membership_type, ['permanent', 'temporary', 'intern'], true));
            @endphp
            @if($isAdminLike)
                <div class="flex flex-col py-2">
                    <a href="{{ route('home') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('home') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        หน้าแรก
                    </a>
                    <div class="border-t border-gray-800" data-mobile-nav-dropdown>
                        <button type="button" class="mobile-nav-dropdown-btn flex w-full items-center justify-between py-2 text-left text-sm text-gray-300 hover:text-orange-500 transition" aria-expanded="{{ request()->routeIs('admin.courts', 'admin.pricing.*') ? 'true' : 'false' }}">
                            จัดการสนาม
                            <svg class="h-5 w-5 transition-transform {{ request()->routeIs('admin.courts', 'admin.pricing.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="mobile-nav-dropdown pl-3 {{ request()->routeIs('admin.courts', 'admin.pricing.*') ? '' : 'hidden' }}">
                            <a href="{{ route('admin.courts') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.courts') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">จัดการสนาม</a>
                            <a href="{{ route('admin.pricing.index') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.pricing.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">ตั้งราคา</a>
                        </div>
                    </div>
                    <div class="border-t border-gray-800" data-mobile-nav-dropdown>
                        <button type="button" class="mobile-nav-dropdown-btn flex w-full items-center justify-between py-2 text-left text-sm text-gray-300 hover:text-orange-500 transition" aria-expanded="{{ request()->routeIs('admin.private-training.*', 'admin.private-schedule.*', 'admin.courses', 'admin.packages.*') ? 'true' : 'false' }}">
                            การสอน
                            <svg class="h-5 w-5 transition-transform {{ request()->routeIs('admin.private-training.*', 'admin.private-schedule.*', 'admin.courses', 'admin.packages.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="mobile-nav-dropdown pl-3 {{ request()->routeIs('admin.private-training.*', 'admin.private-schedule.*', 'admin.courses', 'admin.packages.*') ? '' : 'hidden' }}">
                            <a href="{{ route('admin.private-training.index') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.private-training.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">จัดการ Private Training</a>
                            <a href="{{ route('admin.private-schedule.index') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.private-schedule.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">Schedule บุคลากร</a>
                            <a href="{{ route('admin.courses') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.courses') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">จัดการคอร์สเรียน</a>
                            <a href="{{ route('admin.packages.index') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.packages.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">จัดการแพ็กเกจ</a>
                        </div>
                    </div>
                    <div class="border-t border-gray-800" data-mobile-nav-dropdown>
                        <button type="button" class="mobile-nav-dropdown-btn flex w-full items-center justify-between py-2 text-left text-sm text-gray-300 hover:text-orange-500 transition" aria-expanded="{{ request()->routeIs('history', 'admin.dashboard') ? 'true' : 'false' }}">
                            รายงานและภาพรวม
                            <svg class="h-5 w-5 transition-transform {{ request()->routeIs('history', 'admin.dashboard') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="mobile-nav-dropdown pl-3 {{ request()->routeIs('history', 'admin.dashboard') ? '' : 'hidden' }}">
                            <a href="{{ route('admin.dashboard') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.dashboard') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">Dashboard</a>
                            <a href="{{ route('history') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('history') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">ดูประวัติการจอง</a>
                        </div>
                    </div>
                    <div class="border-t border-gray-800" data-mobile-nav-dropdown>
                        <button type="button" class="mobile-nav-dropdown-btn flex w-full items-center justify-between py-2 text-left text-sm text-gray-300 hover:text-orange-500 transition" aria-expanded="{{ request()->routeIs('admin.credit-topup-packages.index', 'admin.credit-topups.index') ? 'true' : 'false' }}">
                            เติมเครดิต
                            <svg class="h-5 w-5 transition-transform {{ request()->routeIs('admin.credit-topup-packages.index', 'admin.credit-topups.index') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="mobile-nav-dropdown pl-3 {{ request()->routeIs('admin.credit-topup-packages.index', 'admin.credit-topups.index') ? '' : 'hidden' }}">
                            <a href="{{ route('admin.credit-topup-packages.index') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.credit-topup-packages.index') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">แพ็กเกจเครดิต</a>
                            <a href="{{ route('admin.credit-topups.index') }}" class="block py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.credit-topups.index') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">คำขอเติมเครดิต</a>
                        </div>
                    </div>

                </div>
            @else
                <div class="flex flex-col py-2">
                    <a href="{{ route('home') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('home') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        หน้าแรก
                    </a>
                    <a href="{{ route('booking.index') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('booking.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        จองสนาม
                    </a>
                    @if($user->role === 'staff' && in_array($user->membership_type, ['coach', 'court_assistant'], true))
                        <a href="{{ route('private-training.my-schedule') }}" class="flex items-center hover:text-orange-500 transition {{ request()->routeIs('private-training.my-schedule') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            ตารางงาน
                        </a>
                    @elseif($user->role === 'staff' && in_array($user->membership_type, ['permanent', 'temporary', 'intern'], true))
                        <a href="{{ route('admin.private-training.index') }}" class="flex items-center py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.private-training.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            เทรนเนอร์ส่วนตัว
                        </a>
                    @else
                        <a href="{{ route('private-training.index') }}" class="flex items-center py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('private-training.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            เทรนเนอร์ส่วนตัว
                        </a>
                    @endif
                    <a href="{{ route('history') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('history') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        ประวัติการจอง
                    </a>

                    {{-- @if($canManageBookings && auth()->user()->role === 'staff')
                        <a href="{{ route('admin.bookings') }}" class="py-2 hover:text-orange-500 transition {{ request()->routeIs('admin.bookings') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            จัดการการจอง
                        </a>
                    @endif --}}
                </div>
            @endif

            @php
                $mUser = auth()->user();
                $mUnreadCount = $mUser->unreadNotifications()->count();
            @endphp

            {{-- ปุ่มแจ้งเตือน (มือถือ ลิงก์ไปหน้ารายละเอียด หรือ toggle เดียวกันกับด้านบนก็ได้) --}}
            <button id="notifBtnMobile" class="w-full flex items-center justify-between py-2 text-sm text-gray-300 hover:text-orange-500 transition border-t border-gray-800">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    การแจ้งเตือน
                </span>
                <span id="notifBadgeMobile" class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full {{ $mUnreadCount ? '' : 'hidden' }}">{{ $mUnreadCount > 99 ? '99+' : $mUnreadCount }}</span>
            </button>
            {{-- ใช้ dropdown เดียวกับ desktop โดยอ้างอิงผ่าน id เดิม --}}
            <div id="notifDropdownMobile" class="hidden bg-gray-800 rounded-xl mt-1 mb-2 overflow-hidden"></div>

            @if($isAdminLike)
                <div class="border-t border-gray-800 pt-2 mt-1">
                    <div class="text-xs text-gray-400 mb-1">เข้าสู่ระบบในฐานะ</div>
                    <div class="font-bold text-orange-500 mb-2 truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                    <div class="flex flex-col">
                        <a href="{{ route('admin.users.index') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">จัดการผู้ใช้งาน</a>
                        <a href="{{ route('admin.edit.text') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">แก้ไขเนื้อหาเว็บไซต์</a>
                        <a href="{{ route('admin.credit-topups.index') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">คำขอเติมเครดิต</a>
                        <a href="{{ route('admin.credit-topup-packages.index') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">แพ็กเกจเครดิต</a>
                        <a href="{{ route('profile') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">ตั้งค่าโปรไฟล์</a>
                    </div>
                </div>
            @else
                <div class="border-t border-gray-800 pt-2 mt-1">
                    <div class="text-xs text-gray-400 mb-1">เข้าสู่ระบบในฐานะ</div>
                    <div class="font-bold text-orange-500 mb-2 truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                    <div class="flex flex-col">
                        <a href="{{ route('profile') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">ตั้งค่าโปรไฟล์</a>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="w-full border border-gray-500 text-gray-300 px-4 py-2 rounded-full text-sm hover:border-orange-500 hover:text-orange-500 transition">
                    ออกจากระบบ
                </button>
            </form>
        @endauth

        @guest
            <a href="{{ route('login') }}" class="flex items-center hover:text-gray-300 transition mt-3">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                เข้าสู่ระบบ
            </a>
            <a href="{{ route('register') }}" class="flex items-center hover:text-gray-300 transition mt-3">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                สมัครสมาชิก
            </a>
        @endguest
    </div>

    <!-- Scripts สำหรับ Notification & Admin Dropdown & Mobile Menu -->
    <script>
        //JavaScript สำหรับจัดการการแสดง/ซ่อน Dropdown แจ้งเตือน
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');

        // สำหรับ Admin Menu Dropdown
        const adminMenuBtn = document.getElementById('adminMenuBtn');
        const adminMenuDropdown = document.getElementById('adminMenuDropdown');

        // สำหรับเมนูมือถือ
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuIconOpen = document.getElementById('mobileMenuIconOpen');
        const mobileMenuIconClose = document.getElementById('mobileMenuIconClose');

        // Accordion สำหรับเมนูกลุ่มบนมือถือ
        const mobileNavDropdowns = document.querySelectorAll('[data-mobile-nav-dropdown]');
        mobileNavDropdowns.forEach((menu) => {
            const button = menu.querySelector('.mobile-nav-dropdown-btn');
            const dropdown = menu.querySelector('.mobile-nav-dropdown');
            const icon = button?.querySelector('svg');

            button?.addEventListener('click', () => {
                const isOpen = !dropdown?.classList.contains('hidden');

                mobileNavDropdowns.forEach((otherMenu) => {
                    if (otherMenu === menu) return;

                    otherMenu.querySelector('.mobile-nav-dropdown')?.classList.add('hidden');
                    otherMenu.querySelector('.mobile-nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
                    otherMenu.querySelector('svg')?.classList.remove('rotate-180');
                });

                dropdown?.classList.toggle('hidden', isOpen);
                button.setAttribute('aria-expanded', String(!isOpen));
                icon?.classList.toggle('rotate-180', !isOpen);
            });
        });

        // Dropdown ของเมนูผู้ดูแลในแถบหลัก
        const adminNavDropdowns = document.querySelectorAll('[data-admin-nav-dropdown]');
        adminNavDropdowns.forEach((menu) => {
            const button = menu.querySelector('.admin-nav-dropdown-btn');
            const dropdown = menu.querySelector('.admin-nav-dropdown');

            button?.addEventListener('click', () => {
                const isOpen = !dropdown?.classList.contains('hidden');
                adminNavDropdowns.forEach((otherMenu) => {
                    otherMenu.querySelector('.admin-nav-dropdown')?.classList.add('hidden');
                    otherMenu.querySelector('.admin-nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    dropdown?.classList.remove('hidden');
                    button.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // เมื่อคลิกที่ปุ่มแจ้งเตือน ให้สลับการแสดงผลของ Dropdown
        notifBtn?.addEventListener('click', () => {
            notifDropdown?.classList.toggle('hidden');
            adminMenuDropdown?.classList.add('hidden'); // ปิดเมนูอื่น
        });

        // เมื่อคลิกปุ่ม Admin Menu
        adminMenuBtn?.addEventListener('click', () => {
            adminMenuDropdown?.classList.toggle('hidden');
            notifDropdown?.classList.add('hidden'); // ปิดเมนูอื่น
        });

        // เมื่อคลิกปุ่มเมนูมือถือ/แท็บเล็ต ให้เปิด/ปิดเมนูและสลับไอคอน
        mobileMenuBtn?.addEventListener('click', () => {
            mobileMenu?.classList.toggle('hidden');
            mobileMenuIconOpen?.classList.toggle('hidden');
            mobileMenuIconClose?.classList.toggle('hidden');
        });

        // เมื่อคลิกที่พื้นที่นอก Dropdown ให้ซ่อน Dropdown
        window.addEventListener('click', (e) => {
            if (!notifBtn?.contains(e.target) && !notifDropdown?.contains(e.target)) {
                notifDropdown?.classList.add('hidden');
            }
            if (!adminMenuBtn?.contains(e.target) && !adminMenuDropdown?.contains(e.target)) {
                adminMenuDropdown?.classList.add('hidden');
            }
            adminNavDropdowns.forEach((menu) => {
                if (!menu.contains(e.target)) {
                    menu.querySelector('.admin-nav-dropdown')?.classList.add('hidden');
                    menu.querySelector('.admin-nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
                }
            });
            mobileNavDropdowns.forEach((menu) => {
                if (!menu.contains(e.target) && !mobileMenu?.contains(e.target)) {
                    menu.querySelector('.mobile-nav-dropdown')?.classList.add('hidden');
                    menu.querySelector('.mobile-nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
                    menu.querySelector('svg')?.classList.remove('rotate-180');
                }
            });
        });

        // ปุ่มแจ้งเตือนในเมนูมือถือ: แสดงเนื้อหาเดียวกับ dropdown บน desktop
        const notifBtnMobile = document.getElementById('notifBtnMobile');
        const notifDropdownMobile = document.getElementById('notifDropdownMobile');
        notifBtnMobile?.addEventListener('click', () => {
            if (notifDropdown && notifDropdownMobile) {
                notifDropdownMobile.innerHTML = notifDropdown.innerHTML;
            }
            notifDropdownMobile?.classList.toggle('hidden');
        });

       // ===================== อ่านแจ้งเตือนแบบไม่ reload หน้า =====================

let readCount = 0; // นับจำนวนครั้งที่กด "อ่านแล้ว" สำเร็จ

// ลดตัวเลข badge ทั้ง desktop และ mobile ทีละ 1 (หรือรับค่าที่ต้องการหักลบ)
function decreaseBadge(amount) {
    [document.getElementById('notifBadge'), document.getElementById('notifBadgeMobile')]
        .forEach(function (badge) {
            if (!badge) return;
            let current = parseInt(badge.textContent.replace('+', ''), 10) || 0;
            let next = Math.max(current - amount, 0);
            if (next <= 0) {
                badge.classList.add('hidden');
                badge.textContent = '0';
            } else {
                badge.textContent = next > 99 ? '99+' : next;
            }
        });
}

// ล้าง badge ทั้งหมด (ใช้ตอนกด "อ่านทั้งหมด")
function clearBadge() {
    [document.getElementById('notifBadge'), document.getElementById('notifBadgeMobile')]
        .forEach(function (badge) {
            if (!badge) return;
            badge.classList.add('hidden');
            badge.textContent = '0';
        });
}

// เมื่อรายการแจ้งเตือนถูกลบจนหมด ให้โชว์ข้อความว่าง + ซ่อน header "อ่านทั้งหมด"
function checkEmptyState() {
    const remaining = document.querySelectorAll('#notifItemsWrap .notif-item').length;
    if (remaining === 0) {
        document.getElementById('notifEmptyMsg')?.classList.remove('hidden');
        document.getElementById('notifDropdownHeader')?.classList.add('hidden');
    }
}

document.addEventListener('submit', function (e) {
    const form = e.target;

    // --- กดอ่านแล้ว ทีละรายการ ---
    if (form.classList.contains('mark-read-form')) {
        e.preventDefault();

        const url = form.getAttribute('action');
        const token = form.querySelector('input[name="_token"]').value;
        const item = form.closest('.notif-item');

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        }).then(function (res) {
            if (!res.ok) throw new Error('failed');

            const notifId = item?.getAttribute('data-notif-id');
            document.querySelectorAll('.notif-item[data-notif-id="' + notifId + '"]')
                .forEach(function (el) { el.remove(); });

            decreaseBadge(1);
            checkEmptyState();

            // นับจำนวนครั้งที่กดอ่านสำเร็จ ถ้าครบ 10 ให้ reload หน้า
            readCount++;
            if (readCount >= 10) {
                window.location.reload();
            }
        }).catch(function (err) {
            console.error(err);
        });

        return;
    }

    // --- กดอ่านทั้งหมด ---
    if (form.classList.contains('mark-all-read-form')) {
        e.preventDefault();

        const url = form.getAttribute('action');
        const token = form.querySelector('input[name="_token"]').value;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        }).then(function (res) {
            if (!res.ok) throw new Error('failed');
            // อ่านทั้งหมดแปลว่าครบแล้วแน่นอน ให้ reload หน้าเลย
            window.location.reload();
        }).catch(function (err) {
            console.error(err);
        });
    }
});

    </script>
</nav>
