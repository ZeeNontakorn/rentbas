<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองถูกยกเลิก</title>
</head>
<body style="font-family:sans-serif;line-height:1.6;color:#111;">
    <h1>การจองของคุณถูกยกเลิกโดยระบบ</h1>
    <p>สวัสดี {{ $booking->user->name }},</p>
    <p>ขออภัยในความไม่สะดวก การจองสนาม <strong>{{ $booking->court->name }}</strong> ของคุณถูกยกเลิกโดยผู้ดูแลระบบ</p>
    <ul>
        <li>วันที่: {{ $booking->booking_date->format('Y-m-d') }}</li>
        <li>เวลา: {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}</li>
        <li>เหตุผล: {{ $reason }}</li>
    </ul>
    <p>หากมีข้อสงสัยเกี่ยวกับการยกเลิกนี้ กรุณาติดต่อผู้ดูแลระบบ หรือทำการจองใหม่ได้ที่ <a href="{{ url('/') }}">เว็บไซต์ของเรา</a></p>
    <p>ขออภัยในความไม่สะดวกมา ณ ที่นี้</p>
</body>
</html>
