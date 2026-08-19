{{-- resources/views/emails/private-training-confirmed.blade.php --}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันการจองเทรนเนอร์ส่วนตัว</title>
    <style>
        body { font-family: 'Sarabun', Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 32px auto; background: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }

        .header { background: #0b0b1a; padding: 28px 32px 22px; text-align: center; }
        .header .brand { color: #ffffff; margin: 0; font-size: 20px; font-weight: 800; letter-spacing: 0.5px; }
        .header .sub { color: rgba(255,255,255,0.6); margin: 4px 0 0; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; }

        .status-bar { background: #ecfdf3; border-bottom: 1px solid #d1fae5; padding: 16px 32px; text-align: center; }
        .status-bar .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #87D068; margin-right: 6px; }
        .status-bar span.label { color: #1c7a3d; font-weight: 700; font-size: 14px; }

        .code-box { text-align: center; padding: 26px 32px 6px; }
        .code-box .label { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px; }
        .code-box .code { display: inline-block; font-family: 'Courier New', monospace; font-size: 30px; font-weight: 800; letter-spacing: 4px; color: #0b0b1a; background: #f9fafb; border: 2px dashed #87D068; border-radius: 10px; padding: 12px 22px; }

        .body { padding: 20px 32px 8px; }
        .greet { color: #374151; font-size: 14px; margin: 0 0 18px; }

        .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .detail-table td { padding: 10px 0; font-size: 14px; border-bottom: 1px solid #f1f3f5; vertical-align: top; }
        .detail-table td.k { color: #9ca3af; width: 38%; }
        .detail-table td.v { color: #111827; font-weight: 700; text-align: right; }
        .detail-table tr:last-child td { border-bottom: none; }

        .checkin-box { background: #fff7ed; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 14px 18px; margin: 4px 0 22px; }
        .checkin-box p { color: #92400e; font-size: 13px; margin: 0 0 4px; line-height: 1.6; }
        .checkin-box p:last-child { margin-bottom: 0; }
        .checkin-box strong { color: #78350f; }

        .cta { text-align: center; margin-bottom: 8px; }
        .cta a { display: inline-block; background: #87D068; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 8px; }

        .footer { background: #f9fafb; padding: 20px 32px; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <p class="brand">THATA HOMECOURT</p>
            <p class="sub">ยืนยันการจองเทรนเนอร์ส่วนตัว</p>
        </div>

        <div class="status-bar">
            <span class="dot"></span><span class="label">การจองได้รับการยืนยันแล้ว</span>
        </div>

        <div class="code-box">
            <div class="label">หมายเลขการจอง (แจ้งพนักงานหน้าเคาน์เตอร์)</div>
            <div class="code">#{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>

        <div class="body">
            <p class="greet">เรียนคุณ {{ $booking->user->us_name }},</p>

            <table class="detail-table">
                <tr>
                    <td class="k">ผู้จอง</td>
                    <td class="v">{{ $booking->user->us_name }}</td>
                </tr>
                <tr>
                    <td class="k">โค้ช</td>
                    <td class="v">{{ $booking->coach->us_name }}</td>
                </tr>
                <tr>
                    <td class="k">ผู้ช่วยสนาม</td>
                    <td class="v">{{ $booking->courtAssistant->us_name ?? 'ไม่มี' }}</td>
                </tr>
                <tr>
                    <td class="k">สนาม</td>
                    <td class="v">
                        {{ $booking->court->name }}
                        <br><span style="font-weight:400;color:#6b7280;font-size:12px;">({{ $booking->courtSection->name }})</span>
                    </td>
                </tr>
                <tr>
                    <td class="k">จำนวนผู้เข้าร่วม</td>
                    <td class="v">{{ $booking->participant_count ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="k">วันที่</td>
                    <td class="v">{{ $booking->date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="k">เวลา</td>
                    <td class="v">{{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }} น.</td>
                </tr>
                <tr>
                    <td class="k">การชำระเงิน</td>
                    <td class="v">
                        {{ $booking->payment_status === 'paid_by_package' ? 'ใช้สิทธิ์จากแพ็กเกจ' : number_format($booking->price / 100, 2) . ' บาท (หักจากเครดิต)' }}
                    </td>
                </tr>
            </table>

            <div class="checkin-box">
                <p><strong>สำหรับ Check-in:</strong> กรุณาแสดงอีเมลฉบับนี้ (หรือแจ้งหมายเลขการจองด้านบน) แก่เจ้าหน้าที่ที่เคาน์เตอร์ต้อนรับ</p>
                <p>กรุณามาถึงก่อนเวลาเทรนอย่างน้อย 10 นาที</p>
            </div>

            <div class="cta">
                <a href="{{ url('/') }}">ดูรายละเอียด / จัดการการจอง</a>
            </div>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} THATA HOMECOURT — อีเมลนี้ส่งอัตโนมัติ กรุณาอย่าตอบกลับ<br>หากมีข้อสงสัย ติดต่อทีมงานได้ที่ 081-246-0000</p>
        </div>
    </div>
</body>
</html>
