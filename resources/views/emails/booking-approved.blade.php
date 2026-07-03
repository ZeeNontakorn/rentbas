<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองได้รับการอนุมัติ</title>
</head>
<body style="font-family:sans-serif;line-height:1.6;color:#111;">
    <h1>การจองของคุณได้รับการอนุมัติแล้ว</h1>
    <p>สวัสดี {{ $booking->user->name }},</p>
    <p>การจองสนาม <strong>{{ $booking->court->name }}</strong> ได้รับการอนุมัติเรียบร้อยแล้ว</p>
    <ul>
        <li>วันที่: {{ $booking->booking_date->format('Y-m-d') }}</li>
        <li>เวลา: {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}</li>
    </ul>
    <p>ขอบคุณที่ใช้บริการระบบจองของเรา หากคุณต้องการดูรายละเอียดเพิ่มเติมสามารถเข้าไปที่หน้า <a href="{{ url('/') }}">เว็บไซต์ของเรา</a></p>
    <p>ขอให้คุณมีความสุขกับการใช้งาน!</p>
</body>
</html>
