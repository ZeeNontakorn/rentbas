@extends('layouts.app')

@section('title', 'จัดการผู้ใช้งาน')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-7xl">

        {{-- Flash message --}}
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">จัดการผู้ใช้งาน</h1>
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
                    ค้นหาผู้ใช้
                </button>
            </form>
        </div>

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
                // สีพื้นหลังของแต่ละประเภทสมาชิก (วนใช้จากพาเลตให้อัตโนมัติตามลำดับที่นิยามใน MEMBERSHIP_TYPES)
                $membershipPalette = [
                    'bg-emerald-100 text-emerald-700',
                    'bg-sky-100 text-sky-700',
                    'bg-amber-100 text-amber-700',
                    'bg-pink-100 text-pink-700',
                    'bg-indigo-100 text-indigo-700',
                    'bg-teal-100 text-teal-700',
                ];
                $membershipColorMap = [];
                foreach (array_keys(\App\Models\User::MEMBERSHIP_TYPES) as $i => $typeKey) {
                    $membershipColorMap[$typeKey] = $membershipPalette[$i % count($membershipPalette)];
                }
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 font-medium">รหัส</th>
                            <th class="px-6 py-3 font-medium">ชื่อผู้ใช้</th>
                            <th class="px-6 py-3 font-medium">อีเมล</th>
                            <th class="px-6 py-3 font-medium">Role</th>
                            <th class="px-6 py-3 font-medium">สถานะยืนยัน (OTP)</th>
                            <th class="px-6 py-3 font-medium">ประเภทสมาชิก</th>
                            <th class="px-6 py-3 font-medium text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $u)
                            @continue($u->id === 0)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-gray-400 text-xs font-mono">#{{ $u->id }}</td>
                                <td class="px-6 py-4 font-medium text-gray-700">{{ $u->name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $u->email }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $roleColors = [
                                            'admin' => 'bg-purple-100 text-purple-700',
                                            'staff' => 'bg-blue-100 text-blue-700',
                                            'user'  => 'bg-gray-100 text-gray-600',
                                        ];
                                        $roleClass = $roleColors[$u->role] ?? 'bg-gray-100 text-gray-600';
                                    @endphp
                                    <div class="flex items-center gap-2 w-[136px]">
                                        <span class="inline-flex items-center justify-center flex-1 min-w-0 px-2.5 py-1 text-xs rounded-full font-medium truncate {{ $roleClass }}">
                                            {{ ucfirst($u->role) }}
                                        </span>
                                        <button type="button"
                                                onclick="openRoleModal('{{ $u->id }}', '{{ $u->name }}', '{{ $u->role }}', '{{ route('admin.users.updateRole', $u) }}')"
                                                class="text-gray-400 hover:text-orange-500 transition p-1 flex-shrink-0" title="แก้ไข Role">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    </div>
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
                                    @if($u->role === 'admin')
                                        {{-- แอดมิน: ป้ายพื้นหลังสีเทา มีกรอบ เหมือนป้ายประเภทสมาชิกอื่นๆ --}}
                                        <div class="flex items-center gap-2 w-[107px]">
                                            <span class="inline-flex items-center justify-center flex-1 min-w-0 px-2.5 py-1 text-xs rounded-full font-bold truncate bg-gray-100 text-gray-500 border border-gray-300">แอดมิน</span>
                                        </div>
                                    @else
                                        {{-- ประเภทสมาชิก: ป้ายสถานะ (มีสีตามประเภท) + ปุ่มแก้ไขเปิดโมดัล (สไตล์เดียวกับ Role) --}}
                                        @php
                                            $membershipClass = $membershipColorMap[$u->membership_type] ?? 'bg-gray-100 text-gray-600';
                                        @endphp
                                        <div class="flex items-center gap-2 w-[136px] membership-cell" data-user-id="{{ $u->id }}">
                                            <span class="membership-label inline-flex items-center justify-center flex-1 min-w-0 px-2.5 py-1 text-xs rounded-full font-medium truncate {{ $membershipClass }}">
                                                {{ $u->membershipTypeLabel() }}
                                            </span>
                                            <button type="button"
                                                    onclick="openMembershipModal('{{ $u->id }}', '{{ $u->name }}', '{{ $u->membership_type }}', '{{ route('admin.users.updateMembershipType', $u) }}')"
                                                    class="text-gray-400 hover:text-orange-500 transition p-1 flex-shrink-0" title="แก้ไขประเภทสมาชิก">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.users.show', $u) }}"
                                       class="inline-flex items-center gap-1.5 bg-gray-800 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-xs font-medium transition shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        ดูข้อมูลและประวัติ
                                    </a>
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
                        class="role-option px-2 py-2 text-xs font-medium rounded-lg border transition">
                    User
                </button>
                <button type="button" data-role="staff" onclick="selectRole(this)"
                        class="role-option px-2 py-2 text-xs font-medium rounded-lg border transition">
                    Staff
                </button>
                <button type="button" data-role="admin" onclick="selectRole(this)"
                        class="role-option px-2 py-2 text-xs font-medium rounded-lg border transition">
                    Admin
                </button>
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
                @foreach(\App\Models\User::MEMBERSHIP_TYPES as $value => $label)
                    <button type="button" data-value="{{ $value }}" onclick="selectMembership(this)"
                            class="membership-option-btn flex items-center justify-center gap-1.5 px-2 py-2 text-xs font-medium rounded-lg border transition">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $dotPalette[$loop->index % count($dotPalette)] }}"></span>
                        {{ $label }}
                    </button>
                @endforeach
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

    function openMembershipModal(id, name, currentValue, actionUrl) {
        membershipModalUrl = actionUrl;
        membershipModalUserId = id;

        document.getElementById('membershipModalUserName').textContent = name;
        document.getElementById('membershipModalInput').value = currentValue;

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
                badge.className = 'membership-label inline-flex items-center justify-center flex-1 min-w-0 px-2.5 py-1 text-xs rounded-full font-medium truncate ' + colorClass;
            }
            closeMembershipModal();
        })
        .catch(function () {
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
        });
    });
</script>
@endsection
