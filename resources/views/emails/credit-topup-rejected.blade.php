<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำขอเติมเครดิตไม่ผ่านการตรวจสอบ</title>
    <style>
        body {
            font-family: 'Sarabun', 'Noto Sans Thai', Arial, sans-serif;
            background: #eef0f4;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .wrapper { padding: 40px 16px; }

        .container {
            max-width: 540px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px -12px rgba(15, 15, 30, 0.18);
        }

        .hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #0b0b1a 100%);
            padding: 40px 36px 84px;
            text-align: center;
            position: relative;
        }

        .hero .brand {
            color: #ffffff;
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: 0.55;
        }

        .icon-badge {
            width: 68px;
            height: 68px;
            background: #ffffff;
            border-radius: 50%;
            margin: 74px auto -34px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px -6px rgba(220, 38, 38, 0.4);
            position: relative;
            z-index: 2;
        }

        .icon-badge svg { display: block; }

        .body { padding: 56px 36px 8px; text-align: center; }

        .title {
            color: #111827;
            font-size: 21px;
            font-weight: 800;
            margin: 0 0 10px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.7;
            margin: 0 0 30px;
        }

        .subtitle strong { color: #111827; }

        .summary-card {
            background: #f9fafb;
            border-radius: 14px;
            padding: 22px 24px;
            text-align: left;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
        }

        .summary-row + .summary-row { border-top: 1px solid #e5e7eb; }

        .summary-row .k { color: #9ca3af; font-size: 13px; }
        .summary-row .v { color: #111827; font-size: 13.5px; font-weight: 700; }

        .amount-row .v { color: #dc2626; font-size: 16px; }

        .reason-card {
            background: #fef2f2;
            border-radius: 14px;
            padding: 18px 22px;
            text-align: left;
            margin-bottom: 24px;
        }

        .reason-card .tag {
            display: inline-block;
            color: #b91c1c;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .reason-card p {
            color: #7f1d1d;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .helper-text {
            color: #6b7280;
            font-size: 13.5px;
            line-height: 1.8;
            margin: 0 0 32px;
            text-align: left;
        }

        .divider { height: 1px; background: #eef0f4; margin: 0 36px; }

        .footer { padding: 26px 36px 34px; text-align: center; }
        .footer p { color: #b0b4bd; font-size: 12px; margin: 0; line-height: 1.7; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">

            <div class="hero">
                <p class="brand">THATA HOMECOURT</p>
            </div>

            <div class="icon-badge">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="11" fill="#fef2f2"/>
                    <path d="M8 8L16 16M16 8L8 16" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>

            <div class="body">
                <p class="title">คำขอเติมเครดิตไม่ผ่านการตรวจสอบ</p>
                <p class="subtitle">สวัสดีคุณ <strong>{{ $topupRequest->user->us_name }}</strong> คำขอเติมเครดิตของคุณยังไม่สามารถอนุมัติได้ในขณะนี้</p>

                <div class="summary-card">
                    <div class="summary-row">
                        <span class="k">คำขอเลขที่</span>
                        <span class="v">#{{ str_pad($topupRequest->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="summary-row amount-row">
                        <span class="k">จำนวนเงินที่แจ้ง</span>
                        <span class="v">{{ number_format($topupRequest->price_satang / 100, 2) }} บาท</span>
                    </div>
                    <div class="summary-row">
                        <span class="k">วันที่ส่งคำขอ</span>
                        <span class="v">{{ $topupRequest->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>

                @if($topupRequest->rejected_reason)
                <div class="reason-card">
                    <span class="tag">เหตุผลที่ไม่ผ่าน</span>
                    <p>{{ $topupRequest->rejected_reason }}</p>
                </div>
                @endif

                <p class="helper-text">กรุณาตรวจสอบสลิปและยอดเงินให้ถูกต้อง แล้วส่งคำขอใหม่อีกครั้ง หรือติดต่อเจ้าหน้าที่หากคิดว่าเป็นความผิดพลาด</p>
            </div>

            <div class="divider"></div>

            <div class="footer">
                <p>© {{ date('Y') }} THATA HOMECOURT<br>อีเมลนี้ส่งอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            </div>
        </div>
    </div>
</body>
</html>