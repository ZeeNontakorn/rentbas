@extends('layouts.app')
@section('title', 'จัดการกลุ่มเล่นบาส')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8" x-data="{
    showSessionForm: false,
    showEditForm: false,
    showRoundForm: false,
    prefillSession: null,
    editSession: null,
    editStartH: '00', editStartM: '00', editEndH: '00', editEndM: '00',
    roundStartH: '00', roundStartM: '00', roundEndH: '00', roundEndM: '00'
}">
<div class="container mx-auto px-4 sm:px-6 max-w-7xl">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">กลุ่มเล่นบาส</h1>
            <p class="font-sarabun text-sm text-gray-500 mt-1">จัดการรอบประจำ และเปิดรอบให้สมาชิกลงชื่อ</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.group-sessions.history') }}"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                ประวัติกลุ่มเล่นบาส
            </a>
            <button @click="showSessionForm = true"
                class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 shadow-sm gap-2 cursor-pointer transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 5v14m7-7H5" />
                    </svg>สร้างรอบประจำใหม่
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- ═══ เทมเพลตรอบประจำ ═══ --}}
    <div class="flex items-center gap-2 mb-3 mt-2">
        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
        <h2 class="font-medium text-gray-700 text-sm">รอบประจำ (เทมเพลต)</h2>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-medium">ชื่อรอบ</th>
                        <th class="px-6 py-3 font-medium">วัน</th>
                        <th class="px-6 py-3 font-medium">เวลา</th>
                        <th class="px-6 py-3 font-medium">สนาม</th>
                        <th class="px-6 py-3 font-medium">จำนวน</th>
                        <th class="px-6 py-3 font-medium">เครดิต/คน</th>
                        <th class="px-6 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sessions as $s)
                    <tr>
                        <td class="px-6 py-3 text-gray-800 font-medium">{{ $s->name }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $s->dayLabel() }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $s->court->name ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $s->max_players }} คน</td>
                        <td class="px-6 py-3 text-gray-500">{{ $s->credit_cost == 0 ? 'ฟรี' : $s->credit_cost }}</td>
                        <td class="px-6 py-3 text-right space-x-2">
    <button
        @click="
            showRoundForm = true;
            prefillSession = {
                id: {{ $s->id }},
                title: '{{ addslashes($s->name) }}',
                start_time: '{{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}',
                end_time: '{{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}',
                court_id: {{ $s->court_id ?? 'null' }},
                max_players: {{ $s->max_players }},
                credit_cost: {{ $s->credit_cost }},
                play_date: '{{ $s->nextOccurrence()->format('Y-m-d') }}'
            };
            roundStartH = prefillSession.start_time.split(':')[0];
            roundStartM = prefillSession.start_time.split(':')[1];
            roundEndH = prefillSession.end_time.split(':')[0];
            roundEndM = prefillSession.end_time.split(':')[1];
        "
        class="text-xs font-medium text-orange-600 hover:text-orange-800 rounded-lg px-3 py-1.5 cursor-pointer transition">เปิดรับสมัครรอบใหม่</button>
    <button
        @click="
            showEditForm = true;
            editSession = {
                id: {{ $s->id }},
                name: '{{ addslashes($s->name) }}',
                day_of_week: {{ $s->day_of_week }},
                start_time: '{{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}',
                end_time: '{{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}',
                court_id: {{ $s->court_id ?? 'null' }},
                max_players: {{ $s->max_players }},
                credit_cost: {{ $s->credit_cost }}
            };
            editStartH = editSession.start_time.split(':')[0];
            editStartM = editSession.start_time.split(':')[1];
            editEndH = editSession.end_time.split(':')[0];
            editEndM = editSession.end_time.split(':')[1];
        "
        class="text-xs font-medium text-blue-600 hover:text-blue-800 rounded-lg px-3 py-1.5 cursor-pointer transition">แก้ไข</button>
        <form action="{{ route('admin.group-sessions.destroy', $s) }}" method="POST" class="inline"
    data-confirm="ลบเทมเพลต &quot;{{ $s->name }}&quot; ทิ้งถาวร? รอบที่เคยเปิดจากเทมเพลตนี้จะไม่หายไป "
    data-confirm-button-text="ยืนยันการลบ"
    data-confirm-danger="1">
    @csrf
    @method('DELETE')
    <button class="text-xs font-medium text-red-500 hover:text-red-700 rounded-lg px-3 py-1.5 cursor-pointer transition">ลบ</button>
</form>
</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-6 text-center text-gray-400">ยังไม่มีรอบประจำ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ รอบที่เปิดอยู่ / จะถึง ═══ --}}
    <div class="flex items-center justify-between mb-3 mt-10">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <h2 class="font-medium text-gray-700 text-sm">รอบที่กำลังจะถึง</h2>
        </div>
        <button
            @click="
                showRoundForm = true;
                prefillSession = null;
                roundStartH = '00'; roundStartM = '00'; roundEndH = '00'; roundEndM = '00';
            "
            class="text-xs font-medium text-white bg-gray-500 hover:bg-gray-600 rounded-lg px-4 py-2 shadow-sm inline-flex item-center gap-2 cursor-pointer transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 5v14m7-7H5" />
            </svg>
            เปิดรอบแบบกำหนดเอง
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-medium">รอบ</th>
                        <th class="px-6 py-3 font-medium">วันที่</th>
                        <th class="px-6 py-3 font-medium">เวลา</th>
                        <th class="px-6 py-3 font-medium">คนลงชื่อ</th>
                        <th class="px-6 py-3 font-medium">สถานะ</th>
                        <th class="px-6 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($upcomingRounds as $r)
                    <tr>
                        <td class="px-6 py-3 text-gray-800 font-medium">{{ $r->title }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $r->play_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ \Carbon\Carbon::parse($r->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($r->end_time)->format('H:i') }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $r->players_count }}/{{ $r->max_players }}</td>
                        <td class="px-6 py-3">
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium',
                                'bg-emerald-50 text-emerald-600' => $r->status === 'open',
                                'bg-gray-100 text-gray-400' => $r->status === 'closed',
                            ])>
                                {{ $r->status === 'open' ? 'เปิดรับ' : 'ปิดรับแล้ว' }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('admin.group-sessions.rounds.show', $r) }}" class="text-xs font-medium text-orange-600 hover:text-orange-800 rounded-lg px-3 py-1.5 cursor-pointer transition">ดูรายชื่อ</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-6 text-center text-gray-400">ยังไม่มีรอบที่เปิดอยู่</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal: สร้างเทมเพลตรอบประจำ (ไม่มีเดดไลน์ เพราะเป็นแค่เทมเพลต ยังไม่ใช่รอบจริง) --}}
    <div x-show="showSessionForm" x-cloak class="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-50">
        <div @click.outside="showSessionForm = false" class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md overflow-hidden"
            x-data="{ startH: '00', startM: '00', endH: '00', endM: '00' }">

            <!-- Header พร้อมเส้นคั่น -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-2xl font-semibold text-gray-900">สร้างรอบประจำใหม่</h3>
                    <p class="font-sarabun text-sm text-gray-500 mt-1">กำหนดรายละเอียดรอบเล่นประจำสัปดาห์</p>
                </div>
            </div>

            <!-- Form -->
            <form action="{{ route('admin.group-sessions.store') }}" method="POST" class="p-6 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">ชื่อรอบ</label>
                    <input type="text" name="name" required placeholder="เช่น กลุ่มเล่นบาสค่ำ"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">วัน</label>
                    <select name="day_of_week" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                        <option value="1">จันทร์</option>
                        <option value="2">อังคาร</option>
                        <option value="3">พุธ</option>
                        <option value="4">พฤหัสบดี</option>
                        <option value="5">ศุกร์</option>
                        <option value="6">เสาร์</option>
                        <option value="0">อาทิตย์</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">เวลาเริ่ม</label>
                        <div class="flex gap-1">
                            <select x-model="startH" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                @for($i = 0; $i < 24; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                            <select x-model="startM" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                <option value="00">00</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                        <input type="hidden" name="start_time" x-bind:value="startH + ':' + startM">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">เวลาสิ้นสุด</label>
                        <div class="flex gap-1">
                            <select x-model="endH" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                @for($i = 0; $i < 24; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                            <select x-model="endM" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                <option value="00">00</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                        <input type="hidden" name="end_time" x-bind:value="endH + ':' + endM">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">สนาม</label>
                    <select name="court_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                        <option value="">- ไม่ระบุ -</option>
                        @isset($courts)
                            @foreach($courts as $court)
                                <option value="{{ $court->id }}">{{ $court->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">จำนวนคนสูงสุด</label>
                        <input type="number" name="max_players" value="25" min="1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">เครดิต/คน</label>
                        <input type="number" name="credit_cost" value="0" min="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showSessionForm = false" class="text-xs font-medium text-gray-500 hover:text-gray-700 rounded-lg px-4 py-2 cursor-pointer transition">ยกเลิก</button>
                    <button type="submit" class="text-xs font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-lg px-4 py-2 cursor-pointer transition">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: แก้ไขเทมเพลตรอบประจำ --}}
    <div x-show="showEditForm" x-cloak class="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-50">
        <div @click.outside="showEditForm = false" class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md p-6">
            <h3 class="font-semibold text-gray-800 text-[15px] mb-4">แก้ไขรอบประจำ</h3>
            <form :action="editSession ? '{{ url('/admin/group-sessions') }}/' + editSession.id : '#'" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">ชื่อรอบ</label>
                    <input type="text" name="name" required
                        x-bind:value="editSession ? editSession.name : ''"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">วัน</label>
                    <select name="day_of_week" required
                        x-bind:value="editSession ? editSession.day_of_week : ''"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                        <option value="1">จันทร์</option>
                        <option value="2">อังคาร</option>
                        <option value="3">พุธ</option>
                        <option value="4">พฤหัสบดี</option>
                        <option value="5">ศุกร์</option>
                        <option value="6">เสาร์</option>
                        <option value="0">อาทิตย์</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">เวลาเริ่ม</label>
                        <div class="flex gap-1">
                            <select x-model="editStartH" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                                @for($i = 0; $i < 24; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                            <select x-model="editStartM" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                                <option value="00">00</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                        <input type="hidden" name="start_time" x-bind:value="editStartH + ':' + editStartM">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">เวลาสิ้นสุด</label>
                        <div class="flex gap-1">
                            <select x-model="editEndH" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                                @for($i = 0; $i < 24; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                            <select x-model="editEndM" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                                <option value="00">00</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                        <input type="hidden" name="end_time" x-bind:value="editEndH + ':' + editEndM">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">สนาม</label>
                    <select name="court_id"
                        x-bind:value="editSession ? editSession.court_id : ''"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                        <option value="">- ไม่ระบุ -</option>
                        @isset($courts)
                            @foreach($courts as $court)
                                <option value="{{ $court->id }}">{{ $court->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">จำนวนคนสูงสุด</label>
                        <input type="number" name="max_players" min="1" required
                            x-bind:value="editSession ? editSession.max_players : 25"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">เครดิต/คน</label>
                        <input type="number" name="credit_cost" min="0" required
                            x-bind:value="editSession ? editSession.credit_cost : 0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showEditForm = false" class="text-xs font-medium text-gray-500 hover:text-gray-700 rounded-lg px-4 py-1.5 cursor-pointer transition">ยกเลิก</button>
                    <button type="submit" class="text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg px-4 py-1.5 cursor-pointer transition">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: เปิดรอบ (จากเทมเพลต หรือกำหนดเอง) — จุดเดียวที่มีเดดไลน์สละสิทธิ์ เพราะเป็นรอบจริง --}}
    <div x-show="showRoundForm" x-cloak class="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-50">
        <div @click.outside="showRoundForm = false" class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-2xl font-semibold text-gray-900">เปิดรอบสนามแบบกำหนดเอง</h3>
                    <p class="font-sarabun text-sm text-gray-500 mt-1">กรอกข้อมูลเพื่อเปิดรอบสนามใหม่</p>
                </div>
            </div>

            <form action="{{ route('admin.group-sessions.rounds.open') }}" method="POST" class="p-6 space-y-3">
                @csrf
                <input type="hidden" name="group_session_id" :value="prefillSession ? prefillSession.id : ''">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">ชื่อรอบ</label>
                    <input type="text" name="title" required
                        x-bind:value="prefillSession ? prefillSession.title : ''"
                        placeholder="เช่น กลุ่มเล่นบาสค่ำ อังคาร 25 คน สนาม A เทา"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">วันที่เล่น</label>
                    <input type="date" name="play_date" required
                        x-bind:value="prefillSession ? prefillSession.play_date : ''"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">เวลาเริ่ม</label>
                        <div class="flex gap-1">
                            <select x-model="roundStartH" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                @for($i = 0; $i < 24; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                            <select x-model="roundStartM" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                <option value="00">00</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                        <input type="hidden" name="start_time" x-bind:value="roundStartH + ':' + roundStartM">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">เวลาเลิก</label>
                        <div class="flex gap-1">
                            <select x-model="roundEndH" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                @for($i = 0; $i < 24; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}</option>
                                @endfor
                            </select>
                            <select x-model="roundEndM" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                <option value="00">00</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                        <input type="hidden" name="end_time" x-bind:value="roundEndH + ':' + roundEndM">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">สนาม</label>
                    <select name="court_id"
                        x-bind:value="prefillSession ? (prefillSession.court_id ?? '') : ''"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                        <option value="">- ไม่ระบุ -</option>
                        @isset($courts)
                            @foreach($courts as $court)
                                <option value="{{ $court->id }}">{{ $court->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">จำนวนคนสูงสุด</label>
                        <input type="number" name="max_players" min="1" required
                            x-bind:value="prefillSession ? prefillSession.max_players : 25"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">เครดิต/คน</label>
                        <input type="number" name="credit_cost" min="0" required
                            x-bind:value="prefillSession ? prefillSession.credit_cost : 0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">ยกเลิกจองได้ถึง (ไม่บังคับ)</label>
                    <input type="datetime-local" name="cancel_deadline"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                    <p class="mt-1 text-[11px] text-gray-400">ถ้าไม่กรอก จะยกเลิกจองเองได้ตลอด (ไม่มีเดดไลน์)</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showRoundForm = false" class="text-xs font-medium text-gray-500 hover:text-gray-700 rounded-lg px-4 py-2 cursor-pointer transition">ยกเลิก</button>
                    <button type="submit" class="text-xs font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-lg px-4 py-2 cursor-pointer transition">เปิดรอบ</button>
                </div>
            </form>
        </div>
    </div>

</div>
</div>
@endsection