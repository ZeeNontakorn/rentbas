<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เครดิตของคุณใกล้หมดอายุ</title>
</head>
<body style="font-family:sans-serif;line-height:1.6;color:#111;">
    <h1>เครดิตของคุณใกล้หมดอายุ</h1>
    <p>สวัสดีคุณ {{ $user->us_name }}</p>
    <p>เครดิตคงเหลือของคุณ <strong>{{ number_format($user->credit_balance / 100, 2) }} บาท</strong>
        จะหมดอายุในวันที่ <strong>{{ $user->credit_expires_at->format('d/m/Y') }}</strong></p>
    <p>กรุณาใช้เครดิตของคุณก่อนวันหมดอายุ เครดิตที่หมดอายุแล้วจะไม่สามารถใช้งานหรือกู้คืนได้</p>
</body>
</html>
