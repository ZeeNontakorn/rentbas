<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * แจ้งเตือนแอดมินทางอีเมลทุกครั้งที่มีการ "ชำระเงินสำเร็จ" ในระบบ (ตอนนี้ใช้กับ
 * ชำระด้วยเครดิตของทั้งการจองสนามปกติ และ Private Training — ยังไม่รวม PromptPay
 * เพราะ flow นั้นยังเป็น mock ที่ต้องรอแอดมินตรวจสลิปเองก่อน ยังไม่ถือว่า "จ่ายสำเร็จ" จริง)
 *
 * ออกแบบให้รับเป็นค่าธรรมดา (ไม่ผูกกับ Modelใดโดยเฉพาะ) เพื่อให้ใช้ร่วมกันได้ทั้ง
 * Booking และ PrivateTrainingBooking โดยไม่ต้องสร้าง Mailable แยกสองตัว
 */
class AdminPaymentReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $refType,      // เช่น 'การจองสนาม' หรือ 'Private Training'
        public int $refId,
        public string $customerName,
        public string $detailLine,   // เช่น "Court 2 — เต็มสนาม | 23/07/2026 10:00-11:00"
        public int $amountSatang,
        public string $paymentMethod, // 'credit' | 'promptpay'
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "แจ้งเตือน: มีการชำระเงิน{$this->refType} #{$this->refId}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-payment-received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
