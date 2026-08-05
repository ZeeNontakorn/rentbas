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
<nav class="sticky top-0 z-50 bg-gray-900 shadow-md text-white">
    <div class="container mx-auto flex justify-between items-center py-4 px-4 md:px-10">
        <!-- Logo / ชื่อระบบ -->
        <a href="{{ route('home') }}" class="flex items-center font-bold text-xl md:text-2xl hover:text-gray-300 transition">
            <!-- System Name -->
            <span class="font-bold text-lg md:text-2xl leading-tight">THATA HOMECOURT</span>
        </a>

        <div class="flex items-center gap-3 md:hidden ml-auto mr-3">
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

        <!-- ปุ่มเปิด/ปิดเมนู สำหรับจอมือถือ -->
        <button id="mobileMenuBtn" class="md:hidden flex items-center focus:outline-none hover:text-orange-500 transition" aria-label="เปิดเมนู">
            <svg id="mobileMenuIconOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg id="mobileMenuIconClose" class="w-7 h-7 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- เมนูหลัก (Desktop) -->
        <div class="hidden md:flex flex-1 min-w-0 flex-nowrap justify-end items-center gap-5 lg:gap-8 whitespace-nowrap">
            @auth
                @if($activeCheckout ?? false)
                    <a href="{{ route('checkout.show', $activeCheckout) }}" class="flex items-center gap-1 rounded-full bg-orange-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-orange-600 transition">
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
                    <!-- เมนูหลักสำหรับ Admin -->
                    <a href="{{ route('home') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition {{ request()->routeIs('home') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        หน้าแรก
                    </a>
                    <a href="{{ route('admin.bookings') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition {{ request()->routeIs('admin.bookings') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        จัดการการจอง
                    </a>
                    <!-- จัดการสนาม -->
                    <div class="relative" data-admin-nav-dropdown>
                        <button type="button" class="admin-nav-dropdown-btn flex items-center gap-2 text-sm whitespace-nowrap hover:text-orange-500 transition focus:outline-none {{ request()->routeIs('admin.courts', 'admin.pricing.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}" aria-expanded="false">
                            จัดการสนาม
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="admin-nav-dropdown hidden absolute left-0 mt-3 w-48 overflow-hidden rounded-xl border border-gray-700 bg-gray-800 text-sm text-gray-100 shadow-lg z-50">
                            <a href="{{ route('admin.courts') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.courts') ? 'text-orange-500 font-bold' : '' }}">จัดการสนาม</a>
                            <a href="{{ route('admin.pricing.index') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.pricing.*') ? 'text-orange-500 font-bold' : '' }}">ตั้งราคา</a>
                        </div>
                    </div>

                    <!-- การสอน -->
                    <div class="relative" data-admin-nav-dropdown>
                        <button type="button" class="admin-nav-dropdown-btn flex items-center gap-2 text-sm whitespace-nowrap hover:text-orange-500 transition focus:outline-none {{ request()->routeIs('admin.private-training.*', 'admin.private-schedule.*', 'admin.courses') ? 'text-orange-500 font-bold' : 'text-gray-300' }}" aria-expanded="false">
                            การสอน
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="admin-nav-dropdown hidden absolute left-0 mt-3 w-56 overflow-hidden rounded-xl border border-gray-700 bg-gray-800 text-sm text-gray-100 shadow-lg z-50">
                            <a href="{{ route('admin.private-training.index') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.private-training.*') ? 'text-orange-500 font-bold' : '' }}">จัดการ Private Training</a>
                            <a href="{{ route('admin.private-schedule.index') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.private-schedule.*') ? 'text-orange-500 font-bold' : '' }}">ตารางโค้ช</a>
                            <a href="{{ route('admin.courses') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.courses') ? 'text-orange-500 font-bold' : '' }}">จัดการคอร์สเรียน</a>
                        </div>
                    </div>

                    <!-- รายงานและภาพรวม -->
                    <div class="relative" data-admin-nav-dropdown>
                        <button type="button" class="admin-nav-dropdown-btn flex items-center gap-2 text-sm whitespace-nowrap hover:text-orange-500 transition focus:outline-none {{ request()->routeIs('history', 'admin.dashboard') ? 'text-orange-500 font-bold' : 'text-gray-300' }}" aria-expanded="false">
                            รายงานและภาพรวม
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="admin-nav-dropdown hidden absolute left-0 mt-3 w-52 overflow-hidden rounded-xl border border-gray-700 bg-gray-800 text-sm text-gray-100 shadow-lg z-50">
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('admin.dashboard') ? 'text-orange-500 font-bold' : '' }}">Dashboard</a>
                            <a href="{{ route('history') }}" class="block px-4 py-3 hover:bg-gray-700 transition {{ request()->routeIs('history') ? 'text-orange-500 font-bold' : '' }}">ดูประวัติการจอง</a>
                        </div>
                    </div>
                @else
                    <!-- หน้าแรก User -->
                    <a href="{{ route('home') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition {{ request()->routeIs('home') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        หน้าแรก
                    </a>
                    <!-- จองสนาม สำหรับ User -->
                    <a href="{{ route('booking.index') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition {{ request()->routeIs('booking.index') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        จองสนาม
                    </a>

                    <!-- เทรนเนอร์ส่วนตัว สำหรับ User -->
                    @if($user->role === 'staff' && $user->membership_type === 'coach')
                        <a href="{{ route('private-training.my-schedule') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition {{ request()->routeIs('private-training.my-schedule') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            ตาราง Private ของฉัน
                        </a>
                    @elseif($user->role === 'staff' && in_array($user->membership_type, ['permanent', 'temporary', 'intern'], true))
                        <!-- เทรนเนอร์ส่วนตัว สำหรับ Staff (จัดการคำขอ) -->
                        <a href="{{ route('admin.private-training.index') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition {{ request()->routeIs('admin.private-training.index') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            เทรนเนอร์ส่วนตัว
                        </a>
                    @else
                        <!-- เทรนเนอร์ส่วนตัว สำหรับ User -->
                        <a href="{{ route('private-training.index') }}" class="flex items-center text-sm whitespace-nowrap hover:text-orange-500 transition {{ request()->routeIs('private-training.index') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            เทรนเนอร์ส่วนตัว
                        </a>
                    @endif

                    <!-- ประวัติการจอง -->
                    <a href="{{ route('history') }}" class="flex items-center hover:text-orange-500 transition {{ request()->routeIs('history') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        ประวัติการจอง
                    </a>

                    @if($canManageBookings && auth()->user()->role === 'staff')
                        <!-- จัดการการจอง สำหรับ Staff -->
                        <a href="{{ route('admin.bookings') }}" class="flex items-center hover:text-orange-500 transition {{ request()->routeIs('admin.bookings') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            จัดการการจอง
                        </a>
                    @endif
                @endif

                <!-- Notification -->
                @php
                    $user = auth()->user(); // ดึงข้อมูลผู้ใช้ที่ Login อยู่
                    $unreadCount = $user->unreadNotifications()->count(); // นับจำนวนแจ้งเตือนที่ยังไม่ได้อ่าน
                    $notifications = $user->unreadNotifications()->latest()->take(10)->get(); // ดึงเฉพาะที่ยังไม่ได้อ่าน ล่าสุด 10 รายการ
                @endphp
                {{-- ปุ่มแจ้งเตือน --}}
                <div class="relative">
                    {{-- ไอคอนกระดิ่ง --}}
                    <button id="notifBtn" class="relative focus:outline-none hover:text-gray-300 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span id="notifBadge" class="absolute -top-1 -right-2 bg-red-500 text-white text-xs px-1 rounded-full {{ $unreadCount ? '' : 'hidden' }}">
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                    </button>

                    {{-- Dropdown สำหรับแสดงรายการแจ้งเตือน --}}
                    <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-96 bg-gray-800 text-gray-100 rounded-xl shadow-lg overflow-y-auto max-h-96 z-50 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                        <div id="notifDropdownHeader" class="flex justify-between items-center px-3 py-2 border-b border-gray-700 sticky top-0 bg-gray-800 z-10 {{ $notifications->isEmpty() ? 'hidden' : '' }}">
                            <span class="text-xs text-gray-400 font-medium">แจ้งเตือนล่าสุด</span>
                            <form class="mark-all-read-form" method="POST" action="{{ route('notifications.readAll') }}">
                                @csrf
                                <button type="submit" class="text-[11px] text-orange-400 hover:text-orange-300 font-medium">อ่านทั้งหมด</button>
                            </form>
                        </div>

                        <div id="notifEmptyMsg" class="p-4 text-center text-gray-400 {{ $notifications->isEmpty() ? '' : 'hidden' }}">ไม่มีการแจ้งเตือนใหม่</div>

                        <div id="notifItemsWrap">
                @php
                    // ปลายทางเริ่มต้นเมื่อกดที่การ์ดแจ้งเตือน: ผู้ใช้ทั่วไป -> ประวัติการจอง, แอดมิน/สตาฟ -> จัดการการจอง
                    $defaultNotifTarget = in_array(auth()->user()->role, ['admin', 'staff']) ? route('admin.bookings') : route('history');
                    // แต่ถ้าเป็นแจ้งเตือนผลการจอง (อนุมัติ/ปฏิเสธ) ให้ไปหน้าประวัติการจองเสมอ ไม่ว่าจะ role ไหน
                    $historyOnlyTitles = ['การจองได้รับการอนุมัติ', 'การจองถูกปฏิเสธ'];
                @endphp
                            @foreach($notifications as $n)
                            {{-- แต่ละรายการแจ้งเตือน: กดที่การ์ดเพื่อไปดูรายละเอียด --}}
                                @php
                                    $notifTarget = in_array($n->title, $historyOnlyTitles) ? route('history') : $defaultNotifTarget;
                                    if ($n->title === 'มีรีวิวใหม่รอตรวจสอบ') {
                                        $notifTarget = route('admin.edit.text').'#review-moderation';
                                    }

                                    // สีประจำประเภทแจ้งเตือน ให้แยกอนุมัติ/ปฏิเสธออกจากกันชัดเจน
                                    $accentBorder = 'border-gray-700';
                                    $accentText = 'text-orange-400';
                                    $accentIcon = null;
                                    if ($n->title === 'การจองได้รับการอนุมัติ') {
                                        $accentBorder = 'border-l-4 border-l-green-500';
                                        $accentText = 'text-green-400';
                                        $accentIcon = ['bg' => 'bg-green-500/15', 'color' => 'text-green-400', 'path' => 'M5 13l4 4L19 7'];
                                    } elseif ($n->title === 'การจองถูกปฏิเสธ') {
                                        $accentBorder = 'border-l-4 border-l-red-500';
                                        $accentText = 'text-red-400';
                                        $accentIcon = ['bg' => 'bg-red-500/15', 'color' => 'text-red-400', 'path' => 'M6 18L18 6M6 6l12 12'];
                                    }
                                @endphp
                                <div class="notif-item p-3 border-b {{ $accentBorder }} hover:bg-gray-700 flex justify-between items-start cursor-pointer"
                                     data-notif-id="{{ $n->id }}"
                                     onclick="window.location.href='{{ $notifTarget }}'">
                                    <div class="flex-1 pr-2">
                                        <div class="flex items-center gap-2">
                                            @if($accentIcon)
                                                <span class="w-5 h-5 rounded-full {{ $accentIcon['bg'] }} {{ $accentIcon['color'] }} flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="{{ $accentIcon['path'] }}"></path></svg>
                                                </span>
                                            @endif
                                            <div class="font-semibold text-[15px] truncate">{{ $n->title ?? 'การจอง' }}</div>
                                        </div>
                                        <div class="text-[13px] text-gray-300 mt-1 leading-tight">
                                            @php
                                            // แยกข้อความโดยใช้ '|' เป็นตัวแบ่ง
                                                $msgParts = explode('|', $n->message ?? ($n->data['message'] ?? ''));
                                            @endphp

                                            {{-- แสดงส่วนแรกของข้อความ (หลัก) --}}
                                            {{ $msgParts[0] }}

                                            {{-- แสดงส่วนที่สองของข้อความ (ถ้ามี) — แปลง \n เป็นขึ้นบรรทัดใหม่จริง --}}
                                            @if(isset($msgParts[1]) && trim($msgParts[1]) !== '')
                                                <div class="mt-1 font-medium {{ $accentText }}">{!! nl2br(e(trim($msgParts[1]))) !!}</div>
                                            @endif
                                        </div>
                                        {{-- แสดงวันที่สร้างแจ้งเตือน --}}
                                        <div class="text-[11px] text-gray-400 mt-2">{{ $n->created_at->format('d M Y H:i') }}</div>
                                    </div>
                                    {{-- ปุ่มสำหรับทำเครื่องหมายว่าอ่านแล้ว (ถ้ายังไม่ได้อ่าน) — กันไม่ให้คลิกทะลุไปเปิดหน้าปลายทางด้วย --}}
                                    <form class="mark-read-form" method="POST" action="{{ route('notifications.read', $n) }}" onclick="event.stopPropagation()">
                                        @csrf
                                        <button type="submit" class="text-[11px] bg-gray-600 hover:bg-gray-500 text-white px-2 py-1 rounded transition whitespace-nowrap">อ่านแล้ว</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Divider for Admin (as per Figma) -->
                @if($isAdminLike)
                    <div class="h-6 w-px bg-gray-600 mx-2"></div>
                    <div class="relative">
                        <button id="adminMenuBtn" class="flex items-center hover:text-orange-500 transition text-gray-300 focus:outline-none">
                            {{ auth()->user()->name }}
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div id="adminMenuDropdown" class="hidden absolute right-0 mt-4 w-56 bg-gray-800 text-gray-100 rounded-xl shadow-lg z-50 border border-gray-700 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-700 bg-gray-900/50">
                                <div class="text-xs text-gray-400">เข้าสู่ระบบในฐานะ</div>
                                <div class="font-bold truncate text-orange-500">{{ auth()->user()->name }}</div>
                            </div>
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
                            <a href="{{ route('admin.credit-topups.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                คำขอเติมเครดิต
                            </a>
                            <a href="{{ route('admin.credit-topup-packages.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m16 0a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4a2 2 0 012-2m16 0a2 2 0 002-2V6a2 2 0 00-2-2H4a2 2 0 00-2 2v4a2 2 0 002 2m6 4h.01"></path></svg>
                                แพ็กเกจเติมเครดิต
                            </a>
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
                           <button type="button" class="border border-gray-500 text-gray-300 px-4 py-1.5 rounded-full text-sm hover:border-orange-500 hover:text-orange-500 transition flex items-center"
                            onclick="window.location.href='{{ route('admin.credits.show', auth()->user()) }}'">
                            {{ number_format(auth()->user()->credit_balance / 100, 2) }} <span class="ml-1">฿</span>
                        </button>

                @else
                {{-- credit --}}
                <button type="button" class="border border-gray-500 text-gray-300 px-4 py-1.5 rounded-full text-sm hover:border-orange-500 hover:text-orange-500 transition flex items-center"
                    onclick="window.location.href='{{ route('credits.topup.index') }}'">
                    {{ number_format(auth()->user()->credit_balance / 100, 2) }} <span class="ml-1">฿</span>
                </button>
                    <div class="h-6 w-px bg-gray-600 mx-2"></div>
                    <div class="relative">
                        <button id="userMenuBtn" class="flex items-center hover:text-orange-500 transition text-gray-300 focus:outline-none">
                            {{ auth()->user()->name }}
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div id="userMenuDropdown" class="hidden absolute right-0 mt-4 w-56 bg-gray-800 text-gray-100 rounded-xl shadow-lg z-50 border border-gray-700 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-700 bg-gray-900/50">
                                <div class="text-xs text-gray-400">เข้าสู่ระบบในฐานะ</div>
                                <div class="font-bold truncate text-orange-500">{{ auth()->user()->name }}</div>
                            </div>
                            <a href="{{ route('profile') }}" class="block px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                ตั้งค่าโปรไฟล์
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-700">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm hover:bg-gray-700 transition flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    ออกจากระบบ
                                </button>
                            </form>
                        </div>
                    </div>
                    {{-- credit --}}
                    <button type="button" class="border border-gray-500 text-gray-300 px-4 py-1.5 rounded-full text-sm hover:border-orange-500 hover:text-orange-500 transition flex items-center"
                        onclick="swalalert()">
                        {{ number_format(auth()->user()->credit_balance / 100, 2) }} <span class="ml-1">฿</span>
                    </button>
                @endif
            @endauth

            @guest
                <a href="{{ route('login') }}" class="flex items-center hover:text-gray-300 transition">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5.121 17.804A12.055 12.055 0 0112 15c2.21 0 4.21.635 5.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Login
                </a>
                <a href="{{ route('register') }}" class="flex items-center hover:text-gray-300 transition">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 11c2.21 0 4-1.79 4-4S14.21 3 12 3 8 4.79 8 7s1.79 4 4 4zM6 21v-2c0-2.21 3.58-4 6-4s6 1.79 6 4v2H6z"></path>
                    </svg>
                    Register
                </a>
            @endguest
        </div>
    </div>

    <!-- เมนู (Mobile, แบบเลื่อนลง) -->
    <div id="mobileMenu" class="hidden md:hidden border-t border-gray-800 bg-gray-900 px-4 pb-4">
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
                    <a href="{{ route('admin.courts') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.courts') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        จัดการสนาม
                    </a>
                    <a href="{{ route('admin.bookings') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.bookings') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        จัดการการจอง
                    </a>
                    <a href="{{ route('admin.private-training.index') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.private-training.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        จัดการ Private Training
                    </a>
                    <a href="{{ route('admin.private-schedule.index') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.private-schedule.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        ตารางโค้ช
                    </a>
                    <a href="{{ route('admin.pricing.index') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.pricing.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        ตั้งราคา
                    </a>
                    <a href="{{ route('history') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('history') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        ดูประวัติการจอง
                    </a>
                    <a href="{{ route('admin.courses') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.courses') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        จัดการคอร์สเรียน
                    </a>

                    <a href="{{ route('admin.dashboard') }}" class="py-2 text-sm hover:text-orange-500 transition {{ request()->routeIs('admin.dashboard') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        Dashboard
                    </a>

                </div>
            @else
                <div class="flex flex-col py-2">
                    <a href="{{ route('booking.index') }}" class="py-2 hover:text-orange-500 transition {{ request()->routeIs('booking.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        จองสนาม
                    </a>
                    @if($user->role === 'staff' && $user->membership_type === 'coach')
                        <a href="{{ route('private-training.my-schedule') }}" class="flex items-center hover:text-orange-500 transition {{ request()->routeIs('private-training.my-schedule') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            ตาราง Private ของฉัน
                        </a>
                    @elseif($user->role === 'staff' && in_array($user->membership_type, ['permanent', 'temporary', 'intern'], true))
                        <!-- เทรนเนอร์ส่วนตัว สำหรับ Staff (จัดการคำขอ) -->
                        <a href="{{ route('admin.private-training.index') }}" class="flex items-center hover:text-orange-500 transition {{ request()->routeIs('admin.private-training.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            เทรนเนอร์ส่วนตัว
                        </a>
                    @else
                        <!-- เทรนเนอร์ส่วนตัว สำหรับ User -->
                        <a href="{{ route('private-training.index') }}" class="flex items-center hover:text-orange-500 transition {{ request()->routeIs('private-training.*') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            เทรนเนอร์ส่วนตัว
                        </a>
                    @endif
                    <a href="{{ route('history') }}" class="py-2 hover:text-orange-500 transition {{ request()->routeIs('history') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                        ประวัติการจอง
                    </a>
                    @if($canManageBookings && auth()->user()->role === 'staff')
                        <a href="{{ route('admin.bookings') }}" class="py-2 hover:text-orange-500 transition {{ request()->routeIs('admin.bookings') ? 'text-orange-500 font-bold' : 'text-gray-300' }}">
                            จัดการการจอง
                        </a>
                    @endif
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
            <div id="notifDropdownMobile" class="hidden bg-gray-800 rounded-xl mt-1 mb-2 overflow-y-auto max-h-80 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"></div>

            @if($isAdminLike)
                <div class="border-t border-gray-800 pt-2 mt-1">
                    <div class="text-xs text-gray-400 mb-1">เข้าสู่ระบบในฐานะ</div>
                    <div class="font-bold text-orange-500 mb-2">{{ auth()->user()->name }}</div>
                    <div class="flex flex-col">
                        <a href="{{ route('admin.users.index') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">จัดการผู้ใช้งาน</a>
                        <a href="{{ route('admin.edit.text') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">แก้ไขเนื้อหาเว็บไซต์</a>
                        <a href="{{ route('admin.credit-topups.index') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">คำขอเติมเครดิต</a>
                        <a href="{{ route('admin.credit-topup-packages.index') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">แพ็กเกจเติมเครดิต</a>
                        <a href="{{ route('profile') }}" class="py-2 text-sm text-gray-300 hover:text-orange-500 transition">ตั้งค่าโปรไฟล์</a>
                    </div>
                </div>
            @else
                <a href="{{ route('profile') }}" class="block py-2 text-gray-300 font-medium hover:text-orange-500 transition border-t border-gray-800 mt-1">
                    {{ auth()->user()->name }}
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="w-full border border-gray-500 text-gray-300 px-4 py-2 rounded-full text-sm hover:border-orange-500 hover:text-orange-500 transition">
                    ออกจากระบบ
                </button>
            </form>
        @endauth

        @guest
            <div class="flex flex-col py-2">
                <a href="{{ route('login') }}" class="py-2 flex items-center text-gray-300 hover:text-orange-500 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5.121 17.804A12.055 12.055 0 0112 15c2.21 0 4.21.635 5.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Login
                </a>
                <a href="{{ route('register') }}" class="py-2 flex items-center text-gray-300 hover:text-orange-500 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 11c2.21 0 4-1.79 4-4S14.21 3 12 3 8 4.79 8 7s1.79 4 4 4zM6 21v-2c0-2.21 3.58-4 6-4s6 1.79 6 4v2H6z"></path>
                    </svg>
                    Register
                </a>
            </div>
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

        // สำหรับ User Menu Dropdown
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenuDropdown = document.getElementById('userMenuDropdown');

        // สำหรับเมนูมือถือ
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuIconOpen = document.getElementById('mobileMenuIconOpen');
        const mobileMenuIconClose = document.getElementById('mobileMenuIconClose');

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
            userMenuDropdown?.classList.add('hidden'); // ปิดเมนูอื่น
        });

        // เมื่อคลิกปุ่ม Admin Menu
        adminMenuBtn?.addEventListener('click', () => {
            adminMenuDropdown?.classList.toggle('hidden');
            notifDropdown?.classList.add('hidden'); // ปิดเมนูอื่น
            userMenuDropdown?.classList.add('hidden'); // ปิดเมนูอื่น
        });

        // เมื่อคลิกปุ่ม User Menu
        userMenuBtn?.addEventListener('click', () => {
            userMenuDropdown?.classList.toggle('hidden');
            notifDropdown?.classList.add('hidden'); // ปิดเมนูอื่น
            adminMenuDropdown?.classList.add('hidden'); // ปิดเมนูอื่น
        });

        // เมื่อคลิกปุ่มเมนูมือถือ ให้เปิด/ปิดเมนูและสลับไอคอน
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
            if (!userMenuBtn?.contains(e.target) && !userMenuDropdown?.contains(e.target)) {
                userMenuDropdown?.classList.add('hidden');
            }

            adminNavDropdowns.forEach((menu) => {
                if (!menu.contains(e.target)) {
                    menu.querySelector('.admin-nav-dropdown')?.classList.add('hidden');
                    menu.querySelector('.admin-nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
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
