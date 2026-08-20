<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เครดิตของคุณหมดอายุแล้ว</title>
</head>
<body style="font-family:sans-serif;line-height:1.6;color:#111;">
    <h1>เครดิตของคุณหมดอายุแล้ว</h1>
    <p>สวัสดีคุณ {{ $transaction->user->us_name }} เครดิตของคุณครบกำหนดวันหมดอายุแล้ว ระบบจึงตัดยอดที่เหลือทั้งหมดออกโดยอัตโนมัติ รายละเอียดดังนี้</p>
    <table cellpadding="6" style="border-collapse:collapse;">
        <tr><td style="color:#666;">วันที่ตัดยอด</td><td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td style="color:#666;">จำนวนที่ถูกตัด</td><td><strong>-{{ number_format($transaction->amount / 100, 2) }} บาท</strong></td></tr>
        <tr><td style="color:#666;">สาเหตุ</td><td>เครดิตครบกำหนดวันหมดอายุ</td></tr>
        <tr><td style="color:#666;">ยอดเครดิตคงเหลือ</td><td>0.00 บาท</td></tr>
    </table>
    <p style="margin-top:16px;color:#666;font-size:13px;">เติมเครดิตใหม่ได้ทุกเมื่อผ่านหน้าเว็บ หากคิดว่ารายการนี้ไม่ถูกต้อง กรุณาติดต่อเจ้าหน้าที่</p>
</body>
</html>
