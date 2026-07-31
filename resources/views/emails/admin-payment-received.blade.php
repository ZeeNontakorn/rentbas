<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งเตือนการชำระเงิน</title>
</head>
<body style="font-family:sans-serif;line-height:1.6;color:#111;">
    <h1>มีการชำระเงินเข้ามาใหม่</h1>
    <p>{{ $refType }} <strong>#{{ $refId }}</strong> ได้รับการชำระเงินเรียบร้อยแล้ว</p>
    <ul>
        <li>ลูกค้า: {{ $customerName }}</li>
        <li>รายละเอียด: {{ $detailLine }}</li>
        <li>ยอดชำระ: {{ number_format($amountSatang / 100, 2) }} บาท</li>
        <li>ช่องทางชำระเงิน: {{ $paymentMethod === 'credit' ? 'หักเครดิต' : 'PromptPay' }}</li>
    </ul>
    <p>เข้าสู่ระบบแอดมินเพื่อดูรายละเอียดเพิ่มเติมได้ที่ <a href="{{ url('/admin') }}">หลังบ้าน</a></p>
</body>
</html>
