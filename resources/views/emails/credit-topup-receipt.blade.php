@php
    $methodLabel = [
        'promptpay' => 'โอนผ่าน PromptPay (แนบสลิปผ่านหน้าเว็บ)',
        'line' => 'เติมผ่าน LINE',
        'cash_counter' => 'เงินสดหน้าเคาน์เตอร์',
    ][$transaction->payment_method] ?? null;
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จเติมเครดิต</title>
</head>
<body style="font-family:sans-serif;line-height:1.6;color:#111;">
    <h1>เติมเครดิตสำเร็จ</h1>
    <p>สวัสดีคุณ {{ $transaction->user->us_name }} เครดิตของคุณถูกเติมเรียบร้อยแล้ว ใบเสร็จมีรายละเอียดดังนี้</p>
    <table cellpadding="6" style="border-collapse:collapse;">
        <tr><td style="color:#666;">เลขที่รายการ</td><td>#{{ $transaction->id }}</td></tr>
        <tr><td style="color:#666;">วันที่ทำรายการ</td><td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td style="color:#666;">จำนวนเงินที่เติม</td><td><strong>{{ number_format($transaction->amount / 100, 2) }} บาท</strong></td></tr>
        @if($methodLabel)
            <tr><td style="color:#666;">ช่องทางชำระเงิน</td><td>{{ $methodLabel }}</td></tr>
        @endif
        @if($transaction->processed_by_name)
            <tr><td style="color:#666;">ดำเนินการโดย</td><td>{{ $transaction->processed_by_name }}</td></tr>
        @endif
        <tr><td style="color:#666;">ยอดเครดิตคงเหลือหลังทำรายการ</td><td>{{ number_format($transaction->balance_after / 100, 2) }} บาท</td></tr>
        @if($transaction->note)
            <tr><td style="color:#666;">หมายเหตุ</td><td>{{ $transaction->note }}</td></tr>
        @endif
    </table>
    <p style="margin-top:16px;color:#666;font-size:13px;">อีเมลฉบับนี้ออกโดยระบบอัตโนมัติ หากมีข้อสงสัยเกี่ยวกับรายการนี้ กรุณาติดต่อเจ้าหน้าที่</p>
</body>
</html>
