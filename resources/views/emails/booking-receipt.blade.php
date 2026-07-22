<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จรับเงิน</title>
    <style>
        body { font-family: 'Sarabun', Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 32px auto; background: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }

        .header { background: #0b0b1a; padding: 28px 32px 22px; text-align: center; }
        .header .brand { color: #ffffff; margin: 0; font-size: 20px; font-weight: 800; letter-spacing: 0.5px; }
        .header .sub { color: rgba(255,255,255,0.6); margin: 4px 0 0; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; }

        .status-bar { background: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 16px 32px; text-align: center; }
        .status-bar span.label { color: #374151; font-weight: 700; font-size: 14px; }

        .code-box { text-align: center; padding: 26px 32px 6px; }
        .code-box .label { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px; }
        .code-box .code { display: inline-block; font-family: 'Courier New', monospace; font-size: 26px; font-weight: 800; letter-spacing: 3px; color: #0b0b1a; background: #f9fafb; border: 2px dashed #d1d5db; border-radius: 10px; padding: 10px 20px; }

        .body { padding: 20px 32px 8px; }
        .greet { color: #374151; font-size: 14px; margin: 0 0 18px; }

        .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .detail-table td { padding: 10px 0; font-size: 14px; border-bottom: 1px solid #f1f3f5; vertical-align: top; }
        .detail-table td.k { color: #9ca3af; width: 38%; }
        .detail-table td.v { color: #111827; font-weight: 700; text-align: right; }
        .detail-table tr:last-child td { border-bottom: none; }

        .section-title { font-size: 12px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin: 24px 0 10px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .items-table th { text-align: left; font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 0 8px; border-bottom: 1px solid #e5e7eb; }
        .items-table th.num, .items-table td.num { text-align: right; }
        .items-table td { font-size: 13px; color: #374151; padding: 9px 0; border-bottom: 1px solid #f1f3f5; }

        .total-row td { padding-top: 14px; font-size: 16px; font-weight: 800; color: #0b0b1a; border-top: 2px solid #111827; border-bottom: none; }

        .method-badge {
            display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px;
            background: #ecfdf3; color: #1c7a3d; margin-top: 4px;
        }

        .footer { background: #f9fafb; padding: 20px 32px; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <p class="brand">THATA HOMECOURT</p>
            <p class="sub">ใบเสร็จรับเงิน</p>
        </div>

        <div class="status-bar">
            <span class="label">Receipt / ใบเสร็จรับเงินอิเล็กทรอนิกส์</span>
        </div>

        <div class="code-box">
            <div class="label">เลขที่ใบเสร็จ</div>
            <div class="code">#{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div><span class="method-badge">ชำระด้วยเครดิต</span></div>
        </div>

        <div class="body">
            <p class="greet">เรียน {{ $booking->user->name }},</p>
            <p class="greet" style="margin-top:-14px;">ขอบคุณที่ใช้บริการ นี่คือใบเสร็จรับเงินสำหรับการจองของคุณ</p>

            <table class="detail-table">
                <tr>
                    <td class="k">ผู้จอง</td>
                    <td class="v">{{ $booking->user->name }}</td>
                </tr>
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
                    <td class="k">วันที่ทำรายการ</td>
                    <td class="v">{{ $bd->day }} {{ $thMonthsFull[$bd->month] }} {{ $bd->year + 543 }}</td>
                </tr>
                <tr>
                    <td class="k">เวลาที่จอง</td>
                    <td class="v">{{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }} น.</td>
                </tr>
            </table>

            <p class="section-title">รายการค่าใช้จ่าย</p>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>รายการ</th>
                        <th class="num">ระยะเวลา</th>
                        <th class="num">จำนวนเงิน</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($breakdown as $item)
                        <tr>
                            <td>{{ $item['label'] }}</td>
                            <td class="num">{{ !empty($item['minutes']) ? (int) $item['minutes'] . ' นาที' : '-' }}</td>
                            <td class="num">฿{{ number_format($item['price'] / 100, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">ค่าบริการสนาม</td>
                            <td class="num">฿{{ number_format($booking->price / 100, 2) }}</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="2">ยอดชำระสุทธิ</td>
                        <td class="num">฿{{ number_format($booking->price / 100, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>เอกสารนี้ออกโดยระบบอัตโนมัติ ไม่จำเป็นต้องมีลายเซ็น</p>
            <p>© {{ date('Y') }} THATA HOMECOURT — อีเมลนี้ส่งอัตโนมัติ กรุณาอย่าตอบกลับ</p>
        </div>
    </div>
</body>
</html>
