{{--
    Overlay โหลดแบบเต็มจอ ใช้กับทุก action ที่มีการส่งอีเมล (synchronous ->send() ตรงๆ เพราะโฮสติ้ง
    นี้ไม่มี queue worker ให้ใช้ — ดูรายละเอียดใน App\Mail\CreditTopupRequestedMail) ระหว่างรอ SMTP
    ตอบกลับ หน้าเว็บจะค้างสักครู่ จึงต้องมี overlay กันผู้ใช้กดซ้ำ/งงว่าเว็บค้าง

    วิธีใช้: @include('components.mail-loading-overlay')
    แล้วเรียก showMailLoadingOverlay('ข้อความที่จะแสดง') ตอน form submit (ดู onsubmit handler
    ในแต่ละหน้าที่ include ไฟล์นี้)
--}}
<div id="mailLoadingOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] hidden items-center justify-center px-4">
    <div class="bg-white rounded-2xl px-8 py-7 flex flex-col items-center gap-4 shadow-2xl max-w-[300px] text-center">
        <svg class="animate-spin w-9 h-9 text-emerald-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <p id="mailLoadingText" class="text-sm font-medium text-gray-700 leading-relaxed" style="font-family:'Sarabun','Kanit',sans-serif;">
            กำลังดำเนินการและส่งอีเมลแจ้งเตือน...
        </p>
        <p class="text-xs text-gray-400" style="font-family:'Sarabun','Kanit',sans-serif;">อาจใช้เวลาสักครู่ กรุณาอย่าปิดหน้านี้</p>
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
