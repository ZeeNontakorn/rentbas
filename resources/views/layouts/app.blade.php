<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'THATA HOMECOURT')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- @stack('styles') {{-- จองที่ไว้สำหรับ CSS เฉพาะหน้า --}} -->
    <style>
        /* ปรับ Font ให้ดูเป็นสไตล์สปอร์ต (ถ้าต้องการ) */
        @import url('https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,900&display=swap');

        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col text-white antialiased">

    {{-- Navbar อยู่ด้านบนสุด --}}
    @include('components.navbar')
    <div id="navbar-spacer"></div>



    {{-- เนื้อหาหลัก --}}
    <main class="flex-1 w-full overflow-x-hidden">
        @yield('content')
    </main>

    {{-- Scripts อื่นๆ --}}
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800, // ความเร็วอนิเมชั่น 0.8 วิ
                once: true, // เล่นรอบเดียวตอนโหลดเจอ
                offset: 50, // เลื่อนลงมา 50px ค่อยเล่น
                easing: 'ease-out-cubic'
            });
        });
    </script>

    {{-- SweetAlert2 + Toast (ใช้ร่วมกันทุกหน้าที่ extend layout นี้) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // แก้บั๊ก sticky navbar กระตุกตอนเปิด SweetAlert2 (heightAuto default ไปเซ็ต height:auto ให้ <html>)
        Swal = Swal.mixin({ heightAuto: false });

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

        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success'))
                Toast.fire({ icon: 'success', title: @json(session('success')) });
            @endif

            @if (session('error'))
                Toast.fire({ icon: 'error', title: @json(session('error')) });
            @endif

            @if ($errors->any())
                Toast.fire({ icon: 'error', title: @json($errors->first()) });
            @endif
        });
    </script>

    {{-- Loading overlay ตอน submit ฟอร์มที่ต้องรอส่งอีเมล (SMTP บางทีตอบช้ามาก) เพื่อไม่ให้ผู้ใช้คิดว่า
         หน้าเว็บค้าง — ใส่ attribute data-loading-form ในฟอร์มไหนก็ได้เพื่อเปิดใช้งาน พร้อม
         data-loading-message กำหนดข้อความเองได้ (ถ้าไม่ใส่จะใช้ข้อความ default) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[data-loading-form]').forEach((form) => {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.loadingSkip === '1') return;
                    if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                        event.preventDefault();
                        return;
                    }

                    const message = form.dataset.loadingMessage || 'กำลังบันทึกข้อมูลและส่งอีเมลแจ้งเตือน อาจใช้เวลาสักครู่ (ไม่ต้องปิดหรือรีเฟรชหน้านี้)...';
                    Swal.fire({
                        title: 'กำลังดำเนินการ',
                        text: message,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => Swal.showLoading(),
                    });
                });
            });
        });
    </script>
    <script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            // หน้านี้ถูกดึงจาก bfcache (กด back/forward) ให้โหลดใหม่จาก server
            // เพื่อไม่ให้ flash message เก่า (เช่น toast แจ้งเตือน) ค้างแสดงซ้ำ
            window.location.reload();
        }
    });
    function syncNavbarSpacer() {
        const nav = document.querySelector('nav.fixed');
        const spacer = document.getElementById('navbar-spacer');
        if (nav && spacer) spacer.style.height = nav.offsetHeight + 'px';
    }
    window.addEventListener('load', syncNavbarSpacer);
    window.addEventListener('resize', syncNavbarSpacer);
    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => setTimeout(syncNavbarSpacer, 50));
</script>
{{-- แทนที่ confirm() ของเบราว์เซอร์ด้วย SweetAlert2 — ใส่ data-confirm="ข้อความ" ในฟอร์มไหนก็ได้
     รองรับ dynamic message ผ่าน JS ก่อน submit ด้วย --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === '1') {
                    return; // ยืนยันแล้ว ปล่อยให้ submit ตามปกติ
                }

                event.preventDefault();

                const message = form.dataset.confirm || 'ยืนยันการทำรายการนี้?';
                const confirmText = form.dataset.confirmButtonText || 'ยืนยัน';
                const danger = form.dataset.confirmDanger === '1';

                Swal.fire({
                    title: 'ยืนยันการทำรายการ',
                    text: message,
                    icon: danger ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: danger ? '#dc2626' : '#ea580c',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                });
            });
        });
    });
</script>
    @stack('scripts')
</body>

</html>
