<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองถูกยกเลิก</title>
    <style>
        body { font-family: 'Sarabun', Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 32px auto; background: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }

        .header { background: #0b0b1a; padding: 28px 32px 22px; text-align: center; }
        .header .brand { color: #ffffff; margin: 0; font-size: 20px; font-weight: 800; letter-spacing: 0.5px; }
        .header .sub { color: rgba(255,255,255,0.6); margin: 4px 0 0; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; }

        .status-bar { background: #fef2f2; border-bottom: 1px solid #fecaca; padding: 16px 32px; text-align: center; }
        .status-bar .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #ef4444; margin-right: 6px; }
        .status-bar span.label { color: #b91c1c; font-weight: 700; font-size: 14px; }

        .body { padding: 26px 32px 8px; }
        .greet { color: #374151; font-size: 14px; margin: 0 0 6px; }
        .lead { color: #6b7280; font-size: 13px; margin: 0 0 20px; line-height: 1.6; }

        .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .detail-table td { padding: 10px 0; font-size: 14px; border-bottom: 1px solid #f1f3f5; vertical-align: top; }
        .detail-table td.k { color: #9ca3af; width: 38%; }
        .detail-table td.v { color: #111827; font-weight: 700; text-align: right; }
        .detail-table tr:last-child td { border-bottom: none; }

        .reason-box { background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 8px; padding: 14px 18px; margin: 4px 0 22px; }
        .reason-box p { color: #b91c1c; font-size: 13px; margin: 0; line-height: 1.6; }
        .reason-box p strong { color: #7f1d1d; }

        .cta { text-align: center; margin-bottom: 8px; }
        .cta a { display: inline-block; background: #0b0b1a; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 8px; }

        .footer { background: #f9fafb; padding: 20px 32px; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <p class="brand">THATA HOMECOURT</p>
            <p class="sub">แจ้งเตือนการจอง</p>
        </div>

        <div class="status-bar">
            <span class="dot"></span><span class="label">การจองถูกยกเลิกโดยผู้ดูแลระบบ</span>
        </div>

        <div class="body">
            <p class="greet">สวัสดี {{ $booking->user->us_name }},</p>
            <p class="lead">ขออภัยในความไม่สะดวก การจองของคุณด้านล่างนี้ถูกยกเลิกโดยผู้ดูแลระบบ</p>

            <table class="detail-table">
                <tr>
                    <td class="k">สนาม</td>
                    <td class="v">
                        {{ $booking->court->name }}
                        @if($booking->courtSection && $booking->courtSection->code !== 'full')
                            <br><span style="font-weight:400;color:#6b7280;font-size:12px;">({{ $booking->courtSection->name }})</span>
                        @endif
                    </td>
                </tr>
                @php
                    $thMonthsFull = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
                    $bd = $booking->booking_date;
                @endphp
                <tr>
                    <td class="k">วันที่</td>
                    <td class="v">{{ $bd->day }} {{ $thMonthsFull[$bd->month] }} {{ $bd->year + 543 }}</td>
                </tr>
                <tr>
                    <td class="k">เวลา</td>
                    <td class="v">{{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }} น.</td>
                </tr>
            </table>

            <div class="reason-box">
                <p><strong>เหตุผล:</strong> {{ $reason }}</p>
            </div>

            <div class="cta">
                <a href="{{ url('/') }}">จองเวลาใหม่</a>
            </div>
        </div>

        <div class="footer">
            <p>หากมีข้อสงสัยเกี่ยวกับการยกเลิกนี้ กรุณาติดต่อผู้ดูแลระบบ</p>
            <p>© {{ date('Y') }} THATA HOMECOURT — อีเมลนี้ส่งอัตโนมัติ กรุณาอย่าตอบกลับ</p>
        </div>
    </div>
</body>
</html>
