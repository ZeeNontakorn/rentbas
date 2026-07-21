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
</head>
<body class="bg-[#ffffff] min-h-screen flex flex-col text-white antialiased">

    {{-- Navbar อยู่ด้านบนสุด --}}
    @include('components.navbar')




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
                once: true,    // เล่นรอบเดียวตอนโหลดเจอ
                offset: 50,    // เลื่อนลงมา 50px ค่อยเล่น
                easing: 'ease-out-cubic'
            });
        });
    </script>

    {{-- SweetAlert2 + Toast (ใช้ร่วมกันทุกหน้าที่ extend layout นี้) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
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

    @stack('scripts')
</body>
</html>
