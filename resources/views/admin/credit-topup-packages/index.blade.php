@extends('layouts.app')

@section('title', 'แพ็กเกจเครดิต')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

        <a href="{{ route('admin.credit-topups.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-orange-500 mb-6 transition font-medium group">
            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับไปหน้าคำขอเติมเครดิต
        </a>

        <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">แพ็กเกจเครดิต &amp; โปรโมชั่น</h1>
        <p class="text-sm text-gray-400 mb-6">กำหนดราคาแพ็กเกจที่ผู้ใช้เลือกได้ในหน้าเติมเครดิต — ถ้าตั้งเครดิตที่ได้รับมากกว่ายอดชำระ ระบบจะถือเป็นโบนัส/โปรโมชั่นให้อัตโนมัติ</p>

        @if (auth()->user()->role === 'superadmin')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <p class="mb-2">ตั้งค่าเบอร์มือถือ PromptPay เพื่อรับเงิน</p>
                <form method="POST" action="{{ route('admin.credit-topup-packages.promptpay') }}">
                    @csrf
                    <div class="flex flex-wrap gap-3 items-start">
                        <div>
                            <input type="tel" name="promptpay_number" value="{{ old('promptpay_number', $promptpayNumber) }}"
                                   placeholder="เช่น 0812345678" maxlength="10" inputmode="numeric"
                                   title="เบอร์มือถือ 10 หลัก ขึ้นต้นด้วย 0"
                                   class="w-48 rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                          {{ $errors->promptpay->has('promptpay_number')
                                              ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                              : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                            @error('promptpay_number', 'promptpay')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <input type="text" name="promptpay_name" value="{{ old('promptpay_name', $promptpayName) }}"
                                   placeholder="ชื่อบัญชี PromptPay (ภาษาไทย)"
                                   title="กรุณากรอกเป็นภาษาไทยเท่านั้น"
                                   class="w-48 rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                          {{ $errors->promptpay->has('promptpay_name')
                                              ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                              : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                            @error('promptpay_name', 'promptpay')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg px-5 py-2 transition whitespace-nowrap">บันทึกข้อมูล PromptPay</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- LINE URL --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-medium text-gray-700 text-sm mb-4">ลิงก์ LINE สำหรับปุ่ม "เติมผ่าน LINE"</h2>
            <form method="POST" action="{{ route('admin.credit-topup-packages.line-url') }}" class="flex flex-col sm:flex-row gap-3 items-start">
                @csrf
                <div class="flex-1">
                    <input type="url" name="line_topup_url" value="{{ old('line_topup_url', $lineUrl) }}" placeholder="https://line.me/R/ti/p/@yourlineid"
                           class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                  {{ $errors->lineUrl->has('line_topup_url')
                                      ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                      : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                    @error('line_topup_url', 'lineUrl')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg px-5 py-2 transition whitespace-nowrap cursor-pointer">บันทึกลิงก์</button>
            </form>
        </div>

        {{-- Add package --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-medium text-gray-700 text-sm mb-4">เพิ่มแพ็กเกจใหม่</h2>
            <form method="POST" action="{{ route('admin.credit-topup-packages.store') }}" class="grid sm:grid-cols-5 gap-3 items-end">
                @csrf
                <input type="hidden" name="sort_order" value="{{ $packages->count() + 1 }}">

                {{-- เติมคลาส relative ที่ div คลุม --}}
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1">ป้ายชื่อ</label>
                    <input type="text" name="label"
                           value="{{ old('label') }}" placeholder="เช่น แพ็กสุดคุ้ม"
                           class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                  {{ $errors->createPackage->has('label')
                                      ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                      : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                    @error('label', 'createPackage')
                        {{-- เติม absolute top-full left-0 ให้ข้อความ error ลอยอยู่ใต้ช่องกรอก --}}
                        <p class="absolute top-full left-0 text-[11px] text-red-600 mt-1 whitespace-nowrap">{{ $message }}</p>
                    @enderror
                </div>

                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1">ราคา (บาท)</label>
                    <input type="number" step="0.01" min="1" name="price" value="{{ old('price') }}" placeholder="เช่น 1000 บาท"
                           class="no-spinner w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                  {{ $errors->createPackage->has('price')
                                      ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                      : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                    @error('price', 'createPackage')
                        <p class="absolute top-full left-0 text-[11px] text-red-600 mt-1 whitespace-nowrap">{{ $message }}</p>
                    @enderror
                </div>

                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1">เครดิตที่ได้ (บาท)</label>
                    <input type="number" step="0.01" min="1" name="credit" value="{{ old('credit') }}" placeholder="เช่น 1000 บาท"
                           class="no-spinner w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                  {{ $errors->createPackage->has('credit')
                                      ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                      : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                    @error('credit', 'createPackage')
                        <p class="absolute top-full left-0 text-[11px] text-red-600 mt-1 whitespace-nowrap">{{ $message }}</p>
                    @enderror
                </div>

                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1">หมดอายุใน (วัน)</label>
                    <input type="number" step="1" min="1" max="3650" name="expiry_days" value="{{ old('expiry_days') }}" placeholder="เว้นว่าง = ไม่หมดอายุ"
                           class="no-spinner w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                  {{ $errors->createPackage->has('expiry_days')
                                      ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                      : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                    @error('expiry_days', 'createPackage')
                        <p class="absolute top-full left-0 text-[11px] text-red-600 mt-1 whitespace-nowrap">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-5 py-2 transition cursor-pointer">+ เพิ่ม</button>
            </form>
        </div>

       {{-- Package list --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 font-medium text-center">ลำดับ</th>
                            <th class="px-6 py-3 font-medium text-center">ป้ายชื่อ</th>
                            <th class="px-6 py-3 font-medium text-center">ราคา</th>
                            <th class="px-6 py-3 font-medium text-center">เครดิตที่ได้</th>
                            <th class="px-6 py-3 font-medium text-center">หมดอายุ (วัน)</th>
                            <th class="px-6 py-3 font-medium text-center">โบนัส</th>
                            <th class="px-6 py-3 font-medium text-center">แสดงผล</th>
                            <th class="px-6 py-3 font-medium text-right w-32">
                                <button type="button" id="saveAllBtn" disabled
                                        class="w-24 text-center text-xs font-medium rounded-lg py-2 transition normal-case tracking-normal bg-gray-200 text-gray-400 cursor-not-allowed">
                                    บันทึกแล้ว
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="packageRows">
                        @forelse($packages as $pkg)
                            @php
                                $rowErrors = $errors->{"editPkg{$pkg->id}"};
                                $rowLabel = $rowErrors->any() ? old('label', $pkg->label) : $pkg->label;
                                $rowPrice = $rowErrors->any() ? old('price', $pkg->price_satang / 100) : $pkg->price_satang / 100;
                                $rowCredit = $rowErrors->any() ? old('credit', $pkg->credit_satang / 100) : $pkg->credit_satang / 100;
                            @endphp
                            <tr data-id="{{ $pkg->id }}">
                                <td class="px-3 py-3 text-center">
                                    <span class="drag-handle inline-flex items-center justify-center w-7 h-7 rounded text-gray-300 hover:text-gray-500 hover:bg-gray-100 cursor-grab active:cursor-grabbing select-none" title="ลากเพื่อจัดลำดับ">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <input form="editPkg{{ $pkg->id }}" type="text" name="label" value="{{ $rowLabel }}"
                                           class="w-24 rounded border px-2 py-1 text-sm text-center {{ $rowErrors->has('label') ? 'border-red-400 bg-red-50/40' : 'border-gray-200' }}">
                                    @error('label', "editPkg{$pkg->id}")
                                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <input form="editPkg{{ $pkg->id }}" type="number" step="0.01" min="1" name="price" value="{{ $rowPrice }}"
                                           class="no-spinner w-24 rounded border px-2 py-1 text-sm text-center {{ $rowErrors->has('price') ? 'border-red-400 bg-red-50/40' : 'border-gray-200' }}">
                                    @error('price', "editPkg{$pkg->id}")
                                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <input form="editPkg{{ $pkg->id }}" type="number" step="0.01" min="1" name="credit" value="{{ $rowCredit }}"
                                           class="no-spinner w-24 rounded border px-2 py-1 text-sm text-center {{ $rowErrors->has('credit') ? 'border-red-400 bg-red-50/40' : 'border-gray-200' }}">
                                    @error('credit', "editPkg{$pkg->id}")
                                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-6 py-3 text-center">
                                    @php $rowExpiryDays = $rowErrors->any() ? old('expiry_days', $pkg->expiry_days) : $pkg->expiry_days; @endphp
                                    <input form="editPkg{{ $pkg->id }}" type="number" step="1" min="1" max="3650" name="expiry_days" value="{{ $rowExpiryDays }}" placeholder="ไม่หมดอายุ"
                                           class="no-spinner w-24 rounded border px-2 py-1 text-sm text-center {{ $rowErrors->has('expiry_days') ? 'border-red-400 bg-red-50/40' : 'border-gray-200' }}">
                                    @error('expiry_days', "editPkg{$pkg->id}")
                                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-6 py-3 text-center text-amber-600 font-medium bonus-cell">
                                    @if($pkg->bonus_satang > 0) +฿{{ number_format($pkg->bonus_satang / 100, 0) }} @else — @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <input type="hidden" form="editPkg{{ $pkg->id }}" name="sort_order" value="{{ $pkg->sort_order }}">

                                    {{-- เปลี่ยนเป็น Toggle Switch สีเขียว Emerald --}}
                                    <div class="flex justify-center">
                                        <label class="relative inline-flex cursor-pointer items-center">
                                            <input type="hidden" form="editPkg{{ $pkg->id }}" name="is_active" value="0">
                                            <input form="editPkg{{ $pkg->id }}" type="checkbox" name="is_active" value="1" {{ $pkg->is_active ? 'checked' : '' }} class="peer sr-only">
                                            <span class="relative h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-emerald-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] peer-checked:after:translate-x-5"></span>
                                        </label>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-center whitespace-nowrap">
                                    <button type="button" onclick="confirmDeletePackage('{{ $pkg->id }}', '{{ addslashes($pkg->label) }}')" class="rounded-lg px-4 py-1 text-sm text-red-500 transition-colors duration-150 hover:bg-red-100 cursor-pointer">ลบ</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-10 text-center text-gray-400">ยังไม่มีแพ็กเกจ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ย้าย <form> ทั้งหมดมาไว้ด้านนอกตารางตรงนี้ --}}
        @foreach($packages as $pkg)
            <form id="editPkg{{ $pkg->id }}" method="POST" action="{{ route('admin.credit-topup-packages.update', $pkg) }}" class="hidden">
                @csrf
                @method('PUT')
            </form>
            <form id="deletePkg{{ $pkg->id }}" method="POST" action="{{ route('admin.credit-topup-packages.destroy', $pkg) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <div id="pageToast" class="hidden fixed bottom-6 right-6 bg-gray-900 text-white text-sm px-4 py-2.5 rounded-lg shadow-lg z-50"></div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
    // ─── Toast แจ้งผลลัพธ์แบบไม่บล็อกหน้าจอ (รูปแบบเดียวกับหน้าตั้งค่าราคา) ───
    function showToast(text, isError) {
        const toast = document.getElementById('pageToast');
        if (!toast) return;
        toast.textContent = text;
        toast.classList.remove('hidden', 'bg-gray-900', 'bg-red-600');
        toast.classList.add(isError ? 'bg-red-600' : 'bg-gray-900');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(() => toast.classList.add('hidden'), 2200);
    }

    document.addEventListener('DOMContentLoaded', function () {
        // ─── ข้อผิดพลาดจาก server ตอนโหลดหน้า: หลายบรรทัด/สำคัญกว่า จึงใช้ modal แทน toast ที่หายไปเร็วเกินจะอ่านทัน ───
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'ทำรายการไม่สำเร็จ',
                html: @js('• ' . implode('<br>• ', $errors->all())),
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'เข้าใจแล้ว',
            });
        @endif
    });
</script>
<script>
(function () {
    const tbody = document.getElementById('packageRows');
    if (!tbody || typeof Sortable === 'undefined') return;

    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'opacity-40',
        // เฉพาะแถวที่มี data-id (กัน empty-state row หลุดเข้ามาโดนลากด้วย)
        filter: 'tr:not([data-id])',
        onEnd: function () {
            const order = Array.from(tbody.querySelectorAll('tr[data-id]')).map(tr => parseInt(tr.dataset.id, 10));
            if (order.length === 0) return;

            fetch('{{ route('admin.credit-topup-packages.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value,
                },
                body: JSON.stringify({ order }),
            }).then(res => {
                if (!res.ok) throw new Error('reorder failed');
                showToast('บันทึกลำดับใหม่แล้ว');
            }).catch(err => {
                console.error(err);
                showToast('บันทึกลำดับไม่สำเร็จ กำลังโหลดหน้าใหม่...', true);
                setTimeout(() => window.location.reload(), 1200);
            });
        }
    });
})();
</script>

{{-- Bulk save for the package list rows --}}
<script>
(function () {
    const saveBtn = document.getElementById('saveAllBtn');
    const rows = Array.from(document.querySelectorAll('#packageRows tr[data-id]'));
    if (!saveBtn || rows.length === 0) return;

    const SAVED_CLASSES = ['bg-gray-200', 'text-gray-400', 'cursor-not-allowed'];
    const DIRTY_CLASSES = ['bg-emerald-500', 'hover:bg-emerald-600', 'text-white', 'cursor-pointer'];

    const FIELD_DIRTY_CLASSES = ['border-yellow-400', 'ring-2', 'ring-yellow-200'];

    function fieldsOf(row) {
        return {
            label: row.querySelector('input[name="label"]'),
            price: row.querySelector('input[name="price"]'),
            credit: row.querySelector('input[name="credit"]'),
            expiry_days: row.querySelector('input[name="expiry_days"]'),
            is_active: row.querySelector('input[type="checkbox"][name="is_active"]'),
        };
    }

    function readRowState(row) {
        const f = fieldsOf(row);
        return {
            label: f.label ? f.label.value : '',
            price: f.price ? f.price.value : '',
            credit: f.credit ? f.credit.value : '',
            expiry_days: f.expiry_days ? f.expiry_days.value : '',
            is_active: f.is_active ? f.is_active.checked : false,
        };
    }

    // ราคา/เครดิตในช่องกรอกเป็นหน่วยบาทอยู่แล้ว
    function calcBonusText(priceVal, creditVal) {
        const price = parseFloat(priceVal) || 0;
        const credit = parseFloat(creditVal) || 0;
        const diff = credit - price;
        if (diff > 0) {
            return '+฿' + diff.toLocaleString('en-US', { maximumFractionDigits: 0 });
        }
        return '—';
    }

    function updateBonusCell(row, state) {
        const bonusCell = row.querySelector('.bonus-cell');
        if (bonusCell) bonusCell.textContent = calcBonusText(state.price, state.credit);
    }

    function setFieldDirty(input, dirty) {
        if (!input) return;
        if (dirty) {
            input.classList.remove('border-gray-200', 'border-red-400', 'bg-red-50/40');
            input.classList.add(...FIELD_DIRTY_CLASSES);
        } else {
            input.classList.remove(...FIELD_DIRTY_CLASSES);
            input.classList.add('border-gray-200');
        }
    }

    function refreshFieldHighlight(row) {
        const cur = readRowState(row);
        const init = row._initial;
        const f = fieldsOf(row);
        setFieldDirty(f.label, cur.label !== init.label);
        setFieldDirty(f.price, cur.price !== init.price);
        setFieldDirty(f.credit, cur.credit !== init.credit);
        setFieldDirty(f.expiry_days, cur.expiry_days !== init.expiry_days);
        setFieldDirty(f.is_active, cur.is_active !== init.is_active);
    }

    function clearRowHighlight(row) {
        const f = fieldsOf(row);
        Object.values(f).forEach(input => setFieldDirty(input, false));
    }

    // Snapshot the values currently on the page as the "unchanged" baseline.
    rows.forEach(row => { row._initial = readRowState(row); });

    function rowIsDirty(row) {
        const cur = readRowState(row);
        const init = row._initial;
        return cur.label !== init.label
            || cur.price !== init.price
            || cur.credit !== init.credit
            || cur.expiry_days !== init.expiry_days
            || cur.is_active !== init.is_active;
    }

    function refreshSaveButton() {
        const dirty = rows.some(rowIsDirty);
        saveBtn.disabled = !dirty;
        saveBtn.textContent = dirty ? 'บันทึก' : 'บันทึกแล้ว';
        saveBtn.classList.remove(...SAVED_CLASSES, ...DIRTY_CLASSES);
        saveBtn.classList.add(...(dirty ? DIRTY_CLASSES : SAVED_CLASSES));
    }

    rows.forEach(row => {
        row.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => { refreshFieldHighlight(row); refreshSaveButton(); });
            input.addEventListener('change', () => { refreshFieldHighlight(row); refreshSaveButton(); });
        });
    });

    refreshSaveButton();

    saveBtn.addEventListener('click', async function () {
        const dirtyRows = rows.filter(rowIsDirty);
        if (dirtyRows.length === 0) return;

        saveBtn.disabled = true;
        saveBtn.textContent = 'กำลังบันทึก...';

        try {
            await Promise.all(dirtyRows.map(row => {
                const form = document.getElementById('editPkg' + row.dataset.id);
                const formData = new FormData(form);
                return fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                }).then(res => {
                    if (!res.ok) throw new Error('save failed for package ' + row.dataset.id);
                });
            }));

            // อัปเดตค่า baseline, คำนวณโบนัสใหม่ และล้างเส้นขอบสีเหลืองของช่องที่เพิ่งบันทึกไป
            dirtyRows.forEach(row => {
                row._initial = readRowState(row);
                updateBonusCell(row, row._initial);
                clearRowHighlight(row);
            });

            // เรียกใช้ Toast มุมขวาบน สำหรับตอนกดบันทึกสำเร็จ
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

        } catch (err) {
            console.error(err);

            // เรียกใช้ Toast มุมขวาบน สำหรับตอนเกิดข้อผิดพลาด
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'บันทึกไม่สำเร็จ กรุณาลองใหม่',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

        } finally {
            refreshSaveButton();
        }
    });
})();

function confirmDeletePackage(packageId, packageLabel) {
    Swal.fire({
        title: 'ยืนยันลบแพ็กเกจนี้ใช่ไหม?',
        text: `เมื่อลบแพ็กเกจ "${packageLabel}" แล้วจะไม่สามารถกู้คืนข้อมูลได้`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#3085d6',
        reverseButtons: true,
        confirmButtonText: 'ยืนยันการลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deletePkg' + packageId).submit();
        }
    });
}
</script>
@endpush
@endsection
