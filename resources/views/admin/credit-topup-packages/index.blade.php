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

        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
                @foreach ($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
            </div>
        @endif
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
            <h2 class="font-medium text-gray-700 text-sm mb-4">ลิงก์ LINE สำหรับปุ่ม "เติมผ่าน LINE ไวกว่า"</h2>
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
                <button type="submit" class="text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg px-5 py-2 transition whitespace-nowrap">บันทึกลิงก์</button>
            </form>
        </div>

        {{-- Add package --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-medium text-gray-700 text-sm mb-4">เพิ่มแพ็กเกจใหม่</h2>
            <form method="POST" action="{{ route('admin.credit-topup-packages.store') }}" class="grid sm:grid-cols-4 gap-3 items-end">
                @csrf
                <input type="hidden" name="sort_order" value="{{ $packages->count() + 1 }}">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ป้ายชื่อ</label>
                    <input type="text" name="label" required
                           value="{{ old('label') }}" placeholder="เช่น แพ็กสุดคุ้ม"
                           class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                  {{ $errors->createPackage->has('label')
                                      ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                      : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                    @error('label', 'createPackage')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ราคา (บาท)</label>
                    <input type="number" step="0.01" min="1" name="price" required value="{{ old('price') }}" placeholder="เช่น 1000 บาท"
                           class="no-spinner w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                  {{ $errors->createPackage->has('price')
                                      ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                      : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                    @error('price', 'createPackage')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">เครดิตที่ได้ (บาท)</label>
                    <input type="number" step="0.01" min="1" name="credit" required value="{{ old('credit') }}" placeholder="เช่น 1000 บาท"
                           class="no-spinner w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2
                                  {{ $errors->createPackage->has('credit')
                                      ? 'border-red-400 focus:ring-red-500/20 focus:border-red-500 bg-red-50/40'
                                      : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
                    @error('credit', 'createPackage')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-5 py-2 transition">+ เพิ่ม</button>
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
                            <th class="px-6 py-3 font-medium text-center">โบนัส</th>
                            <th class="px-6 py-3 font-medium text-center">แสดงผล</th>
                            <th class="px-6 py-3 font-medium"></th>
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
                                <td class="px-6 py-3 text-center text-amber-600 font-medium">
                                    @if($pkg->bonus_satang > 0) +฿{{ number_format($pkg->bonus_satang / 100, 0) }} @else — @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <input type="hidden" form="editPkg{{ $pkg->id }}" name="sort_order" value="{{ $pkg->sort_order }}">
                                    <input form="editPkg{{ $pkg->id }}" type="checkbox" name="is_active" value="1" {{ $pkg->is_active ? 'checked' : '' }} class="accent-emerald-500 w-4 h-4">
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <button type="submit" form="editPkg{{ $pkg->id }}" class="text-emerald-600 hover:text-emerald-700 font-medium text-xs mr-3">บันทึก</button>
                                    <form method="POST" action="{{ route('admin.credit-topup-packages.destroy', $pkg) }}" class="inline" onsubmit="return confirm('ลบแพ็กเกจนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-600 font-medium text-xs">ลบ</button>
                                    </form>
                                </td>
                            </tr>
                            <form id="editPkg{{ $pkg->id }}" method="POST" action="{{ route('admin.credit-topup-packages.update', $pkg) }}" class="hidden">
                                @csrf
                                @method('PUT')
                            </form>
                        @empty
                            <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">ยังไม่มีแพ็กเกจ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div id="reorderToast" class="hidden fixed bottom-6 right-6 bg-gray-900 text-white text-sm px-4 py-2.5 rounded-lg shadow-lg z-50"></div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const tbody = document.getElementById('packageRows');
    if (!tbody || typeof Sortable === 'undefined') return;

    const toast = document.getElementById('reorderToast');
    function showToast(text, isError) {
        toast.textContent = text;
        toast.classList.remove('hidden', 'bg-gray-900', 'bg-red-600');
        toast.classList.add(isError ? 'bg-red-600' : 'bg-gray-900');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(() => toast.classList.add('hidden'), 2200);
    }

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
@endpush
@endsection
