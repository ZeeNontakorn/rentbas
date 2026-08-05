<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำขอเติมเครดิตใหม่</title>
</head>
<body style="font-family:sans-serif;line-height:1.6;color:#111;">
    <h1>มีคำขอเติมเครดิตใหม่รอตรวจสอบ</h1>
    <p>ลูกค้า <strong>{{ $topupRequest->user->name }}</strong> ({{ $topupRequest->user->email }}) แจ้งโอนเงินและแนบสลิปเพื่อขอเติมเครดิต</p>
    <ul>
        <li>คำขอเลขที่: #{{ $topupRequest->id }}</li>
        <li>จำนวนเงินที่แจ้ง: {{ number_format($topupRequest->price_satang / 100, 2) }} บาท</li>
        <li>เครดิตที่จะได้รับ: {{ number_format($topupRequest->credit_satang / 100, 2) }} บาท</li>
        <li>วันที่ส่งคำขอ: {{ $topupRequest->created_at->format('d/m/Y H:i') }}</li>
    </ul>
    <p>กรุณาเข้าสู่ระบบแอดมินเพื่อตรวจสอบสลิปและอนุมัติ/ปฏิเสธคำขอนี้ที่
        <a href="{{ route('admin.credit-topups.show', $topupRequest) }}">หน้าคำขอเติมเครดิตนี้</a>
    </p>
</body>
</html>
