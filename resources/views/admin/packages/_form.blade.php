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
    </div>

    <!-- รูปภาพแพ็กเกจ -->
    <div>
        <label for="image" class="mb-1.5 block text-sm font-medium text-gray-700">รูปภาพแพ็กเกจ</label>

        @if(isset($package) && $package->image)
            <div class="mb-3 flex items-center gap-3">
                <img id="currentImagePreview" src="{{ asset('storage/' . $package->image) }}" alt="{{ $package->name }}"
                     class="h-20 w-20 rounded-lg border border-gray-200 object-cover">
                <span class="text-xs text-gray-400">รูปปัจจุบัน — เลือกไฟล์ใหม่เพื่อแทนที่</span>
            </div>
        @else
            <img id="currentImagePreview" src="" alt="" class="mb-3 hidden h-20 w-20 rounded-lg border border-gray-200 object-cover">
        @endif

        <input type="file" id="image" name="image" accept="image/*" onchange="previewPackageImage(event)"
               class="block w-full rounded-lg border border-gray-300 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-orange-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-orange-600 hover:file:bg-orange-100">
        <p class="mt-1.5 text-xs text-gray-400">รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 2MB</p>
        @error('image')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

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
        <button type="submit" class="rounded-lg bg-orange-500 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-orange-600">
            {{ isset($package) ? 'บันทึกการแก้ไข' : 'เพิ่มแพ็กเกจ' }}
        </button>
    </div>
</form>

<script>
function previewPackageImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('currentImagePreview');
    if (!file || !preview) return;
    preview.src = URL.createObjectURL(file);
    preview.classList.remove('hidden');
}
</script>
