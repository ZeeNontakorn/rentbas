{{-- resources/views/emails/private-training-rejected.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: 'Sarabun', sans-serif; color: #111827; line-height: 1.7;">
    <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
        <h2 style="color: #e86c2a;">การจอง Private Training ของคุณถูกปฏิเสธ</h2>
        <p>เรียนคุณ {{ $booking->user->name }}</p>
        <p>คำขอจองเทรนเนอร์ส่วนตัวของคุณถูกปฏิเสธ โดยมีรายละเอียดดังนี้</p>

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
            @if($booking->court)
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">สนาม</td>
                    <td style="padding: 8px 0; font-weight: 700;">{{ $booking->court->name }}{{ $booking->courtSection ? ' — '.$booking->courtSection->name : '' }}</td>
                </tr>
            @else
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">สนาม</td>
                    <td style="padding: 8px 0; font-weight: 700;">ยังไม่ได้จัดสนาม</td>
                </tr>
            @endif
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">เหตุผล</td>
                <td style="padding: 8px 0; font-weight: 700;">{{ $reason }}</td>
            </tr>
        </table>

        <p style="color: #6b7280; font-size: 13px;">หากต้องการสอบถามเพิ่มเติม ติดต่อทีมงานได้ที่ 081-246-0000</p>
    </div>
</body>
</html>
