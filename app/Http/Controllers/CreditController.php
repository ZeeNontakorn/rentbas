<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CreditController extends Controller
{
    /**
     * คืนยอดเครดิตปัจจุบันของผู้ใช้ที่ล็อกอินอยู่ (หน่วยบาท) เป็น JSON
     * ใช้สำหรับ polling อัปเดตตัวเลขเครดิตใน navbar แบบไม่ต้อง reload หน้า
     */
    public function current(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'balance' => $user->credit_balance / 100,
        ]);
    }
}
