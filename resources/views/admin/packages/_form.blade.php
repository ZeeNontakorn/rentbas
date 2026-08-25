<form action="{{ isset($package) ? route('admin.packages.update', $package) : route('admin.packages.store') }}"
      method="POST" enctype="multipart/form-data"
      class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    @if(isset($package))
        @method('PUT')
    @endif

    <!-- ชื่อแพ็กเกจ -->
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">ชื่อแพ็กเกจ <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $package->name ?? '') }}" required
               placeholder="เช่น แพ็กเกจ 10 ครั้ง"
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 transition focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">
        @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <!-- รายละเอียด -->
    <div>
        <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700">รายละเอียด</label>
        <textarea id="description" name="description" rows="4"
                  placeholder="อธิบายรายละเอียดของแพ็กเกจนี้ (ไม่บังคับ)"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 transition focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">{{ old('description', $package->description ?? '') }}</textarea>
        @error('description')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <!-- วันที่สามารถใช้แพ็กเกจได้ -->
    <div>
        <label class="mb-2 block text-sm font-medium text-gray-700">
            วันที่สามารถใช้แพ็กเกจได้
        </label>
        <p class="mb-2.5 text-xs text-gray-400">ไม่เลือกวันใดเลย = ใช้ได้ทุกวัน</p>
        <div class="flex flex-wrap gap-2">
            @foreach(['mon'=>'จ','tue'=>'อ','wed'=>'พ','thu'=>'พฤ','fri'=>'ศ','sat'=>'ส','sun'=>'อา'] as $value => $label)
                <label class="cursor-pointer">
                    <input type="checkbox" name="usable_days[]" value="{{ $value }}" class="peer sr-only"
                           {{ in_array($value, old('usable_days', $package->usable_days ?? [])) ? 'checked' : '' }}>
                    <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 px-2 text-sm text-gray-600 transition peer-checked:border-orange-500 peer-checked:bg-orange-500 peer-checked:text-white">
                        {{ $label }}
                    </span>
                </label>
            @endforeach
        </div>
        @error('usable_days')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
        @error('usable_days.*')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <!-- ราคา -->
        <div>
            <label for="price" class="mb-1.5 block text-sm font-medium text-gray-700">ราคา (บาท) <span class="text-red-500">*</span></label>
            <input type="number" id="price" name="price" step="0.01" min="0" value="{{ old('price', $package->price ?? '') }}" required
                   placeholder="0.00"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 transition focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">
            @error('price')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <!-- จำนวนครั้งที่ใช้ได้ -->
        <div>
            <label for="num_of_use" class="mb-1.5 block text-sm font-medium text-gray-700">จำนวนครั้งที่ใช้ได้ <span class="text-red-500">*</span></label>
            <input type="number" id="num_of_use" name="num_of_use" min="0" value="{{ old('num_of_use', $package->num_of_use ?? 0) }}" required
                   placeholder="0"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 transition focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">
            @error('num_of_use')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <!-- จำนวนวัน (ไม่บังคับ) -->
        <div>
            <label for="day" class="mb-1.5 block text-sm font-medium text-gray-700">จำนวนวัน</label>
            <input type="number" id="day" name="day" min="0" value="{{ old('day', $package->day ?? '') }}"
                   placeholder="ไม่บังคับ"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 transition focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">
            @error('day')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="type" class="mb-1.5 block text-sm font-medium text-gray-700">ประเภทแพ็กเกจ <span class="text-red-500">*</span></label>
            <select id="type" name="type" required
                    class="cursor-pointer w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 transition focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">
                <option value="private" {{ old('type', $package->type ?? 'private') == 'private' ? 'selected' : '' }}>เทรนเนอร์ส่วนตัว</option>
            </select>
            @error('type')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <!-- รูปภาพแพ็กเกจ -->
    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
            <!-- สามารถเปลี่ยนไอคอน หรือใส่เป็นตัวเลข Step แบบเดิมได้ -->
            <b class="grid h-7 w-7 place-items-center rounded-lg bg-orange-50 text-sm text-orange-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </b>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">รูปภาพแพ็กเกจ</h2>
                <p class="text-xs text-slate-500">ไม่บังคับ, 1 ภาพ</p>
            </div>
        </div>

        <div id="dropzone"
            class="relative cursor-pointer overflow-hidden rounded-lg border-2 border-dashed border-slate-300 text-center transition-colors duration-150 hover:border-blue-400 hover:bg-blue-50/40">
            
            <div id="dropzone-empty" class="px-6 py-8 {{ isset($package) && $package->image ? 'hidden' : '' }}">
                <svg class="mx-auto mb-2 h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3" />
                </svg>
                <p class="text-sm text-slate-500"><span class="font-medium text-blue-600">คลิกเพื่อเลือกภาพ</span> หรือลากไฟล์มาวางที่นี่</p>
                <p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP ไม่เกิน 2MB</p>
            </div>
            
            <div id="dropzone-preview" class="relative {{ isset($package) && $package->image ? '' : 'hidden' }}">
                <img id="img-preview" src="{{ isset($package) && $package->image ? asset('storage/' . $package->image) : '' }}" class="h-40 w-full object-cover">
                <div class="group absolute inset-0 flex items-center justify-center bg-black/0 transition hover:bg-black/40">
                    <span class="text-sm font-medium text-white opacity-0 transition group-hover:opacity-100">คลิกเพื่อเปลี่ยนภาพ</span>
                </div>
            </div>
            
            <!-- คง onchange ของเดิมไว้เผื่อมีการเขียน JS ดักไว้ -->
            <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewPackageImage(event)">
        </div>
        
        <p id="image_error" class="hidden mt-1.5 text-xs text-red-600">รองรับเฉพาะไฟล์ JPG, PNG, WEBP และต้องมีขนาดไม่เกิน 2MB</p>
        
        @error('image')
            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
        @enderror

        <div class="mt-2 flex items-center gap-3">
            <p id="imageName" class="text-xs text-slate-500 {{ isset($package) && $package->image ? '' : 'hidden' }}">
                {{ isset($package) && $package->image ? 'ใช้ภาพเดิมของแพ็กเกจนี้' : '' }}
            </p>
            
            @if(isset($package))
                <button type="button" id="remove-image-btn"
                    class="{{ $package->image ? '' : 'hidden' }} rounded-lg border border-red-200 bg-white px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50 cursor-pointer">
                    ลบภาพ
                </button>
                <!-- เก็บค่าสำหรับการลบรูปภาพ -->
                <input type="hidden" name="remove_image" id="remove_image_input" value="0">
            @endif
        </div>
    </section>

    <!-- สถานะการใช้งาน -->
    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-slate-50 px-4 py-3.5">
        <div>
            <p class="text-sm font-medium text-gray-700">เปิดใช้งานแพ็กเกจนี้</p>
            <p class="text-xs text-gray-400">แพ็กเกจที่ปิดใช้งานจะไม่แสดงให้ผู้ใช้เลือกซื้อ</p>
        </div>
        <label class="relative inline-flex cursor-pointer items-center">
            <input type="checkbox" name="is_active" value="1" class="peer sr-only"
                   {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}>
            <div class="peer h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-orange-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] peer-checked:after:translate-x-5"></div>
        </label>
    </div>

    <!-- ปุ่มดำเนินการ -->
    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
        <a href="{{ route('admin.packages.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
            ยกเลิก
        </a>
        <button type="submit" class="rounded-lg bg-orange-500 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-orange-600 cursor-pointer">
            {{ isset($package) ? 'บันทึกการแก้ไข' : 'เพิ่มแพ็กเกจ' }}
        </button>
    </div>
</form>

<script>
// 1. อัปเดตฟังก์ชันเดิมให้รองรับโครงสร้าง HTML ใหม่
function previewPackageImage(event) {
    const file = event.target.files[0];
    if (!file) return;

    const imgPreview = document.getElementById('img-preview');
    const emptyState = document.getElementById('dropzone-empty');
    const previewState = document.getElementById('dropzone-preview');
    const imageName = document.getElementById('imageName');
    const removeBtn = document.getElementById('remove-image-btn');
    const removeInput = document.getElementById('remove_image_input');

    // แสดงรูปพรีวิว
    if (imgPreview) imgPreview.src = URL.createObjectURL(file);

    // สลับหน้าตา Dropzone (ซ่อนกล่องว่าง แสดงกล่องพรีวิว)
    if (emptyState) emptyState.classList.add('hidden');
    if (previewState) previewState.classList.remove('hidden');
    
    // แสดงชื่อไฟล์และปุ่มลบ
    if (imageName) {
        imageName.textContent = file.name;
        imageName.classList.remove('hidden');
    }
    if (removeBtn) removeBtn.classList.remove('hidden');
    if (removeInput) removeInput.value = '0';
}

// 2. จัดการการคลิกที่กล่อง Dropzone และปุ่มลบรูปภาพ
document.addEventListener('DOMContentLoaded', function () {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('image');
    const removeBtn = document.getElementById('remove-image-btn');

    // กดที่กล่องเพื่อเปิดเลือกไฟล์
    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());
    }

    // กดปุ่มลบรูปภาพ
    if (removeBtn) {
        removeBtn.addEventListener('click', function (e) {
            e.stopPropagation(); // ป้องกันการเปิดกล่องเลือกไฟล์ซ้ำ
            if (fileInput) fileInput.value = '';
            
            document.getElementById('dropzone-empty')?.classList.remove('hidden');
            document.getElementById('dropzone-preview')?.classList.add('hidden');
            document.getElementById('imageName')?.classList.add('hidden');
            removeBtn.classList.add('hidden');
            
            const removeInput = document.getElementById('remove_image_input');
            if (removeInput) removeInput.value = '1';
        });
    }
});
</script>
