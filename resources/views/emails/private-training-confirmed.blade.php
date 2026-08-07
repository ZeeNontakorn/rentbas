{{-- resources/views/emails/private-training-confirmed.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: 'Sarabun', sans-serif; color: #111827; line-height: 1.7;">
    <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
        <h2 style="color: #e86c2a;">ยืนยันการจอง Private Training</h2>
        <p>เรียนคุณ {{ $booking->user->name }}</p>
        <p>การจองเทรนเนอร์ส่วนตัวของคุณได้รับการยืนยันแล้ว รายละเอียดดังนี้</p>

        <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">โค้ช</td>
                <td style="padding: 8px 0; font-weight: 700;">{{ $booking->coach->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">วันที่</td>
                <td style="padding: 8px 0; font-weight: 700;">{{ $booking->date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">เวลา</td>
                <td style="padding: 8px 0; font-weight: 700;">{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} น.</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">สนาม</td>
                <td style="padding: 8px 0; font-weight: 700;">{{ $booking->court->name }} — {{ $booking->courtSection->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">การชำระเงิน</td>
                <td style="padding: 8px 0; font-weight: 700;">
                    {{ $booking->payment_status === 'paid_by_package' ? 'ใช้สิทธิ์จากแพ็กเกจ' : number_format($booking->price / 100, 2) . ' บาท (หักจากเครดิต)' }}
                </td>
            </tr>
        </table>

        <p style="color: #6b7280; font-size: 13px;">หากมีข้อสงสัย ติดต่อทีมงานได้ที่ 081-246-0000</p>
    </div>
</body>
</html>
