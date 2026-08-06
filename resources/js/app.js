import './bootstrap';
import promptpay from 'promptpay-qr';
import QRCode from 'qrcode';

// สร้างฟังก์ชันผูกไว้กับ window เพื่อให้ไฟล์ Blade เรียกใช้งานได้
window.generatePromptPayQR = function(mobileNumber, amount, canvasElement) {
    const payload = promptpay(mobileNumber, { amount });

    QRCode.toCanvas(canvasElement, payload, {
        width: 180,
        color: {
            dark: '#111827',
            light: '#ffffff'
        }
    }, function (error) {
        if (error) console.error(error);
    });
};
