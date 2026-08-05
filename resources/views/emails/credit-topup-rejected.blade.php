<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำขอเติมเครดิตไม่ผ่านการตรวจสอบ</title>
</head>
<body style="font-family:sans-serif;line-height:1.6;color:#111;">
    <h1>คำขอเติมเครดิตของคุณไม่ผ่านการตรวจสอบ</h1>
    <p>สวัสดีคุณ {{ $topupRequest->user->name }} คำขอเติมเครดิต #{{ $topupRequest->id }} จำนวน
        {{ number_format($topupRequest->price_satang / 100, 2) }} บาท ที่คุณส่งเมื่อ {{ $topupRequest->created_at->format('d/m/Y H:i') }}
        ไม่ผ่านการตรวจสอบจากแอดมิน</p>
    @if($topupRequest->rejected_reason)
        <p><strong>เหตุผล:</strong> {{ $topupRequest->rejected_reason }}</p>
    @endif
    <p>กรุณาตรวจสอบสลิป/ยอดเงินให้ถูกต้องแล้วส่งคำขอใหม่อีกครั้ง หรือติดต่อเจ้าหน้าที่หากคิดว่าเป็นความผิดพลาด</p>
</body>
</html>
