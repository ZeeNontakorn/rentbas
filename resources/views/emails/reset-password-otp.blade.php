<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน</title>
    <style>
        body { font-family: 'Sarabun', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #ea580c, #f97316); padding: 32px 32px 24px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.85); margin: 6px 0 0; font-size: 14px; }
        .body { padding: 32px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .otp-box { background: #fff7ed; border: 2px dashed #f97316; border-radius: 10px; text-align: center; padding: 20px; margin: 24px 0; }
        .otp-box .label { font-size: 12px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .otp-box .code { font-size: 40px; font-weight: 800; letter-spacing: 12px; color: #ea580c; font-family: monospace; }
        .warning { background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 6px; margin: 20px 0; }
        .warning p { color: #b91c1c; font-size: 13px; margin: 0; }
        .footer { background: #f9fafb; padding: 20px 32px; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>THATA HOMECOURT</h1>
            <p>ระบบจองสนามบาสเกตบอล</p>
        </div>
        <div class="body">
            <p>สวัสดีครับ/ค่ะ</p>
            <p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ กรุณาใช้รหัส OTP ด้านล่างเพื่อดำเนินการต่อ:</p>

            <div class="otp-box">
                <div class="label">รหัส OTP สำหรับรีเซ็ตรหัสผ่าน</div>
                <div class="code">{{ $otp }}</div>
            </div>

            <div class="warning">
                <p>⚠️ รหัสนี้จะหมดอายุใน <strong>5 นาที</strong> — หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน กรุณาเพิกเฉยต่ออีเมลฉบับนี้</p>
            </div>

            <p>หากมีข้อสงสัย สามารถติดต่อเราได้ทาง {{ config('mail.from.address') }}</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} THATA HOMECOURT — ส่งอัตโนมัติ กรุณาอย่าตอบกลับอีเมลนี้</p>
        </div>
    </div>
</body>
</html>
