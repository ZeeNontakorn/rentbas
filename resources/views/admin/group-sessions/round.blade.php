@extends('layouts.app')

@section('title', $round->title)

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

    {{-- กลับหน้ารายการ --}}
    <a
        href="{{ route('admin.group-sessions.index') }}"
        class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition"
    >
        &larr; กลับไปหน้ารายการรอบ
    </a>

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-2 mb-6">

        <div class="min-w-0">
            <h1 class="text-2xl font-semibold text-gray-900 break-words">
                {{ $round->title }}
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                {{ $round->play_date->format('d/m/Y') }}
                &middot;
                {{ \Carbon\Carbon::parse($round->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($round->end_time)->format('H:i') }}

                @if($round->court)
                    &middot; {{ $round->court->name }}
                @endif

                &middot; เครดิต {{ $round->credit_cost }}/คน
            &middot;
            @if($round->cancel_deadline)
                ยกเลิกได้ถึง {{ $round->cancel_deadline->format('d/m/Y H:i') }} น.
            @else
                ไม่มีกำหนดเวลายกเลิก
            @endif
            </p>
        </div>

        {{-- ปุ่มจัดการรอบ --}}
        <div class="flex flex-wrap gap-2 shrink-0">

            {{-- ปิด / เปิดรับสมัคร --}}
            @if($round->status === 'open')

                <form
                    action="{{ route('admin.group-sessions.rounds.close', $round) }}"
                    method="POST"
                >
                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="px-3 py-1.5 text-sm border border-orange-200 text-orange-600 rounded-lg hover:bg-orange-50 transition"
                    >
                        ปิดรับสมัคร
                    </button>
                </form>

            @else

                <form
                    action="{{ route('admin.group-sessions.rounds.reopen', $round) }}"
                    method="POST"
                >
                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                    >
                        เปิดรับสมัครอีกครั้ง
                    </button>
                </form>

            @endif

            {{-- ยกเลิกรอบ --}}
            <form
            action="{{ route('admin.group-sessions.rounds.cancel', $round) }}"
            method="POST"
            data-confirm="ยกเลิกรอบนี้และคืนเครดิตให้ทุกคนที่ลงชื่อไว้?"
            data-confirm-button-text="ยืนยันยกเลิก"
            data-confirm-danger="1"
                >
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition"
                >
                    ยกเลิกรอบ
                </button>
            </form>

        </div>
    </div>


    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3">
            {{ session('success') }}
        </div>
    @endif


    {{-- Error Message --}}
    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">

            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach

        </div>
    @endif


    {{-- จำนวนผู้สมัคร --}}
    <div class="flex items-center justify-between mb-3">

        <p class="text-sm text-gray-600">
            ลงชื่อแล้ว

            <span class="font-semibold text-gray-900">
                {{ $round->confirmedSignups->count() }}
            </span>

            /

            <span class="font-semibold text-gray-900">
                {{ $round->max_players }}
            </span>

            คน
        </p>

    </div>


    {{-- เพิ่มคนเข้ารอบ --}}
    @if($round->status === 'open')

        <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-4">

            <p class="mb-2 text-sm font-semibold text-orange-900">
                เพิ่มผู้จองผ่าน LINE
            </p>

            <p class="mb-3 text-xs text-orange-700">
                เลือกสมาชิกเพื่อเพิ่มและตัดเครดิต
                หรือกรอกชื่อผู้จองภายนอก
                (ไม่ตัดเครดิต)
            </p>


            <form
                action="{{ route('admin.group-sessions.rounds.addPlayer', $round) }}"
                method="POST"
                class="flex flex-col lg:flex-row gap-2"
                onsubmit="return this.user_id.value || this.guest_name.value.trim() !== '' ? true : (alert('เลือกสมาชิก หรือกรอกชื่อผู้จองภายนอกอย่างใดอย่างหนึ่ง'), false)"
            >

                @csrf

                {{-- เลือกสมาชิก --}}
                <div x-data="memberPicker({{ Js::from($members) }})" class="relative flex-1 min-w-0">
    <input
        type="text"
        x-model="query"
        @focus="open = true"
        @input="open = true; selectedId = null"
        placeholder="พิมพ์ชื่อ, อีเมล หรือเบอร์โทร เพื่อค้นหาสมาชิก"
        autocomplete="off"
        class="w-full border border-gray-300 bg-white text-gray-900 placeholder-gray-400 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
    >
    <input type="hidden" name="user_id" :value="selectedId">

    {{-- รายชื่อที่ค้นเจอ --}}
    <div
        x-show="open && filtered.length > 0"
        x-cloak
        @click.outside="open = false"
        class="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg"
    >
        <template x-for="m in filtered" :key="m.id">
            <button
                type="button"
                @click="select(m)"
                class="w-full text-left px-3 py-2 text-sm hover:bg-orange-50 border-b border-gray-50 last:border-0"
            >
                <div class="text-gray-900 font-medium" x-text="m.us_name || '(ไม่มีชื่อ)'"></div>
                <div class="text-gray-500 text-xs" x-text="m.email + (m.phone ? ' · ' + m.phone : '')"></div>
            </button>
        </template>
    </div>

    {{-- ไม่พบผลลัพธ์ --}}
    <div
        x-show="open && query.length > 0 && filtered.length === 0"
        x-cloak
        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2 text-sm text-gray-400"
    >
        ไม่พบสมาชิกที่ตรงกับ "<span x-text="query"></span>"
    </div>
</div>


                {{-- หรือ --}}
                <span class="hidden lg:flex items-center justify-center text-xs text-gray-500 px-1">
                    หรือ
                </span>


                {{-- Guest --}}
                <input
                    type="text"
                    name="guest_name"
                    maxlength="255"
                    placeholder="ชื่อผู้จองภายนอก"
                    class="flex-1 min-w-0 border border-gray-300 bg-white text-gray-900 placeholder-gray-400 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                >


                {{-- ปุ่มเพิ่ม --}}
                <button
                    type="submit"
                    class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition whitespace-nowrap"
                >
                    + เพิ่มผู้จอง
                </button>

            </form>

        </div>

    @endif


    {{-- รายชื่อคนลงเล่น --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        {{-- รองรับหน้าจอเล็ก --}}
        <div class="overflow-x-auto">

            <table class="w-full min-w-[1000px] text-sm">

                {{-- Header ตาราง --}}
                <thead>

                    <tr class="text-left text-gray-500 border-b border-gray-100 bg-gray-50">

                        <th class="px-5 py-3 font-medium w-16">
                            ลำดับ
                        </th>

                        <th class="px-5 py-3 font-medium min-w-[300px]">
                            ชื่อ
                        </th>

                        <th class="px-5 py-3 font-medium whitespace-nowrap">
                            เวลาลงชื่อ
                        </th>

                        <th class="px-5 py-3 font-medium whitespace-nowrap">
                            เครดิตที่ใช้
                        </th>

                        <th class="px-5 py-3 font-medium min-w-[180px]">
                            เพิ่มโดย
                        </th>

                        <th class="px-5 py-3 font-medium text-right whitespace-nowrap w-28">
                            จัดการ
                        </th>

                    </tr>

                </thead>


                {{-- Body --}}
                <tbody class="divide-y divide-gray-50">

                    @php $reserveDividerShown = false; @endphp
@forelse($round->confirmedSignups as $signup)
    @if($signup->is_reserve && !$reserveDividerShown)
        @php $reserveDividerShown = true; @endphp
        <tr>
            <td colspan="6" class="px-5 py-2 text-center text-xs text-amber-600 bg-amber-50">
                — ตัวจริงครบแล้ว รายชื่อต่อไปนี้คือคิวสำรอง —
            </td>
        </tr>
    @endif

                        @php
                            $playerName = $signup->user
                            ? ($signup->user->name ?: $signup->user->email)
                            : ($signup->guest_name ?? 'ผู้จองภายนอก');

                            $addedByAccount = $signup->addedBy ?? $signup->bookedBy;
                            $addedByName = $addedByAccount
                                ? $addedByAccount->name.' ('.$addedByAccount->email.')'
                                : 'ลงชื่อเอง';
                        @endphp


                        <tr
                            @class([
                                'bg-amber-50/50' => $signup->order_number > $round->max_players
                            ])
                        >

                            {{-- ลำดับ --}}
                            <td class="px-5 py-3 font-semibold text-gray-900 align-middle">
                                {{ $signup->order_number }}
                            </td>


                            {{-- ชื่อ --}}
                            <td class="px-5 py-3 text-gray-900 align-middle">

                                <div class="max-w-[320px] truncate" title="{{ $playerName }}">
    {{ $playerName }}
    @if($signup->is_reserve)
        <span class="ml-1 px-1.5 py-0.5 rounded text-xs bg-amber-100 text-amber-700">สำรอง</span>
    @endif
</div>

                            </td>


                            {{-- เวลาลงชื่อ --}}
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap align-middle">

                                {{ $signup->signed_up_at->format('d/m H:i:s') }}

                            </td>


                            {{-- เครดิต --}}
                            <td class="px-5 py-3 text-gray-600 whitespace-nowrap align-middle">

                                {{ $signup->credit_used }}

                            </td>


                            {{-- เพิ่มโดย --}}
                            <td class="px-5 py-3 text-gray-500 align-middle">

                                <div
                                    class="max-w-[180px] truncate"
                                    title="{{ $addedByName }}"
                                >
                                    {{ $addedByName }}
                                </div>

                            </td>


                            {{-- จัดการ --}}
                            <td class="px-5 py-3 text-right whitespace-nowrap align-middle">

                                <form
                                    action="{{ route('admin.group-sessions.rounds.removePlayer', [$round, $signup]) }}"
                                    method="POST"
                                    data-confirm="นำ {{ $playerName }} ออกจากรอบ{{ $signup->credit_used > 0 ? ' และคืนเครดิต '.$signup->credit_used.' หน่วย' : '' }}?"
                                    data-confirm-button-text="นำออก"
                                    data-confirm-danger="1"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-red-500 hover:text-red-700 font-medium transition"
                                    >
                                        นำออก
                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-5 py-8 text-center text-gray-400"
                            >
                                ยังไม่มีคนลงชื่อ
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>
@endsection
@push('scripts')
<script>
    function memberPicker(members) {
        return {
            members: members,
            query: '',
            open: false,
            selectedId: null,
            get filtered() {
                const q = this.query.trim().toLowerCase();
                if (!q) return this.members.slice(0, 20);
                return this.members.filter(m => {
                    return (m.us_name || '').toLowerCase().includes(q)
                        || (m.email || '').toLowerCase().includes(q)
                        || (m.phone || '').toLowerCase().includes(q);
                }).slice(0, 20);
            },
            select(m) {
                this.selectedId = m.id;
                this.query = (m.us_name || '(ไม่มีชื่อ)') + ' (' + m.email + ')';
                this.open = false;
            }
        }
    }
</script>
@endpush