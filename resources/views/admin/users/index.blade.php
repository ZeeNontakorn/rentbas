@extends('layouts.app')

@section('title', 'จัดการผู้ใช้งาน')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

        {{-- Header --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">จัดการผู้ใช้งาน</h1>
                <p class="text-sm text-gray-500 mt-1">ค้นหา ดูข้อมูล และจัดการผู้ใช้ทั้งหมดในระบบ</p>
            </div>

            {{-- ฟอร์มค้นหา --}}
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex w-full md:w-auto">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="ระบุชื่อผู้ใช้ที่ต้องการค้นหา..."
                       class="w-full md:w-72 border border-gray-300 rounded-l-lg px-4 py-2.5 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
                <button type="submit"
                        class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-r-lg text-sm font-medium transition flex items-center gap-2 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                    </svg>
                    ค้นหา
                </button>
            </form>
        </div>

        @php
            $isSuperadmin = auth()->user()->role === 'superadmin';
        @endphp
        {{-- ตารางผู้ใช้งาน --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            {{-- Table Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-medium text-gray-700 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    รายชื่อผู้ใช้งานทั้งหมด
                </h2>
                @if($search)
                    <span class="text-xs bg-orange-50 text-orange-600 border border-orange-200 px-3 py-1 rounded-full font-medium">
                        ค้นหา: "{{ $search }}"
                    </span>
                @endif
            </div>

            @php
                // สีพื้นหลังของแต่ละประเภทสมาชิก (รวมทั้งชุด user และชุด staff ให้ได้สีไม่ซ้ำกันครบทั้ง 6 ประเภท)
                $membershipPalette = [
                    'bg-emerald-100 text-emerald-700',
                    'bg-sky-100 text-sky-700',
                    'bg-amber-100 text-amber-700',
                    'bg-pink-100 text-pink-700',
                    'bg-indigo-100 text-indigo-700',
                    'bg-teal-100 text-teal-700',
                ];
                $allMembershipTypes = \App\Models\User::MEMBERSHIP_TYPES + \App\Models\User::STAFF_TYPES;
                $membershipColorMap = [];
                foreach (array_keys($allMembershipTypes) as $i => $typeKey) {
                    $membershipColorMap[$typeKey] = $membershipPalette[$i % count($membershipPalette)];
                }
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 font-medium text-center">ลำดับ</th>
                            <th class="px-6 py-3 font-medium text-center">ชื่อผู้ใช้</th>
                            <th class="px-6 py-3 font-medium text-center">อีเมล</th>
                            <th class="px-6 py-3 font-medium text-center">Role</th>
                            <th class="px-6 py-3 font-medium text-center">สถานะยืนยัน (OTP)</th>
                            <th class="px-6 py-3 font-medium text-center">ประเภทสมาชิก</th>
                            <th class="px-6 py-3 font-medium text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $u)

                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-gray-400 text-xs font-mono">#{{ $users->firstItem() + $loop->index }}</td>
                                <td class="px-6 py-4 font-medium text-gray-700">
                                    <span class="copy-text inline-block max-w-[140px] truncate align-bottom cursor-pointer hover:text-orange-500 transition"
                                          title="{{ $u->name }}" data-copy="{{ $u->name }}" onclick="copyToClipboard(this, event)">{{ $u->name }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <span class="copy-text inline-block max-w-[180px] truncate align-bottom cursor-pointer hover:text-orange-500 transition"
                                          title="{{ $u->email }}" data-copy="{{ $u->email }}" onclick="copyToClipboard(this, event)">{{ $u->email }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($u->role === 'superadmin')
                                        @if($isSuperadmin && $u->id !== auth()->id())
                                            <button type="button"
                                                    onclick="openRoleModal('{{ $u->id }}', '{{ $u->name }}', '{{ $u->role }}', '{{ route('admin.users.updateRole', $u) }}')"
                                                    class="inline-flex items-center justify-center w-[136px] px-2.5 py-1 text-xs rounded-full font-bold truncate bg-rose-100 text-rose-700 border border-rose-300 cursor-pointer hover:bg-rose-200 hover:border-rose-400 hover:shadow-sm transition"
                                                    title="แก้ไข Role">
                                                Super Admin
                                            </button>
                                        @else
                                            <span class="inline-flex items-center justify-center w-[136px] px-2.5 py-1 text-xs rounded-full font-bold truncate bg-rose-100 text-rose-700 border border-rose-300">
                                                Super Admin
                                            </span>
                                        @endif
                                    @else
                                        @php
                                            $roleColors = [
                                                'admin' => 'bg-purple-100 text-purple-700',
                                                'staff' => 'bg-blue-100 text-blue-700',
                                                'user'  => 'bg-gray-100 text-gray-600',
                                            ];
                                            $roleClass = $roleColors[$u->role] ?? 'bg-gray-100 text-gray-600';
                                        @endphp
                                        @if($u->id !== auth()->id())
                                            <button type="button"
                                                    onclick="openRoleModal('{{ $u->id }}', '{{ $u->name }}', '{{ $u->role }}', '{{ route('admin.users.updateRole', $u) }}')"
                                                    class="inline-flex items-center justify-center w-[136px] px-2.5 py-1 text-xs rounded-full font-medium truncate {{ $roleClass }} cursor-pointer hover:brightness-95 hover:ring-1 hover:ring-orange-300 hover:shadow-sm transition"
                                                    title="แก้ไข Role">
                                                {{ ucfirst($u->role) }}
                                            </button>
                                        @else
                                            <span class="inline-flex items-center justify-center w-[136px] px-2.5 py-1 text-xs rounded-full font-medium truncate {{ $roleClass }}">
                                                {{ ucfirst($u->role) }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($u->is_verified)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-full bg-green-100 text-green-700 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            ยืนยันแล้ว
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-full bg-red-100 text-red-700 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            ยังไม่ยืนยัน
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if(in_array($u->role, ['admin', 'superadmin'], true))
                                        {{-- แอดมิน: ป้ายพื้นหลังสีเทา มีกรอบ เหมือนป้ายประเภทสมาชิกอื่นๆ --}}
                                        <span class="inline-flex items-center justify-center w-[107px] px-2.5 py-1 text-xs rounded-full font-bold truncate bg-gray-100 text-gray-500 border border-gray-300">แอดมิน</span>
                                    @else
                                        {{-- ประเภทสมาชิก: ป้ายทั้งก้อนคลิกได้เพื่อเปิดโมดัลแก้ไข (สไตล์เดียวกับ Role) --}}
                                        @php
                                            $membershipClass = $membershipColorMap[$u->membership_type] ?? 'bg-gray-100 text-gray-600';
                                        @endphp
                                        <div class="membership-cell" data-user-id="{{ $u->id }}">
                                            <button type="button"
                                                class="membership-label membership-edit-btn inline-flex items-center justify-center w-[136px] px-2.5 py-1 text-xs rounded-full font-medium truncate {{ $membershipClass }} cursor-pointer hover:brightness-95 hover:ring-1 hover:ring-orange-300 hover:shadow-sm transition"
                                                data-user-id="{{ $u->id }}"
                                                data-name="{{ $u->name }}"
                                                data-value="{{ $u->membership_type }}"
                                                data-url="{{ route('admin.users.updateMembershipType', $u) }}"
                                                data-role="{{ $u->role }}"
                                                onclick="openMembershipModal(this.dataset.userId, this.dataset.name, this.dataset.value, this.dataset.url, this.dataset.role)"
                                                title="แก้ไขประเภทสมาชิก">
                                                {{ $u->membershipTypeLabel() }}
                                            </button>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.users.show', $u) }}"
                                           class="inline-flex items-center gap-1.5 bg-gray-800 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-xs font-medium transition shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            ดูข้อมูลและประวัติ
                                        </a>

                                        @if($u->id !== auth()->id() && $u->role !== 'superadmin' && ($u->role !== 'admin' || $isSuperadmin))
                                            <form action="{{ route('admin.users.destroy', $u) }}" method="POST"
                                                class="user-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    class="user-delete-button inline-flex items-center gap-1.5 rounded-lg bg-red-500 px-4 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-red-600">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    ลบ
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="text-gray-400">
                                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <p class="font-medium text-sm">ไม่พบข้อมูลผู้ใช้งานที่ค้นหา</p>
                                        @if($search)
                                            <p class="text-xs mt-1">ลองเปลี่ยนคำค้นหาใหม่</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-slate-50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
{{-- Modal แก้ไข Role --}}
<div id="roleModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 px-4">
    <div class="bg-white rounded-xl shadow-lg p-6 overflow-hidden" style="width: 100%; max-width: 360px;">
        <h3 class="text-base font-semibold text-gray-800 mb-1">แก้ไข Role</h3>
        <p class="text-sm text-gray-500 mb-4">ผู้ใช้: <span id="roleModalUserName" class="font-medium text-gray-700"></span></p>

        <form id="roleModalForm" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="role" id="roleModalInput" value="user">

            <label class="block text-xs font-medium text-gray-500 mb-2">เลือก Role ใหม่</label>
            <div class="grid grid-cols-3 gap-2 mb-5">
                <button type="button" data-role="user" onclick="selectRole(this)"
                        class="role-option flex items-center justify-center gap-1.5 px-2 py-2 text-xs font-medium rounded-lg border transition">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 bg-gray-400"></span>
                    User
                </button>
                <button type="button" data-role="staff" onclick="selectRole(this)"
                        class="role-option flex items-center justify-center gap-1.5 px-2 py-2 text-xs font-medium rounded-lg border transition">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 bg-blue-500"></span>
                    Staff
                </button>
                <button type="button" data-role="admin" onclick="selectRole(this)"
                        class="role-option flex items-center justify-center gap-1.5 px-2 py-2 text-xs font-medium rounded-lg border transition">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 bg-purple-500"></span>
                    Admin
                </button>

                @if($isSuperadmin)
                    <button type="button" data-role="superadmin" onclick="selectRole(this)"
                            class="role-option col-span-3 flex items-center justify-center gap-1.5 px-2 py-2 text-xs font-medium rounded-lg border transition">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 bg-rose-500"></span>
                        Super Admin
                    </button>
                @endif
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeRoleModal()"
                        class="px-4 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-medium transition">
                    บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal แก้ไขประเภทสมาชิก (สไตล์เดียวกับ Modal แก้ไข Role) --}}
<div id="membershipModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 px-4">
    <div class="bg-white rounded-xl shadow-lg p-6 overflow-hidden" style="width: 100%; max-width: 360px;">
        <h3 class="text-base font-semibold text-gray-800 mb-1">แก้ไขประเภทสมาชิก</h3>
        <p class="text-sm text-gray-500 mb-4">ผู้ใช้: <span id="membershipModalUserName" class="font-medium text-gray-700"></span></p>

        <form id="membershipModalForm">
            <input type="hidden" name="membership_type" id="membershipModalInput" value="">

            <label class="block text-xs font-medium text-gray-500 mb-2">เลือกประเภทสมาชิกใหม่</label>
            <div class="grid grid-cols-3 gap-2 mb-5">
                @php
                    $dotPalette = [
                        'bg-emerald-500',
                        'bg-sky-500',
                        'bg-amber-500',
                        'bg-pink-500',
                        'bg-indigo-500',
                        'bg-teal-500',
                    ];
                @endphp

                {{-- ชุดตัวเลือกสำหรับ role = user (ลูกค้า / ผู้สนับสนุน / นักเรียนบาส) --}}
                <div id="membershipOptionsUser" class="contents">
                    @foreach(\App\Models\User::MEMBERSHIP_TYPES as $value => $label)
                        <button type="button" data-value="{{ $value }}" onclick="selectMembership(this)"
                                class="membership-option-btn flex items-center justify-center gap-1.5 px-2 py-2 text-xs font-medium rounded-lg border transition">
                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $dotPalette[$loop->index % count($dotPalette)] }}"></span>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- ชุดตัวเลือกสำหรับ role = staff (พนักงานประจำ / พนักงานชั่วคราว / นักศึกษาฝึกงาน) --}}
                <div id="membershipOptionsStaff" class="contents hidden">
                    @foreach(\App\Models\User::STAFF_TYPES as $value => $label)
                        <button type="button" data-value="{{ $value }}" onclick="selectMembership(this)"
                                class="membership-option-btn flex items-center justify-center gap-1.5 px-2 py-2 text-xs font-medium rounded-lg border transition">
                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $dotPalette[($loop->index + 3) % count($dotPalette)] }}"></span>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeMembershipModal()"
                        class="px-4 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-medium transition">
                    บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Toast แจ้งเตือนคัดลอกสำเร็จ --}}
<div id="copyToast"
     class="fixed z-[9999] pointer-events-none opacity-0 -translate-y-1 transition-all duration-200 ease-out">
    <span class="inline-flex items-center gap-1.5 bg-gray-900 text-white text-xs font-medium px-3 py-1.5 rounded-lg shadow-lg">
        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        คัดลอกแล้ว
    </span>
</div>

<script>
    function selectRole(btn) {
        document.getElementById('roleModalInput').value = btn.dataset.role;

        document.querySelectorAll('.role-option').forEach(b => {
            b.classList.remove('bg-orange-500', 'text-white', 'border-orange-500');
            b.classList.add('border-gray-300', 'text-gray-600');
        });

        btn.classList.remove('border-gray-300', 'text-gray-600');
        btn.classList.add('bg-orange-500', 'text-white', 'border-orange-500');
    }

    function openRoleModal(id, name, currentRole, actionUrl) {
        document.getElementById('roleModalUserName').textContent = name;
        document.getElementById('roleModalInput').value = currentRole;
        document.getElementById('roleModalForm').action = actionUrl;

        // ไฮไลต์ปุ่มที่ตรงกับ role ปัจจุบัน
        document.querySelectorAll('.role-option').forEach(b => {
            b.classList.remove('bg-orange-500', 'text-white', 'border-orange-500');
            b.classList.add('border-gray-300', 'text-gray-600');
            if (b.dataset.role === currentRole) {
                b.classList.remove('border-gray-300', 'text-gray-600');
                b.classList.add('bg-orange-500', 'text-white', 'border-orange-500');
            }
        });

        const modal = document.getElementById('roleModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeRoleModal() {
        const modal = document.getElementById('roleModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('roleModal').addEventListener('click', function (e) {
        if (e.target === this) closeRoleModal();
    });

    // ===================== Modal แก้ไขประเภทสมาชิก =====================
    const membershipColorMap = {!! json_encode($membershipColorMap) !!};
    let membershipModalUrl = '';
    let membershipModalUserId = '';

    function selectMembership(btn) {
        document.getElementById('membershipModalInput').value = btn.dataset.value;

        document.querySelectorAll('.membership-option-btn').forEach(b => {
            b.classList.remove('bg-orange-500', 'text-white', 'border-orange-500');
            b.classList.add('border-gray-300', 'text-gray-600');
        });

        btn.classList.remove('border-gray-300', 'text-gray-600');
        btn.classList.add('bg-orange-500', 'text-white', 'border-orange-500');
    }

    function openMembershipModal(id, name, currentValue, actionUrl, role) {
        membershipModalUrl = actionUrl;
        membershipModalUserId = id;

        document.getElementById('membershipModalUserName').textContent = name;
        document.getElementById('membershipModalInput').value = currentValue;

        // สลับชุดตัวเลือกตาม role: staff เห็นชุดพนักงาน, user เห็นชุดลูกค้า/ผู้สนับสนุน/นักเรียนบาส
        const userGroup = document.getElementById('membershipOptionsUser');
        const staffGroup = document.getElementById('membershipOptionsStaff');
        if (role === 'staff') {
            userGroup.classList.add('hidden');
            staffGroup.classList.remove('hidden');
        } else {
            staffGroup.classList.add('hidden');
            userGroup.classList.remove('hidden');
        }

        // ไฮไลต์ปุ่มที่ตรงกับประเภทสมาชิกปัจจุบัน
        document.querySelectorAll('.membership-option-btn').forEach(b => {
            b.classList.remove('bg-orange-500', 'text-white', 'border-orange-500');
            b.classList.add('border-gray-300', 'text-gray-600');
            if (b.dataset.value === currentValue) {
                b.classList.remove('border-gray-300', 'text-gray-600');
                b.classList.add('bg-orange-500', 'text-white', 'border-orange-500');
            }
        });

        const modal = document.getElementById('membershipModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeMembershipModal() {
        const modal = document.getElementById('membershipModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('membershipModal').addEventListener('click', function (e) {
        if (e.target === this) closeMembershipModal();
    });

    document.getElementById('membershipModalForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const newValue = document.getElementById('membershipModalInput').value;
        const selectedBtn = document.querySelector('.membership-option-btn[data-value="' + newValue + '"]');
        const newLabel = selectedBtn ? selectedBtn.textContent.trim() : newValue;

        fetch(membershipModalUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ membership_type: newValue }),
        })
        .then(function (res) {
            if (!res.ok) throw new Error('failed');
            return res.json();
        })
        .then(function () {
            const cell = document.querySelector('.membership-cell[data-user-id="' + membershipModalUserId + '"]');
            if (cell) {
                const badge = cell.querySelector('.membership-label');
                badge.textContent = newLabel;
                const colorClass = membershipColorMap[newValue] || 'bg-gray-100 text-gray-600';
                badge.className = 'membership-label membership-edit-btn inline-flex items-center justify-center w-[136px] px-2.5 py-1 text-xs rounded-full font-medium truncate cursor-pointer hover:brightness-95 hover:ring-1 hover:ring-orange-300 hover:shadow-sm transition ' + colorClass;
                badge.dataset.value = newValue;
            }
            closeMembershipModal();
        })
        .catch(function () {
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
        });
    });

    document.querySelectorAll('.user-delete-button').forEach(function (button) {
        button.addEventListener('click', function () {
            const form = this.closest('.user-delete-form');

            Swal.fire({
                title: 'ยืนยันการลบบัญชี?',
                text: 'บัญชีผู้ใช้และข้อมูลที่เกี่ยวข้องจะถูกลบออกจากระบบถาวร และไม่สามารถกู้คืนได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'ยืนยันลบบัญชี',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true,
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });

    // ===================== คัดลอกชื่อ/อีเมลด้วยการคลิก =====================
    function copyToClipboard(el, event) {
        const text = el.dataset.copy;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => showCopyToast(event, el)).catch(() => fallbackCopy(text, event, el));
        } else {
            fallbackCopy(text, event, el);
        }
    }

    function fallbackCopy(text, event, el) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
            showCopyToast(event, el);
        } catch (err) {
            console.error('Copy failed', err);
        }
        document.body.removeChild(textarea);
    }

    let copyToastTimeout;
    function showCopyToast(event, el) {
        const toast = document.getElementById('copyToast');

        // ตำแหน่งอ้างอิงจากคลิก ถ้าไม่มี event (เช่นถูกเรียกจากที่อื่น) ให้อ้างจากตัว element แทน
        let x, y;
        if (event) {
            x = event.clientX;
            y = event.clientY;
        } else {
            const rect = el.getBoundingClientRect();
            x = rect.left;
            y = rect.top;
        }

        toast.style.left = x + 'px';
        toast.style.top = (y - 36) + 'px';

        // เด้งขึ้นเล็กน้อยแล้วค่อยจาง (retrigger animation ทุกครั้งที่คลิกซ้ำเร็วๆ)
        toast.classList.remove('opacity-0', '-translate-y-1');
        toast.classList.add('opacity-100', 'translate-y-0');

        clearTimeout(copyToastTimeout);
        copyToastTimeout = setTimeout(() => {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', '-translate-y-1');
        }, 1000);

        // เอฟเฟกต์เขียวชั่วครู่ที่ตัวข้อความเอง
        el.classList.add('text-emerald-600');
        clearTimeout(el._copyTimeout);
        el._copyTimeout = setTimeout(() => el.classList.remove('text-emerald-600'), 1000);
    }
</script>
@endsection
