@extends('layouts.app')

@section('title', 'จัดการสถานะสนาม')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="bg-[#f8f9fe] min-h-screen text-[#111827] pb-10">

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');


            .bk-main h1,
            .bk-main h2,
            .bk-main h3 {
                font-family: 'Kanit', sans-serif;
            }

            /* Slot CSS */
            .slot-card {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 12px 10px;
                text-align: center;
                background: #fff;
                cursor: pointer;
                transition: all .2s;
            }

            .slot-card.selected {
                border: 2px solid #87D068 !important;
                padding: 11px 9px;
            }

            .slot-time {
                font-family: 'Kanit', sans-serif;
                font-size: 13px;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 8px;
            }

            .slot-btn {
                width: 100%;
                border-radius: 4px;
                font-size: 13px;
                font-weight: 600;
                padding: 8px 0;
            }

            /* States */
            .slot-card.available .slot-btn {
                background: #f3f4f6;
                color: #4b5563;
            }

            .slot-card.unavailable .slot-btn {
                background: #ff0000;
                color: #fff;
            }

            .slot-card.maintenance .slot-btn {
                background: #d4e100;
                color: #111827;
            }

            .slot-card.booking_pending .slot-btn {
                background: #fef3c7;
                color: #92400e;
            }

            .slot-card.booking_approved .slot-btn {
                background: #ff0000;
                color: #fff;
            }

            .slot-card.booked .slot-btn {
                background: #ff0000;
                color: #fff;
            }

            .court-item {
                padding: 8px 12px;
                cursor: pointer;
                transition: background 0.2s;
                font-family: 'Kanit', sans-serif;
            }

            .court-item:hover {
                background: #f9fafb;
                border-color: #e5e7eb;
            }

            .court-item.active {
                border: 1px solid #87D068;
                font-weight: bold;
            }

            #courtModal input:invalid,
            #courtModal select:invalid {
                border-color: #ef4444;
                box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.15);
            }

            #courtModal input:focus:invalid,
            #courtModal select:focus:invalid {
                border-color: #ef4444;
                box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.18);
            }
        </style>

        <div class="bk-main max-w-[1200px] mx-auto px-4 py-8">

            <div class="mb-6 flex justify-between items-end">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <a href="{{ route('admin.dashboard') }}"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-300 hover:text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">จัดการสนาม</h1>
                    </div>
                    <p class="text-gray-600 text-[15px]">แก้ไขข้อมูลสนาม และสถานะสนาม</p>
                </div>
                <button type="button" onclick="openCourtModal()"
                    class="text-sm border border-gray-300 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2M12 5v14m7-7H5"/></svg>
                    เพิ่มสนาม
                </button>
            </div>

            <div class="flex flex-col lg:flex-row gap-6 mt-8">

                {{-- LEFT COLUMN --}}
                <div class="w-full lg:w-[280px] flex-shrink-0 flex flex-col gap-6">

                    {{-- BOX 1: เลือกสนาม --}}
                    <div
                        class="relative z-3 border border-gray-200 bg-white rounded-lg p-5 flex items-center justify-between">
                        <span class="font-bold text-[15px] text-gray-900">1. เลือกสนาม</span>

                        <div class="relative w-[120px]">
                            <div class="border border-gray-300 rounded px-3 py-1 flex items-center justify-between cursor-pointer bg-white text-[14px]"
                                onclick="document.getElementById('courtList').classList.toggle('hidden')">
                                <span class="truncate"> {{ $selectedCourt?->name ?? 'เลือกสนาม' }}</span>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <div id="courtList"
                                class="hidden absolute top-full mt-1 left-0 w-[220px] max-h-[380px] overflow-y-auto overflow-x-hidden bg-white border border-gray-200 rounded-md shadow-xl z-50 flex flex-col">
                                @foreach ($courts as $court)
                                    <div
                                        class="flex items-center justify-between gap-2 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-50 last:border-0 {{ $selectedCourt?->id == $court->id ? 'font-bold bg-gray-50 text-[#87D068]' : '' }}">
                                        <a href="{{ route('admin.courts', ['court_id' => $court->id, 'date' => $date]) }}"
                                            class="truncate flex-1 text-left">
                                            <span class="inline-flex items-center gap-2">
                                                <span
                                                    class="inline-block w-2.5 h-2.5 rounded-full {{ $court->court_status === 'open' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                                <span>{{ $court->name }}</span>
                                            </span>
                                        </a>
                                        <button type="button"
                                            class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-md border border-gray-200 text-gray-500 hover:bg-white hover:text-gray-900 transition"
                                            onclick="event.preventDefault(); event.stopPropagation(); openCourtModal('edit', {{ $court->id }}, @js($court->name), '{{ $court->court_status }}')"
                                            title="แก้ไขสนาม">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path
                                                    d="M22.94,1.061c-1.368-1.367-3.76-1.365-5.124,0L1.611,17.265c-1.039,1.04-1.611,2.421-1.611,3.89v2.346c0,.276,.224,.5,.5,.5H2.846c1.47,0,2.851-.572,3.889-1.611L22.86,6.265c.579-.581,.953-1.262,1.08-1.972,.216-1.202-.148-2.381-1-3.232ZM6.028,21.682c-.85,.851-1.979,1.318-3.182,1.318H1v-1.846c0-1.202,.468-2.332,1.318-3.183L15.292,4.999l3.709,3.709L6.028,21.682ZM22.956,4.116c-.115,.642-.5,1.138-.803,1.441l-2.444,2.444-3.709-3.709,2.525-2.525c.986-.988,2.718-.99,3.709,0,.617,.617,.88,1.473,.723,2.349Z" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- BOX 2: เลือกวันที่ --}}
                    @php
                        $cDate = \Carbon\Carbon::parse($date);
                        $thMonthsFull = [
                            '',
                            'มกราคม',
                            'กุมภาพันธ์',
                            'มีนาคม',
                            'เมษายน',
                            'พฤษภาคม',
                            'มิถุนายน',
                            'กรกฎาคม',
                            'สิงหาคม',
                            'กันยายน',
                            'ตุลาคม',
                            'พฤศจิกายน',
                            'ธันวาคม',
                        ];
                    @endphp
                    <div class="border border-gray-200 bg-white rounded-lg p-5 flex flex-col shadow-sm">
                        <span class="font-bold text-[15px] text-gray-900 mb-5 block">2. เลือกวันที่</span>
                        <div
                            class="relative w-full rounded border border-[#87a0ff] p-[1px] mb-2 focus-within:border-[#5271ff] focus-within:ring-1 focus-within:ring-[#5271ff]">
                            <span
                                class="absolute -top-2.5 left-2 bg-white px-1 text-[10px] font-bold text-[#5271ff] tracking-wider">Date</span>
                            <form id="dateForm" method="GET" action="{{ route('admin.courts') }}">
                                <input type="hidden" name="court_id" value="{{ $selectedCourt?->id }}">
                                <input type="date" name="date" value="{{ $date }}"
                                    onchange="document.getElementById('dateForm').submit()"
                                    class="w-full text-sm text-gray-700 p-2 outline-none bg-transparent">
                            </form>
                        </div>
                        <!-- Note: Native datepicker popup forms the pseudo-calendar seen in mockups -->
                    </div>

                </div>

                {{-- RIGHT COLUMN --}}
                <div class="flex-1 flex flex-col gap-6">

                    {{-- BOX 3: เลือกเวลา --}}
                    <div class="border border-gray-300 bg-white rounded-lg p-6 min-h-[400px]">
                        <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-4">
                            <span class="font-bold text-[16px] text-gray-900">3. เลือกเวลา - {{ $cDate->day }}
                                {{ $thMonthsFull[$cDate->month] }} {{ $cDate->year }}</span>
                            <span class="font-bold text-[14px] text-gray-900">เวลาปัจจุบัน <span
                                    id="currentClock">{{ now()->format('H:i') }}</span> น.</span>
                        </div>

                        @if (!$selectedCourt)
                            <div class="text-center py-20 text-gray-400 font-medium">กรุณาเลือกสนาม</div>
                        @else
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-6">
                                @foreach ($slots as $slot)
                                    @php
                                        $sClass = $slot['status']; // available, unavailable, maintenance, booked
                                        $sLabel = match ($slot['status']) {
                                            'available' => 'ว่าง',
                                            'unavailable' => 'ไม่ว่าง',
                                            'maintenance' => 'ปิดปรับปรุง',
                                            'booking_pending' => 'รออนุมัติ (จอง)',
                                            'booking_approved' => 'ถูกจอง (Booking)',
                                            'booked' => 'ถูกจอง (Booking)',
                                            default => 'ว่าง',
                                        };
                                    @endphp

                                    <div class="slot-card {{ $sClass }}"
                                        onclick="selectAdminTime('{{ $slot['start'] }}', '{{ $slot['end'] }}', '{{ $slot['status'] }}', this)">
                                        <div class="slot-time">{{ $slot['label'] }}</div>
                                        <div class="slot-btn">{{ $sLabel }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Action Box (Fixed bottom or static) --}}
                    <div id="statusBox"
                        class="hidden border-2 border-gray-200 bg-white rounded-lg p-8 shadow-2xl fixed bottom-8 left-1/2 -translate-x-1/2 w-[90%] max-w-[800px] z-50 flex flex-col items-start gap-6">
                        <div class="flex justify-between items-center w-full">
                            <span class="font-bold text-[16px] text-gray-900">เปลี่ยนสถานะ: <span id="s_label"
                                    class="text-gray-500 ml-2 font-normal"></span></span>
                            <button
                                onclick="document.getElementById('statusBox').classList.add('hidden'); if(selEl) { selEl.classList.remove('selected'); }"
                                class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-full p-1 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form id="slotForm" method="POST" action="{{ route('admin.courts.slot') }}"
                            class="flex flex-col sm:flex-row gap-6 w-full">
                            @csrf
                            <input type="hidden" name="court_id" value="{{ $selectedCourt?->id }}">
                            <input type="hidden" name="date" value="{{ $date }}">
                            <input type="hidden" name="start_time" id="st_val">
                            <input type="hidden" name="end_time" id="en_val">

                            <button type="submit" name="status" value="available"
                                class="flex-1 bg-[#eeeeee] hover:bg-gray-300 text-gray-800 font-medium py-3 px-6 rounded-lg transition text-[15px] shadow-sm">
                                ว่าง
                            </button>
                            <button type="submit" name="status" value="unavailable"
                                class="flex-1 bg-[#ff0000] hover:bg-red-700 text-white font-medium py-3 px-6 rounded-lg transition text-[15px] shadow-sm">
                                ไม่ว่าง
                            </button>
                            <button type="submit" name="status" value="maintenance"
                                class="flex-1 bg-[#dcd700] hover:bg-[#c2bd00] text-gray-900 font-medium py-3 px-6 rounded-lg transition text-[15px] shadow-sm">
                                ปิดปรับปรุง
                            </button>
                        </form>

                    </div>

                </div>
            </div>
        </div>

        <div id="courtModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-gray-200 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 id="courtModalTitle" class="text-lg font-bold text-gray-900">เพิ่มสนาม</h2>
                        <p id="courtModalSubtitle" class="text-sm text-gray-500">กรอกชื่อและสถานะสนาม</p>
                    </div>
                    <button type="button" onclick="closeCourtModal()"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="courtForm" method="POST" action="{{ route('admin.court.create') }}" novalidate
                    class="px-6 py-5 space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="court_form_method" value="">
                    <input type="hidden" name="court_id" id="court_id" value="{{ old('court_id') }}">
                    <input type="hidden" name="return_date" value="{{ $date }}">
                    <input type="hidden" name="return_court_id" value="{{ $selectedCourt?->id }}">
                    <div>
                        <label for="court_name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อสนาม</label>
                        <input id="court_name" name="name" type="text" required maxlength="255"
                            value="{{ old('name') }}"
                            class="w-full rounded-lg border px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#5271ff]/20 outline-none {{ $errors->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 focus:border-[#5271ff]' }}"
                            placeholder="เช่น สนาม 1">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $errors->first('name') }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="court_status" class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                        <select id="court_status" name="court_status"
                            class="w-full rounded-lg border px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#5271ff]/20 outline-none {{ $errors->has('court_status') ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 focus:border-[#5271ff]' }}">
                            <option value="open" @selected(old('court_status', 'open') === 'open')>เปิดใช้งาน</option>
                            <option value="closed" @selected(old('court_status') === 'closed')>ปิดใช้งาน</option>
                        </select>
                        @error('court_status')
                            <p class="mt-1 text-xs text-red-600">{{ $errors->first('court_status') }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" onclick="closeCourtModal()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition">ยกเลิก</button>
                        <button type="submit"
                            class="rounded-lg bg-[#5271ff] px-4 py-2 text-sm font-medium text-white hover:bg-[#3f5ee8] transition"
                            id="courtModalSubmit">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script>
                let selEl = null;

                // ----- SweetAlert2 Toast (มุมขวาบน, ปิดเองอัตโนมัติ) -----
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });

                document.addEventListener('DOMContentLoaded', function() {
                    // Toast ที่ถูกฝากไว้ก่อนเปลี่ยนหน้า (จากฟอร์ม AJAX เช่น สร้าง/แก้ไขสนาม)
                    try {
                        const pending = sessionStorage.getItem('pendingToast');
                        if (pending) {
                            sessionStorage.removeItem('pendingToast');
                            Toast.fire(JSON.parse(pending));
                        }
                    } catch (e) {}

                    @if (session('success'))
                        Toast.fire({
                            icon: 'success',
                            title: @js(session('success'))
                        });
                    @endif

                    @if (session('error'))
                        Toast.fire({
                            icon: 'error',
                            title: @js(session('error'))
                        });
                    @endif

                    @if ($errors->any())
                        Toast.fire({
                            icon: 'error',
                            title: @js($errors->first())
                        });
                    @endif
                });

                function openCourtModal(mode = 'create', courtId = null, courtName = '', courtStatus = 'open') {
                    const modal = document.getElementById('courtModal');
                    const form = document.getElementById('courtForm');
                    const courtIdInput = document.getElementById('court_id');
                    const methodInput = document.getElementById('court_form_method');
                    const title = document.getElementById('courtModalTitle');
                    const subtitle = document.getElementById('courtModalSubtitle');
                    const submit = document.getElementById('courtModalSubmit');
                    const nameInput = document.getElementById('court_name');
                    const statusInput = document.getElementById('court_status');

                    if (!modal) {
                        return;
                    }

                    // เคลียร์ error message และ error style เก่าทุกครั้งที่เปิด modal
                    clearFormErrors();

                    if (mode === 'edit' && courtId) {
                        if (form) {
                            form.action = `{{ url('/admin/courts') }}/${courtId}`;
                        }
                        if (methodInput) {
                            methodInput.value = 'PUT';
                        }
                        if (courtIdInput) {
                            courtIdInput.value = courtId;
                        }
                        if (title) {
                            title.innerText = 'แก้ไขสนาม';
                        }
                        if (subtitle) {
                            subtitle.innerText = 'อัปเดตชื่อและสถานะสนาม';
                        }
                        if (submit) {
                            submit.innerText = 'บันทึกการแก้ไข';
                        }
                        if (nameInput) {
                            nameInput.value = courtName;
                        }
                        if (statusInput) {
                            statusInput.value = courtStatus;
                        }
                    } else {
                        if (form) {
                            form.action = '{{ route('admin.court.create') }}';
                        }
                        if (methodInput) {
                            methodInput.value = '';
                        }
                        if (courtIdInput) {
                            courtIdInput.value = '';
                        }
                        if (title) {
                            title.innerText = 'เพิ่มสนาม';
                        }
                        if (subtitle) {
                            subtitle.innerText = 'กรอกชื่อและสถานะสนาม';
                        }
                        if (submit) {
                            submit.innerText = 'บันทึก';
                        }
                        // reset ค่าฟอร์มกลับเป็นค่าว่างเสมอตอนเปิดโหมด "เพิ่มสนาม"
                        if (nameInput) {
                            nameInput.value = '';
                        }
                        if (statusInput) {
                            statusInput.value = 'open';
                        }
                    }

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');

                    if (nameInput && !nameInput.value) {
                        nameInput.focus();
                    }
                }

                function closeCourtModal() {
                    const modal = document.getElementById('courtModal');
                    if (!modal) {
                        return;
                    }

                    const form = document.getElementById('courtForm');
                    const methodInput = document.getElementById('court_form_method');
                    if (form) {
                        form.action = '{{ route('admin.court.create') }}';
                    }
                    if (methodInput) {
                        methodInput.value = '';
                    }

                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                document.getElementById('courtModal')?.addEventListener('click', function(event) {
                    if (event.target === this) {
                        closeCourtModal();
                    }
                });

                // ----- Error helpers -----
                const fieldErrorClasses = ['border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20'];
                const fieldDefaultClasses = {
                    court_name: ['border-gray-300', 'focus:border-[#5271ff]'],
                    court_status: ['border-gray-300', 'focus:border-[#5271ff]'],
                };

                function clearFormErrors() {
                    const form = document.getElementById('courtForm');
                    if (!form) return;
                    form.querySelectorAll('[data-error-for]').forEach(el => el.remove());
                    Object.keys(fieldDefaultClasses).forEach(id => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        el.classList.remove(...fieldErrorClasses);
                        el.classList.add(...fieldDefaultClasses[id]);
                    });
                }

                function setFieldError(fieldName, message) {
                    const inputId = fieldName === 'name' ? 'court_name' : (fieldName === 'court_status' ? 'court_status' : null);
                    if (!inputId) return;
                    const el = document.getElementById(inputId);
                    if (!el) return;

                    el.classList.remove(...(fieldDefaultClasses[inputId] || []));
                    el.classList.add(...fieldErrorClasses);

                    const p = document.createElement('p');
                    p.className = 'mt-1 text-xs text-red-600';
                    p.setAttribute('data-error-for', fieldName);
                    p.innerText = message;
                    el.insertAdjacentElement('afterend', p);
                }

                // ----- AJAX submit (ไม่ปิด modal / ไม่ reload หน้าเวลา validate ไม่ผ่าน) -----
                document.getElementById('courtForm')?.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const form = this;
                    const submitBtn = document.getElementById('courtModalSubmit');
                    const originalText = submitBtn ? submitBtn.innerText : '';

                    clearFormErrors();
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerText = 'กำลังบันทึก...';
                        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                    }

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST', // _method ข้างในสั่ง spoof เป็น PUT ให้ตอน edit
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });

                        if (res.status === 422) {
                            const data = await res.json();
                            Object.entries(data.errors || {}).forEach(([field, messages]) => {
                                setFieldError(field, messages[0]);
                            });
                            Toast.fire({
                                icon: 'error',
                                title: 'กรุณาตรวจสอบข้อมูลให้ครบถ้วน'
                            });
                            return; // ✅ modal ไม่ปิด ไม่ reload — แค่โชว์ error ตรงช่อง
                        }

                        if (!res.ok) {
                            Toast.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'
                            });
                            return;
                        }

                        const data = await res.json();
                        // ฝาก toast ไว้ใน sessionStorage ก่อนเปลี่ยนหน้า เพราะ response แบบ JSON
                        // ฝั่ง server ไม่ได้ flash session('success') ให้ (flash จะมีเฉพาะ response แบบ redirect ปกติ)
                        try {
                            sessionStorage.setItem('pendingToast', JSON.stringify({
                                icon: 'success',
                                title: data.message || 'บันทึกข้อมูลเรียบร้อยแล้ว'
                            }));
                        } catch (e) {}
                        // สำเร็จ ค่อยพาไปหน้าที่อัปเดตแล้ว (modal จะปิดเพราะเปลี่ยนหน้า)
                        window.location.href = data.redirect || window.location.href;
                    } catch (err) {
                        Toast.fire({
                            icon: 'error',
                            title: 'เชื่อมต่อไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่'
                        });
                    } finally {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerText = originalText;
                            submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                        }
                    }
                });

                function selectAdminTime(start, end, status, el) {
                    const applySelection = () => {
                        if (selEl) {
                            selEl.classList.remove('selected');
                        }

                        el.classList.add('selected');
                        selEl = el;

                        document.getElementById('st_val').value = start;
                        document.getElementById('en_val').value = end;
                        document.getElementById('s_label').innerText = start.substring(0, 5) + ' - ' + end
                            .substring(0, 5);

                        document.getElementById('statusBox').classList.remove('hidden');
                    };

                    // Check if it's booked by user, maybe warn before allowing override
                    if (status.includes('book')) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'ยืนยันการแก้ไขสถานะ?',
                            text: 'ช่วงเวลานี้มีการจองโดยผู้ใช้งานอยู่แล้ว ต้องการแก้ไขสถานะทับซ้อนหรือไม่? (ระบบจะนับเฉพาะสถานะใหม่ที่คุณเลือก)',
                            showCancelButton: true,
                            confirmButtonText: 'ยืนยัน',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#5271ff',
                            cancelButtonColor: '#6b7280',
                            reverseButtons: true,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                applySelection();
                            }
                        });
                        return;
                    }

                    applySelection();
                }

                setInterval(() => {
                    let d = new Date();
                    let clock = document.getElementById('currentClock');
                    if (clock) {
                        clock.innerText = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2,
                            '0');
                    }
                }, 60000);
            </script>
        @endpush
    @endsection
