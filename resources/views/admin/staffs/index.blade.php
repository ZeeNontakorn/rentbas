@extends('layouts.app')

@section('title', 'จัดการผู้ช่วยสนามและโค้ช')

@section('content')

<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-7xl">

        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">จัดการโค้ช / ผู้ช่วยสนาม</h1>
            <p class="text-sm text-gray-500 mt-1">ค้นหา ดูข้อมูลโปรไฟล์ ตารางเวลาว่างของโค้ช และผู้ช่วยสนาม</p>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between border-b border-gray-200 mb-6 gap-4">

            <div class="flex gap-2 -mb-px overflow-x-auto select-none">
                @foreach([
                    ['type' => null, 'label' => 'บุคลากรทั้งหมด', 'active' => !request('type'), 'color' => 'border-orange-500 text-orange-600 font-semibold'],
                    ['type' => 'coach', 'label' => 'เฉพาะโค้ช (Coaches)', 'active' => request('type') == 'coach', 'color' => 'border-blue-500 text-blue-600 font-semibold'],
                    ['type' => 'court_assistant', 'label' => 'เฉพาะผู้ช่วยสนาม (Staffs)', 'active' => request('type') == 'court_assistant', 'color' => 'border-purple-500 text-purple-600 font-semibold']
                ] as $tab)
                    <a href="{{ route('admin.staffs.index', array_filter(['type' => $tab['type'], 'search' => $search])) }}"
                       class="px-5 py-2.5 text-sm font-medium border-b-2 transition-all cursor-pointer {{ $tab['active'] ? $tab['color'] : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>

            <div class="flex flex-col md:flex-row items-center justify-end gap-3 w-full lg:w-auto pb-3 lg:pb-0">

                <form method="GET" action="{{ route('admin.staffs.index') }}" class="flex w-full md:w-auto">
                    @if(request('role'))
                        <input type="hidden" name="role" value="{{ request('role') }}">
                    @endif

                    <input type="text" name="search" value="{{ $search }}" placeholder="ค้นหาชื่อ หรืออีเมล..."
                           class="w-full md:w-72 border border-gray-300 rounded-l-lg px-4 py-2.5 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-r-lg text-sm font-medium transition flex items-center justify-center gap-2 flex-shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                        </svg>
                        ค้นหา
                    </button>
                </form>

                <button type="button" onclick="toggleModal(true)" class="w-full md:w-auto bg-green-500 hover:bg-green-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2 flex-shrink-0 shadow-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    เพิ่มบุคลากร
                </button>

            </div>
        </div>

        @php
            $roleConfigs = [
                'coach' => ['color' => 'text-blue-500', 'title' => 'รายชื่อผู้ฝึกสอน (Coaches)', 'icon' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
                'staff' => ['color' => 'text-purple-500', 'title' => 'รายชื่อผู้ช่วยสนาม (Staffs)', 'icon' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z'],
                'default' => ['color' => 'text-orange-500', 'title' => 'รายชื่อบุคลากรทั้งหมด', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z']
            ];
            $currentConfig = $roleConfigs[request('role')] ?? $roleConfigs['default'];
        @endphp

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-medium text-gray-700 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $currentConfig['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $currentConfig['icon'] }}"/>
                    </svg>
                    {{ $currentConfig['title'] }}
                </h2>
                @if($search)
                    <span class="text-xs bg-orange-50 text-orange-600 border border-orange-200 px-3 py-1 rounded-full font-medium">
                        ค้นหา: "{{ $search }}"
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 font-medium">รหัส</th>
                            <th class="px-6 py-3 font-medium">ชื่อบุคลากร</th>
                            <th class="px-6 py-3 font-medium">ตำแหน่ง/หน้าที่</th>
                            <th class="px-6 py-3 font-medium text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($staffs as $s)
                            @php
                                $isCoach = $s->membership_type === 'coach';
                                $themeClass = $isCoach ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600';
                                $badgeClass = $isCoach ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700';
                                $roleLabel = $isCoach ? 'ผู้ฝึกสอน (Coach)' : 'ผู้ช่วยสนาม (Staff)';
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-gray-400 text-xs font-mono">#{{ $s->id }}</td>
                                <td class="px-6 py-4 font-medium text-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs {{ $themeClass }}">
                                            {{ mb_strtoupper(mb_substr($s->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p>{{ $s->name }}</p>
                                            <p class="text-xs text-gray-500 font-normal">{{ $s->email ?? 'ไม่มีอีเมล' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs rounded-full font-medium {{ $badgeClass }}">
                                        {{ $roleLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.staffs.show', $s->id) }}" class="inline-flex items-center gap-1.5 bg-gray-800 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-xs font-medium transition shadow-sm cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        ดูโปรไฟล์และตารางงาน
                                    </a>

                                    <form action="{{ route('admin.staffs.destroy', $s->id) }}" method="POST" class="inline-block form-delete">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn-delete inline-flex items-center gap-1.5 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-medium transition shadow-sm cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            ลบ
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center">
                                    <p class="text-gray-400 font-medium text-sm">ไม่พบข้อมูลบุคลากรในหมวดหมู่นี้</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($staffs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-slate-50">
                    {{ $staffs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<div id="addStaffModal" class="fixed inset-0 z-50 hidden bg-slate-900/20 backdrop-blur-sm flex items-center justify-center transition-all">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden transform transition-all">

        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">เพิ่มบุคลากรใหม่</h3>
        </div>

        <form action="{{ route('admin.staffs.store') }}" method="POST">
            @csrf
            <div class="px-6 py-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ชื่อ-นามสกุล <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="สมชาย ขยันยิ่งใหญ่"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-gray-700">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ตำแหน่ง (Role) <span class="text-red-500">*</span>
                    </label>
                    <select name="membership_type" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white transition text-gray-700">
                        <option value="coach" {{ old('membership_type') == 'coach' ? 'selected' : '' }}>ผู้ฝึกสอน (Coach)</option>
                        <option value="court_assistant" {{ old('membership_type') == 'court_assistant' ? 'selected' : '' }}>ผู้ช่วยสนาม (Court Assistant)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        อีเมล <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="example@domain.com"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-gray-700">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        รหัสผ่านเข้าใช้งาน <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password" required minlength="6" placeholder="ต้องมีอย่างน้อย 6 ตัวอักษร"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-gray-700">
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="toggleModal(false)"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition cursor-pointer">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-sm cursor-pointer">
                    บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function toggleModal(show) {
        const modal = document.getElementById('addStaffModal');
        modal.classList.toggle('hidden', !show);
        document.body.style.overflow = show ? 'hidden' : 'auto';
    }

    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('form');

            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "หากลบแล้วจะไม่สามารถกู้คืนข้อมูลบุคลากรนี้ได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'ใช่, ลบข้อมูลเลย!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    @if(session('success'))
        Toast.fire({ icon: 'success', title: '{{ session('success') }}' });
    @endif

    @if($errors->any())
        Toast.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: '{{ $errors->first() }}' });
    @endif
</script>
@endsection
