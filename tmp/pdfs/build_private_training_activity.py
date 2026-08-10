from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.pagesizes import A3, landscape
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas


ROOT = Path(__file__).resolve().parents[2]
OUTPUT = ROOT / "output" / "pdf" / "private-training-activity-flow.pdf"
FONT_DIR = Path("/Users/pearlly/Library/Fonts")

PAGE_W, PAGE_H = landscape(A3)
MARGIN = 26

NAVY = colors.HexColor("#0F172A")
SLATE = colors.HexColor("#475569")
MUTED = colors.HexColor("#64748B")
LINE = colors.HexColor("#CBD5E1")
PAPER = colors.HexColor("#F8FAFC")
WHITE = colors.white
BLUE = colors.HexColor("#2563EB")
BLUE_BG = colors.HexColor("#DBEAFE")
PURPLE = colors.HexColor("#7C3AED")
PURPLE_BG = colors.HexColor("#EDE9FE")
GREEN = colors.HexColor("#15803D")
GREEN_BG = colors.HexColor("#DCFCE7")
RED = colors.HexColor("#DC2626")
RED_BG = colors.HexColor("#FEE2E2")
ORANGE = colors.HexColor("#EA580C")
ORANGE_BG = colors.HexColor("#FFEDD5")
GRAY_BG = colors.HexColor("#E2E8F0")


def setup_fonts():
    pdfmetrics.registerFont(TTFont("Thai", str(FONT_DIR / "THSarabunNew.ttf")))
    pdfmetrics.registerFont(TTFont("ThaiBold", str(FONT_DIR / "THSarabunNew Bold.ttf")))


def text(c, x, y, value, size=15, color=NAVY, bold=False, align="left"):
    c.setFillColor(color)
    c.setFont("ThaiBold" if bold else "Thai", size)
    if align == "center":
        c.drawCentredString(x, y, value)
    elif align == "right":
        c.drawRightString(x, y, value)
    else:
        c.drawString(x, y, value)


def wrapped_lines(value, max_chars):
    words = value.split(" ")
    lines, current = [], ""
    for word in words:
        candidate = word if not current else f"{current} {word}"
        if len(candidate) <= max_chars:
            current = candidate
        else:
            if current:
                lines.append(current)
            current = word
    if current:
        lines.append(current)
    return lines


def box(c, x, y, w, h, label, fill=WHITE, stroke=LINE, size=14, bold=False, radius=9):
    c.setFillColor(fill)
    c.setStrokeColor(stroke)
    c.setLineWidth(1.1)
    c.roundRect(x, y, w, h, radius, fill=1, stroke=1)
    lines = wrapped_lines(label, max(16, int(w / (size * 0.48))))
    leading = size * 0.9
    base = y + h / 2 + (len(lines) - 1) * leading / 2 - size * 0.28
    for i, line in enumerate(lines):
        text(c, x + w / 2, base - i * leading, line, size=size, bold=bold, align="center")
    return (x + w / 2, y + h / 2)


def decision(c, cx, cy, w, h, label, fill=ORANGE_BG, stroke=ORANGE, size=13):
    pts = [(cx, cy + h / 2), (cx + w / 2, cy), (cx, cy - h / 2), (cx - w / 2, cy)]
    path = c.beginPath()
    path.moveTo(*pts[0])
    for p in pts[1:]:
        path.lineTo(*p)
    path.close()
    c.setFillColor(fill)
    c.setStrokeColor(stroke)
    c.setLineWidth(1.2)
    c.drawPath(path, fill=1, stroke=1)
    lines = wrapped_lines(label, max(14, int(w / (size * 0.55))))
    leading = size * 0.82
    base = cy + (len(lines) - 1) * leading / 2 - size * 0.25
    for i, line in enumerate(lines):
        text(c, cx, base - i * leading, line, size=size, bold=True, align="center")
    return (cx, cy)


def arrow(c, x1, y1, x2, y2, label=None, color=SLATE, dashed=False):
    c.saveState()
    c.setStrokeColor(color)
    c.setFillColor(color)
    c.setLineWidth(1.4)
    if dashed:
        c.setDash(5, 3)
    c.line(x1, y1, x2, y2)
    import math
    angle = math.atan2(y2 - y1, x2 - x1)
    ah = 7
    for delta in (2.55, -2.55):
        c.line(x2, y2, x2 + ah * math.cos(angle + delta), y2 + ah * math.sin(angle + delta))
    c.restoreState()
    if label:
        mx, my = (x1 + x2) / 2, (y1 + y2) / 2
        c.setFillColor(PAPER)
        c.roundRect(mx - 20, my - 8, 40, 16, 5, fill=1, stroke=0)
        text(c, mx, my - 4, label, size=12, color=color, bold=True, align="center")


def elbow(c, points, label=None, color=SLATE, dashed=False):
    for i in range(len(points) - 2):
        x1, y1 = points[i]
        x2, y2 = points[i + 1]
        c.saveState()
        c.setStrokeColor(color)
        c.setLineWidth(1.4)
        if dashed:
            c.setDash(5, 3)
        c.line(x1, y1, x2, y2)
        c.restoreState()
    arrow(c, *points[-2], *points[-1], label=label, color=color, dashed=dashed)


def header(c, title, subtitle, page_no):
    c.setFillColor(NAVY)
    c.rect(0, PAGE_H - 72, PAGE_W, 72, fill=1, stroke=0)
    text(c, MARGIN, PAGE_H - 35, title, size=27, color=WHITE, bold=True)
    text(c, MARGIN, PAGE_H - 57, subtitle, size=15, color=colors.HexColor("#CBD5E1"))
    text(c, PAGE_W - MARGIN, PAGE_H - 43, f"หน้า {page_no}/2", size=14, color=WHITE, bold=True, align="right")


def lanes(c, names):
    top = PAGE_H - 105
    bottom = 42
    usable = PAGE_W - 2 * MARGIN
    lane_w = usable / len(names)
    for i, name in enumerate(names):
        x = MARGIN + i * lane_w
        c.setFillColor(WHITE if i % 2 == 0 else colors.HexColor("#F1F5F9"))
        c.rect(x, bottom, lane_w, top - bottom, fill=1, stroke=0)
        c.setStrokeColor(LINE)
        c.line(x, bottom, x, top)
        c.setFillColor(colors.HexColor("#E2E8F0"))
        c.rect(x, top, lane_w, 28, fill=1, stroke=0)
        text(c, x + lane_w / 2, top + 8, name, size=16, bold=True, align="center")
    c.line(MARGIN + usable, bottom, MARGIN + usable, top + 28)
    return [MARGIN + lane_w * (i + 0.5) for i in range(len(names))], lane_w


def page_one(c):
    header(c, "Activity Diagram: การจอง Private Training", "ตั้งแต่เลือกโค้ช ส่งคำขอ อนุมัติ จัดสนาม จนถึงยืนยันรายการ", 1)
    centers, lw = lanes(c, ["ลูกค้า", "ระบบ", "แอดมิน / Super Admin", "โค้ช / ผู้ช่วยสนาม"])
    cx, sx, ax, tx = centers
    bw = lw - 34

    box(c, cx - bw/2, 680, bw, 42, "เริ่มจอง Private Training", BLUE_BG, BLUE, bold=True)
    decision(c, sx, 700, 150, 56, "มีแพ็กเกจที่ใช้ได้?", GREEN_BG, GREEN)
    arrow(c, cx + bw/2, 701, sx - 76, 701)
    box(c, cx - bw/2, 618, bw, 44, "ไม่มี: ซื้อแพ็กเกจก่อน", RED_BG, RED)
    elbow(c, [(sx, 672), (sx, 640), (cx + bw/2, 640)], label="ไม่มี", color=RED)
    box(c, cx - bw/2, 558, bw, 44, "เลือกโค้ช และดูตาราง วัน / สัปดาห์ / เดือน", WHITE, BLUE)
    elbow(c, [(sx, 672), (sx, 580), (cx + bw/2, 580)], label="มี", color=GREEN)

    decision(c, sx, 580, 170, 58, "วันที่อยู่ในช่วงจองล่วงหน้า 3 วัน?", ORANGE_BG, ORANGE)
    arrow(c, cx + bw/2, 580, sx - 86, 580)
    box(c, sx - bw/2, 510, bw, 44, "เกิน 3 วัน: ดูตารางได้ แต่กดจองไม่ได้", GRAY_BG, SLATE)
    arrow(c, sx, 551, sx, 534, label="เกิน", color=ORANGE)

    decision(c, sx, 464, 162, 56, "โค้ชว่างหรือไม่?", BLUE_BG, BLUE)
    arrow(c, sx, 510, sx, 493)
    box(c, cx - bw/2, 436, bw, 46, "ไม่ว่าง: แสดงสีเทาและเหตุผลทั่วไป", GRAY_BG, SLATE)
    elbow(c, [(sx - 82, 464), (cx + bw/2, 464)], label="ไม่ว่าง", color=SLATE)

    box(c, cx - bw/2, 372, bw, 48, "ว่าง: เลือกวัน เวลา และเลือกว่าจะใช้ผู้ช่วยสนามหรือไม่", WHITE, BLUE)
    elbow(c, [(sx, 436), (sx, 396), (cx + bw/2, 396)], label="ว่าง", color=GREEN)
    box(c, sx - bw/2, 372, bw, 48, "กรองเฉพาะผู้ช่วยที่ว่าง และตรวจเงื่อนไขทั้งหมด", WHITE, BLUE)
    arrow(c, cx + bw/2, 396, sx - bw/2, 396)

    decision(c, sx, 322, 162, 54, "ข้อมูลผ่านครบไหม?", ORANGE_BG, ORANGE)
    arrow(c, sx, 372, sx, 349)
    box(c, cx - bw/2, 300, bw, 44, "ไม่ผ่าน: แจ้งสาเหตุและให้เลือกใหม่", RED_BG, RED)
    elbow(c, [(sx - 82, 322), (cx + bw/2, 322)], label="ไม่ผ่าน", color=RED)
    box(c, sx - bw/2, 260, bw, 42, "ตัดสิทธิ์แพ็กเกจ 1 ครั้ง และสร้างคำขอรออนุมัติ", BLUE_BG, BLUE, bold=True)
    arrow(c, sx, 295, sx, 282, label="ผ่าน", color=GREEN)
    box(c, tx - bw/2, 260, bw, 42, "คำขอขึ้นตารางโค้ช / ผู้ช่วยอัตโนมัติ", PURPLE_BG, PURPLE)
    arrow(c, sx + bw/2, 281, tx - bw/2, 281)
    box(c, ax - bw/2, 260, bw, 42, "แจ้งเตือนแอดมิน กดแล้วไปหน้าจัดการ", ORANGE_BG, ORANGE)
    arrow(c, tx - bw/2, 270, ax + bw/2, 270)

    decision(c, ax, 210, 150, 54, "อนุมัติหรือปฏิเสธ?", ORANGE_BG, ORANGE)
    arrow(c, ax, 260, ax, 238)
    box(c, sx - bw/2, 184, bw, 44, "ปฏิเสธ: บันทึกเหตุผล คืนสิทธิ์ และแจ้งลูกค้า", RED_BG, RED)
    elbow(c, [(ax - 76, 210), (sx + bw/2, 210)], label="ปฏิเสธ", color=RED)
    box(c, ax - bw/2, 142, bw, 44, "อนุมัติ: เปลี่ยนเป็นรอจัดสนาม", PURPLE_BG, PURPLE)
    arrow(c, ax, 182, ax, 164, label="อนุมัติ", color=GREEN)
    box(c, ax - bw/2, 84, bw, 42, "เลือกจากสนามที่ว่างตรงกับวันและเวลา", WHITE, PURPLE)
    arrow(c, ax, 142, ax, 126)
    box(c, sx - bw/2, 84, bw, 42, "ล็อกและตรวจซ้ำก่อนยืนยัน เพื่อกันการจองชนกัน", WHITE, BLUE)
    arrow(c, ax - bw/2, 105, sx + bw/2, 105)
    box(c, sx - bw/2, 30, bw, 42, "ยืนยัน + ปิดสนามช่วงนั้น + ไม่คิดราคา/ไม่หักเครดิตเพิ่ม", GREEN_BG, GREEN, bold=True)
    arrow(c, sx, 84, sx, 72)

    c.setFillColor(colors.HexColor("#FFF7ED"))
    c.roundRect(cx - bw/2, 30, bw, 42, 8, fill=1, stroke=0)
    text(c, cx, 51, "ลูกค้ายกเลิกก่อนเริ่มได้ และระบบคืนสิทธิ์แพ็กเกจ", size=13, color=ORANGE, bold=True, align="center")
    arrow(c, sx - bw/2, 51, cx + bw/2, 51, color=ORANGE, dashed=True)


def page_two(c):
    header(c, "Activity Diagram: ตารางงานและการแสดงรายละเอียด", "การจัดการ Schedule การกันเวลาทับ และสิทธิ์การดูข้อมูลของแต่ละบทบาท", 2)
    centers, lw = lanes(c, ["Admin / Super Admin", "โค้ช / ผู้ช่วยสนาม", "ระบบ", "ลูกค้า"])
    ax, tx, sx, cx = centers
    bw = lw - 34

    box(c, ax - bw/2, 678, bw, 44, "จัดการตารางของโค้ชและผู้ช่วยได้", BLUE_BG, BLUE, bold=True)
    box(c, tx - bw/2, 678, bw, 44, "จัดการตารางงานของตัวเองได้", GREEN_BG, GREEN, bold=True)
    box(c, ax - bw/2, 610, bw, 48, "เพิ่ม / แก้ไข / ลบ Schedule", WHITE, BLUE)
    box(c, tx - bw/2, 610, bw, 48, "เพิ่ม / แก้ไข / ลบ Schedule", WHITE, GREEN)
    arrow(c, ax, 678, ax, 658)
    arrow(c, tx, 678, tx, 658)

    box(c, sx - bw/2, 610, bw, 48, "Modal: ชื่อกิจกรรม ประเภท สี วัน เวลา รายละเอียด และการเกิดซ้ำ", PURPLE_BG, PURPLE)
    arrow(c, ax + bw/2, 634, sx - bw/2, 634)
    arrow(c, tx + bw/2, 622, sx - bw/2, 622)
    decision(c, sx, 552, 170, 58, "เวลาทับ Schedule หรือ Private เดิมไหม?", ORANGE_BG, ORANGE)
    arrow(c, sx, 610, sx, 581)
    box(c, tx - bw/2, 526, bw, 46, "ทับ: ไม่ให้บันทึก พร้อมแจ้งช่วงเวลาที่ชน", RED_BG, RED)
    elbow(c, [(sx - 86, 552), (tx + bw/2, 552)], label="ทับ", color=RED)
    box(c, sx - bw/2, 480, bw, 46, "ไม่ทับ: บันทึกและกำหนดช่วงนั้นเป็นไม่ว่าง", GREEN_BG, GREEN, bold=True)
    arrow(c, sx, 523, sx, 503, label="ไม่ทับ", color=GREEN)
    box(c, cx - bw/2, 480, bw, 46, "เห็นช่วงไม่ว่างเป็นสีเทา และจองช่วงนั้นไม่ได้", GRAY_BG, SLATE)
    arrow(c, sx + bw/2, 503, cx - bw/2, 503)

    box(c, sx - bw/2, 410, bw, 48, "Private ที่ส่งคำขอจะขึ้นตารางอัตโนมัติ โดยไม่สร้าง Schedule ซ้ำ", BLUE_BG, BLUE)
    arrow(c, sx, 480, sx, 458)
    box(c, tx - bw/2, 410, bw, 48, "โค้ช / ผู้ช่วยเห็นงานที่ได้รับมอบหมาย", WHITE, GREEN)
    arrow(c, sx - bw/2, 434, tx + bw/2, 434)

    box(c, ax - bw/2, 328, bw, 62, "กดรายการ Private เพื่อดู เลขคำขอ สถานะ ลูกค้า ติดต่อ โค้ช ผู้ช่วย สนาม แพ็กเกจ และหมายเหตุ", WHITE, BLUE)
    box(c, tx - bw/2, 328, bw, 62, "กดรายการของตัวเองเพื่อดู วัน เวลา ลูกค้า สนาม สถานะ และรายละเอียดงาน", WHITE, GREEN)
    box(c, cx - bw/2, 328, bw, 62, "กดคำขอของตัวเองเพื่อดู วัน เวลา โค้ช ผู้ช่วย สนาม แพ็กเกจ และสถานะ", WHITE, PURPLE)

    text(c, MARGIN, 286, "สถานะของคำขอ Private Training", size=20, bold=True)
    status_y = 232
    sw = (PAGE_W - 2*MARGIN - 36) / 4
    statuses = [
        ("รออนุมัติ", "แอดมินยังไม่ได้ตรวจคำขอ", BLUE_BG, BLUE),
        ("รอจัดสนาม", "อนุมัติแล้ว แต่ยังไม่ได้เลือกสนาม", PURPLE_BG, PURPLE),
        ("ยืนยันแล้ว", "จัดสนามเรียบร้อยและพร้อมให้บริการ", GREEN_BG, GREEN),
        ("ปฏิเสธ / ยกเลิก", "คืนสิทธิ์แพ็กเกจตามเงื่อนไข", RED_BG, RED),
    ]
    for i, (name, desc, fill, stroke) in enumerate(statuses):
        x = MARGIN + i * (sw + 12)
        c.setFillColor(fill)
        c.setStrokeColor(stroke)
        c.roundRect(x, status_y, sw, 48, 9, fill=1, stroke=1)
        text(c, x + 12, status_y + 28, name, size=15, color=stroke, bold=True)
        text(c, x + 12, status_y + 10, desc, size=12, color=NAVY)

    c.setFillColor(NAVY)
    c.roundRect(MARGIN, 76, PAGE_W - 2*MARGIN, 122, 12, fill=1, stroke=0)
    text(c, MARGIN + 18, 169, "กติกาหลักของระบบ", size=20, color=WHITE, bold=True)
    rules = [
        "1. มี Schedule ในช่วงเวลาใด = บุคลากรไม่ว่างในช่วงเวลานั้น",
        "2. ลูกค้าดูปฏิทินได้ทั้งวัน สัปดาห์ และเดือน แต่เลือกจองล่วงหน้าได้สูงสุด 3 วัน",
        "3. สนามที่จัดให้ Private ต้องว่างจากการจองสนามปกติ Private อื่น วันปิดสนาม และกิจกรรมที่ผูกกับสนาม",
        "4. การจัดสนามเป็นการกันสนามให้ Private เท่านั้น ไม่มีการหักเครดิตหรือคิดค่าสนามเพิ่ม",
        "5. ลูกค้าเห็นเหตุผลทั่วไปของช่วงไม่ว่าง แต่ไม่เห็นข้อมูลส่วนตัวของลูกค้าคนอื่น",
    ]
    for i, rule in enumerate(rules):
        text(c, MARGIN + 22, 146 - i*17, rule, size=14, color=colors.HexColor("#E2E8F0"))

    text(c, MARGIN, 51, "THATA HOMECOURT - Private Training & Schedule Flow", size=12, color=MUTED)
    text(c, PAGE_W - MARGIN, 51, "จัดทำวันที่ 7 สิงหาคม 2569", size=12, color=MUTED, align="right")


def build():
    setup_fonts()
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    c = canvas.Canvas(str(OUTPUT), pagesize=landscape(A3))
    c.setTitle("Private Training Activity Flow")
    c.setAuthor("THATA HOMECOURT")
    c.setSubject("Activity Diagram for Private Training and Schedule Management")
    c.setFillColor(PAPER)
    c.rect(0, 0, PAGE_W, PAGE_H, fill=1, stroke=0)
    page_one(c)
    c.showPage()
    c.setFillColor(PAPER)
    c.rect(0, 0, PAGE_W, PAGE_H, fill=1, stroke=0)
    page_two(c)
    c.save()
    print(OUTPUT)


if __name__ == "__main__":
    build()
