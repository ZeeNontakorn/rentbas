{{--
    Overlay โหลดแบบเต็มจอ ใช้กับทุก action ที่มีการส่งอีเมล (synchronous ->send() ตรงๆ เพราะโฮสติ้ง
    นี้ไม่มี queue worker ให้ใช้ — ดูรายละเอียดใน App\Mail\CreditTopupRequestedMail) ระหว่างรอ SMTP
    ตอบกลับ หน้าเว็บจะค้างสักครู่ จึงต้องมี overlay กันผู้ใช้กดซ้ำ/งงว่าเว็บค้าง

    วิธีใช้: @include('components.mail-loading-overlay')
    แล้วเรียก showMailLoadingOverlay('ข้อความที่จะแสดง') ตอน form submit (ดู onsubmit handler
    ในแต่ละหน้าที่ include ไฟล์นี้)
--}}
<div id="mailLoadingOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] hidden items-center justify-center px-4 transition-all">
    
    {{-- ขยายความกว้างกล่องเป็น max-w-md (448px) พร้อมเพิ่ม Padding p-8 sm:p-10 --}}
    <div class="bg-white rounded-3xl p-8 sm:p-10 flex flex-col items-center gap-5 shadow-2xl w-full max-w-md text-center border border-gray-100">
        
        {{-- ขยายขนาดวงกลมหมุน (Spinner) จาก w-9 h-9 เป็น w-14 h-14 --}}
        <svg class="animate-spin w-14 h-14 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>

        {{-- ขยายตัวหนังสือข้อความหลัก จาก text-sm เป็น text-base sm:text-lg และปรับความหนาเป็น font-semibold --}}
        <p id="mailLoadingText" class="text-base sm:text-lg font-semibold text-gray-800 leading-relaxed" style="font-family:'Sarabun','Kanit',sans-serif;">
            กำลังดำเนินการและส่งอีเมลแจ้งเตือน...
        </p>

        {{-- ขยายตัวหนังสือคำอธิบายด้านล่าง จาก text-xs เป็น text-sm --}}
        <p class="text-xs sm:text-sm text-gray-400 font-normal" style="font-family:'Sarabun','Kanit',sans-serif;">
            อาจใช้เวลาสักครู่ กรุณาอย่าปิดหน้านี้
        </p>
    </div>
</div>

<script>
    function showMailLoadingOverlay(text) {
        const overlay = document.getElementById('mailLoadingOverlay');
        if (!overlay) return;
        const textEl = document.getElementById('mailLoadingText');
        if (textEl && text) textEl.textContent = text;
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    }
</script>