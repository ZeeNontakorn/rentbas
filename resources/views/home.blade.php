@extends('layouts.app')

@section('title', 'THATA Homecourt - THATA SPORT HQ & Basketball Chonburi ')

@section('content')
@php
    // ดึงค่าการตั้งค่าเว็บไซต์จากฐานข้อมูล
    $site = \App\Models\Setting::values([
        'hero_img_1',
        'hero_img_2',
        'hero_img_3',
        'about_title',
        'about_desc',
        'about_img_1',
        'about_img_2',
        'about_img_3',
        'courts_bg',
        'community_img',
        'promo_subtitle',
        'promo_title',
        'promo_card_title',
        'promo_card_sub',
        'promo_image',
    ]);
@endphp

@php
    // กรองรูปภาพสำหรับทำ Hero Slider
    $heroSlides = array_values(array_filter([
        $site['hero_img_1'] ?? null,
        $site['hero_img_2'] ?? null,
        $site['hero_img_3'] ?? null,
    ]));
@endphp

<style>
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Kanit:wght@300;400;500;600;700;800;900&family=Sarabun:wght@300;400;500;600&display=swap');

:root {
    --ore: #e86c2a;
    --ore-d: #d05a1a;
    --ink: #0d0f1e;
    --navy: #1e2235;
    --navy-d: #13162a;
    --cream: #f6f5f0;
    --border: rgba(255,255,255,0.08);
    --green: #22c55e;
    --red: #ef4444;
    --max-w: 1200px;
}

.home-content *, .home-content *::before, .home-content *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
.home-content {
    font-family: 'Sarabun', sans-serif;
    color: #e0e0e0;
    -webkit-font-smoothing: antialiased;
}
.home-content h1, .home-content h2, .home-content h3, .home-content h4, .home-content h5 { font-family: 'Kanit', sans-serif; }
.home-content a { text-decoration: none; color: inherit; }
.home-content img { display: block; max-width: 100%; }

.mobile-menu {
    display: none;
    position: fixed;
    top: 56px; left: 0; right: 0;
    background: var(--navy-d);
    padding: 12px 24px 20px;
    border-bottom: 1px solid var(--border);
    z-index: 199;
    flex-direction: column;
    gap: 4px;
}
.mobile-menu.open { display: flex; }
.mobile-menu .nav-link { color: rgba(255,255,255,0.8); }

/* ─── HERO ─── */
.hero {
    position: relative;
    height: 68vh;
    min-height: 440px;
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    overflow: hidden;
}
.hero-bg-fader {
    position: absolute;
    inset: 0;
    z-index: 0;
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    opacity: 0;
    transition: opacity .9s ease;
}
.hero::before {
    content: '';
    position: absolute; inset: 0; z-index: 1;
    background: linear-gradient(160deg, rgba(13,15,30,.82) 0%, rgba(13,15,30,.45) 60%, rgba(0,0,0,.2) 100%);
}
.hero-content {
    position: absolute; inset: 0; z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0 24px 80px;
}
.hero-eyebrow {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .22em;
    text-transform: uppercase;
    color: var(--ore);
    margin-bottom: 12px;
}
.hero-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(52px, 10vw, 108px);
    line-height: .9;
    color: #fff;
    letter-spacing: .04em;
    margin-bottom: 10px;
}
.hero-title span { color: var(--ore); }
.hero-sub {
    font-size: 15px;
    color: rgba(255,255,255,.6);
    font-weight: 300;
    margin-bottom: 36px;
    line-height: 1.6;
}
.hero-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;}

/* hero stats bar */
.hero-stats {
    position: absolute; bottom: 0; left: 0; right: 0;
    display: flex;
    background: rgba(13,15,30,.85);
    backdrop-filter: blur(8px);
    border-top: 1px solid rgba(255,255,255,.07);
}
.hstat {
    flex: 1;
    padding: 14px 20px;
    border-right: 1px solid rgba(255,255,255,.07);
    text-align: center;
}
.hstat:last-child { border-right: none; }
.hstat-num {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 28px;
    color: var(--ore);
    letter-spacing: .04em;
    line-height: 1;
}
.hstat-label {
    font-size: 10px;
    color: rgba(255,255,255,.4);
    letter-spacing: .1em;
    text-transform: uppercase;
    margin-top: 2px;
}

/* ─── TICKER ─── */
.ticker {
    background: var(--ore);
    overflow: hidden;
    height: 30px;
    display: flex;
    align-items: center;
}
.ticker-track {
    display: flex;
    gap: 48px;
    animation: ticker-move 22s linear infinite;
    white-space: nowrap;
}
.ticker-track span {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 12px;
    letter-spacing: .15em;
    color: #fff;
}
@keyframes ticker-move { from{transform:translateX(0)} to{transform:translateX(-50%)} }

/* ─── SECTION PADDING (centered max-width) ─── */
.about-section,
.courts-section,
.booking-section,
.community-section {
    padding-left:  max(40px, calc((100% - var(--max-w)) / 2));
    padding-right: max(40px, calc((100% - var(--max-w)) / 2));
}

.footer {
    padding-left:  max(40px, calc((100% - var(--max-w)) / 2));
    padding-right: max(40px, calc((100% - var(--max-w)) / 2));
}

/* ─── ABOUT SECTION ─── */
.about-section {
    background: var(--cream);
    padding-top: 72px;
    padding-bottom: 72px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 64px;
    align-items: center;
}
.about-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--ore);
    margin-bottom: 10px;
}
.about-title {
    font-size: clamp(26px, 4vw, 40px);
    font-weight: 800;
    color: var(--ink);
    line-height: 1.2;
    margin-bottom: 16px;
}
.about-desc {
    font-size: 13.5px;
    color: #6b7280;
    line-height: 1.85;
    margin-bottom: 22px;
}
.about-checks { display: flex; flex-direction: column; gap: 10px; margin-bottom: 28px; }
.check-row { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--ink); font-weight: 500; }
.check-icon {
    width: 22px; height: 22px;
    background: var(--ore);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #fff;
    font-size: 11px;
}
.btn-orange-sm {
    display: inline-block;
    padding: 11px 26px;
    background: var(--ore);
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    transition: background .2s;
}
.btn-orange-sm:hover { background: var(--ore-d); }
.about-images {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 200px 160px;
    gap: 10px;
}
.about-img { border-radius: 10px; overflow: hidden; }
.about-img.main { grid-column: 1/3; }
.about-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s ease; }
.about-img:hover img { transform: scale(1.04); }

/* ─── COURTS SECTION ─── */
.courts-section {
    background: linear-gradient(rgba(0, 31, 63, 0.8), rgba(0, 17, 34, 0.8)),
                url('{{ $site['courts_bg'] }}');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    padding-top: 60px;
    padding-bottom: 60px;
}
.courts-header { text-align: center; margin-bottom: 40px; }
.courts-label { font-size: 30px; font-weight: 700; color: var(--ore); font-family: 'Kanit', sans-serif; margin-bottom: 4px; }
.courts-subtitle { font-size: 14px; color: rgba(255,255,255,.45); margin-bottom: 6px; }
.courts-count { font-family: 'Bebas Neue', sans-serif; font-size: 44px; color: #fff; letter-spacing: .04em; }
.courts-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
.court-card {
    background: var(--navy);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.07);
    transition: border-color .25s, transform .25s;
    cursor: pointer;
}
.court-card:hover { border-color: rgba(232,108,42,.45); transform: translateY(-4px); }
.court-thumb { height: 130px; position: relative; overflow: hidden; }
.court-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.court-card:hover .court-thumb img { transform: scale(1.06); }
.court-thumb-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(19,22,42,.95) 0%, transparent 60%);
}
.court-num {
    position: absolute; bottom: 10px; left: 14px;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 40px; color: rgba(255,255,255,.8); line-height: 1;
}
.court-body { padding: 14px 16px 16px; }
.court-name { font-family: 'Kanit', sans-serif; font-size: 15px; font-weight: 700; color: #fff; margin-bottom: 6px; }
.court-status-row { display: flex; align-items: center; gap: 6px; margin-bottom: 12px; }
.sdot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.sdot.open { background: var(--green); box-shadow: 0 0 6px rgba(34,197,94,.5); }
.sdot.closed { background: var(--red); }
.stext { font-size: 11px; font-weight: 500; }
.stext.open { color: var(--green); }
.stext.closed { color: var(--red); }
.court-btn-book {
    display: block; width: 100%; padding: 9px;
    background: var(--ore); color: #fff;
    font-family: 'Kanit', sans-serif; font-size: 13px; font-weight: 600;
    border-radius: 6px; text-align: center; transition: background .2s;
    cursor: pointer;
}
.court-btn-book:hover { background: var(--ore-d); }
.court-btn-disabled {
    display: block; width: 100%; padding: 9px;
    background: rgba(255,255,255,.05); color: rgba(255,255,255,.2);
    font-family: 'Kanit', sans-serif; font-size: 13px; font-weight: 600;
    border-radius: 6px; text-align: center; cursor: not-allowed;
}

/* ─── COURSES / BASKETBALL SCHOOL SECTION ─── */
.courses-section {
    background: var(--cream);
    padding-top: 72px;
    padding-bottom: 72px;
    padding-left:  max(40px, calc((100% - var(--max-w)) / 2));
    padding-right: max(40px, calc((100% - var(--max-w)) / 2));
}
.courses-header { text-align: center; margin-bottom: 40px; }
.courses-label {
    font-size: 11px; font-weight: 600; letter-spacing: .2em;
    text-transform: uppercase; color: var(--ore); margin-bottom: 8px;
}
.courses-title { font-size: clamp(26px, 4vw, 40px); font-weight: 800; color: var(--ink); margin-bottom: 8px; }
.courses-subtitle { font-size: 13.5px; color: #868e96; }
.courses-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.courses-group + .courses-group { margin-top: 48px; }
.courses-group-title { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; color: var(--ink); font-family: 'Kanit', sans-serif; font-size: 20px; font-weight: 700; }
.courses-group-title::before { width: 5px; height: 26px; border-radius: 8px; background: var(--ore); content: ''; }
.session-chip { display: inline-flex; width: fit-content; margin-bottom: 14px; background: #ede9fe; color: #6d28d9; }

/* card */
.course-card2 {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid #edeff2;
    box-shadow: 0 2px 10px rgba(13,15,30,.04);
    display: flex;
    flex-direction: column;
    transition: transform .3s ease, box-shadow .3s ease;
}
.course-card2:hover { transform: translateY(-6px); box-shadow: 0 20px 36px rgba(13,15,30,.12); }

/* image + overlay title */
.course-thumb2 { height: 190px; position: relative; overflow: hidden; }
.course-thumb2 img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
.course-card2:hover .course-thumb2 img { transform: scale(1.08); }
.course-thumb2-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(13,15,30,.92) 0%, rgba(13,15,30,.15) 55%, rgba(13,15,30,0) 100%);
}
.course-badge-featured {
    position: absolute; top: 12px; right: 12px; z-index: 1;
    background: var(--ore); color: #fff;
    font-size: 10px; font-weight: 700;
    padding: 5px 11px; border-radius: 20px; letter-spacing: .03em;
    box-shadow: 0 4px 10px rgba(232,108,42,.35);
}
.course-name2 {
    position: absolute; left: 16px; right: 16px; bottom: 12px; z-index: 1;
    font-family: 'Kanit', sans-serif; font-size: 18px; font-weight: 700;
    color: #fff; line-height: 1.25;
}

/* body */
.course-body2 { padding: 16px 18px 20px; display: flex; flex-direction: column; flex: 1; }
.course-meta-row { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
.course-chip {
    font-size: 10.5px; font-weight: 600; padding: 4px 10px;
    border-radius: 20px; background: #f1f3f5; color: #495057;
}
.course-chip-age { background: rgba(232,108,42,.1); color: var(--ore-d); }

.course-desc2 {
    font-size: 12.5px; color: #868e96; line-height: 1.6;
    margin-bottom: 14px;
}

.course-info-block {
    background: #f8f9fa; border-radius: 10px;
    padding: 10px 12px; margin-bottom: 16px;
}
.course-info-line {
    font-size: 12px; color: #495057; font-weight: 500;
    display: flex; align-items: center; gap: 6px;
    padding: 4px 0;
}
.course-info-line + .course-info-line { border-top: 1px dashed #e9ecef; }
.course-info-icon { width: 13px; height: 13px; flex-shrink: 0; color: var(--ore); }
.course-info-muted { color: #adb5bd; }
.course-spots-tag {
    margin-left: auto; font-size: 10px; font-weight: 700;
    padding: 2px 9px; border-radius: 20px;
    background: #fff4e6; color: #e67700; white-space: nowrap;
}

.course-price-row {
    margin-top: auto; padding-top: 16px; border-top: 1px solid #f1f3f5;
    display: flex; align-items: flex-end; justify-content: space-between; gap: 10px;
}
.course-price { font-family: 'Bebas Neue', sans-serif; font-size: 30px; color: var(--ink); line-height: 1; letter-spacing: .02em; }
.course-price-tag {
    font-size: 10px; font-weight: 700; color: var(--ore);
    text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px;
}
.course-price-sub { font-size: 10.5px; color: #adb5bd; margin-top: 3px; }
.course-btn-enroll {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 16px; background: var(--ink); color: #fff;
    font-family: 'Kanit', sans-serif; font-size: 12.5px; font-weight: 600;
    border-radius: 9px; transition: background .2s, transform .2s; white-space: nowrap;
}
.course-btn-enroll:hover { background: var(--ore); }
.course-btn-arrow { width: 14px; height: 14px; transition: transform .2s; }
.course-btn-enroll:hover .course-btn-arrow { transform: translateX(3px); }

.courses-empty {
    text-align: center; color: #adb5bd; padding: 56px 0; font-size: 13.5px;
}
.courses-empty-icon { font-size: 34px; margin-bottom: 10px; opacity: .6; }

/* ─── BOOKING / CALENDAR SECTION ─── */
.booking-section {
    background: #f8f9fb;
    padding-top: 64px;
    padding-bottom: 64px;
}
.booking-label {
    font-size: 11px; font-weight: 600;
    letter-spacing: .2em; text-transform: uppercase;
    color: var(--ore); margin-bottom: 6px;
}
.booking-title {
    font-size: clamp(22px, 3.5vw, 36px);
    font-weight: 800; color: var(--ink); margin-bottom: 28px;
}
.booking-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 20px;
    align-items: start;
}

/* LEFT: calendar card */
.bk-cal-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e8eaed;
    overflow: hidden;
}
.bk-cal-header {
    background: var(--ink);
    padding: 16px 20px 14px;
    display: flex; align-items: center; justify-content: space-between;
}
.bk-cal-month-label {
    font-family: 'Kanit', sans-serif;
    font-size: 15px; font-weight: 700; color: #fff;
}
.bk-cal-nav-btns { display: flex; gap: 4px; }
.bk-cal-nav-btns button {
    width: 28px; height: 28px; border-radius: 6px;
    background: rgba(255,255,255,.1); border: none;
    color: #fff; font-size: 14px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.bk-cal-nav-btns button:hover { background: var(--ore); }

.bk-cal-body { padding: 14px 16px 16px; }
.bk-cal-days-header {
    display: grid; grid-template-columns: repeat(7,1fr);
    margin-bottom: 6px;
}
.bk-cal-days-header span {
    font-size: 10px; font-weight: 600; color: #adb5bd;
    text-align: center; padding: 4px 0;
    text-transform: uppercase; letter-spacing: .05em;
}
.bk-cal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; }
.bk-day {
    aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 500; color: #495057;
    border-radius: 8px; cursor: pointer; position: relative;
    transition: background .12s, color .12s;
}
.bk-day:hover { background: #f1f3f5; color: var(--ink); }
.bk-day.empty { cursor: default; pointer-events: none; }
.bk-day.today { color: var(--ore); font-weight: 700; }
.bk-day.today::before {
    content: ''; position: absolute; inset: 0;
    border-radius: 8px; border: 1.5px solid var(--ore);
}
.bk-day.selected {
    background: var(--ore) !important; color: #fff !important; font-weight: 700;
}
.bk-day.has-free::after {
    content: ''; position: absolute; bottom: 3px; left: 50%; transform: translateX(-50%);
    width: 3px; height: 3px; border-radius: 50%; background: var(--green);
}
.bk-day.is-full::after {
    content: ''; position: absolute; bottom: 3px; left: 50%; transform: translateX(-50%);
    width: 3px; height: 3px; border-radius: 50%; background: var(--red);
}
.bk-day.selected::after { background: rgba(255,255,255,.7); }

.bk-cal-legend {
    display: flex; gap: 14px; padding: 12px 16px;
    border-top: 1px solid #f1f3f5;
}
.bk-legend-item { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #868e96; }
.bk-legend-dot { width: 7px; height: 7px; border-radius: 50%; }
.bk-legend-dot.free { background: #51cf66; }
.bk-legend-dot.booked { background: #ff6b6b; }
.bk-legend-dot.today-d { width: 7px; height: 7px; border-radius: 2px; border: 1.5px solid var(--ore); background: transparent; }

/* RIGHT: schedule card */
.bk-sch-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e8eaed;
    overflow: hidden;
}
.bk-sch-header {
    background: var(--ink);
    padding: 14px 20px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    flex-wrap: wrap;
}
.bk-sch-date {
    font-family: 'Kanit', sans-serif;
    font-size: 15px; font-weight: 700; color: #fff;
}
.bk-sch-time { font-size: 12px; color: rgba(255,255,255,.45); }
.bk-sch-courts { display: flex; gap: 6px; flex-wrap: wrap; }

/* ─── ปุ่มแท็บรายชื่อสนามในแถบสีดำ (Tab เลือกสนาม) ─── */
.bk-court-pill {
    font-family: 'Kanit', sans-serif;
    font-size: 11px; font-weight: 600;
    padding: 6px 14px; border-radius: 6px;
    background: rgba(255,255,255,.1); color: rgba(255,255,255,.7);
    letter-spacing: .04em; cursor: pointer;
    transition: all .2s ease;
    border: 1px solid transparent;
}
.bk-court-pill:hover {
    background: rgba(255,255,255,.2);
    color: #fff;
}
.bk-court-pill.active {
    background: var(--ore);
    color: #fff;
    border-color: rgba(255,255,255,.3);
    box-shadow: 0 2px 8px rgba(232,108,42,.4);
}

.sch-wrap { overflow-y: auto; max-height: 380px; }
.sch-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.sch-table thead th {
    padding: 10px 14px;
    background: #f8f9fa;
    font-family: 'Kanit', sans-serif;
    font-size: 11px; font-weight: 700; color: #495057;
    text-align: center; text-transform: uppercase; letter-spacing: .06em;
    border-bottom: 2px solid #e9ecef;
    position: sticky; top: 0; z-index: 1;
}
.sch-table thead th:first-child { text-align: left; width: 80px; padding-left: 18px; }
.sch-table tbody td {
    padding: 7px 14px; text-align: center;
    border-bottom: 1px solid #f1f3f5; vertical-align: middle;
}
.sch-table tbody td:first-child {
    text-align: left; padding-left: 18px;
    font-family: 'Kanit', sans-serif; font-weight: 600;
    color: #343a40; font-size: 12px; white-space: nowrap;
}
.sch-table tbody tr:last-child td { border-bottom: none; }
.sch-table tbody tr:hover td { background: #f8f9fa; }

.hour-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f1f3f5; border-radius: 6px;
    padding: 2px 8px; font-size: 11px; font-weight: 700; color: #495057;
}

.slot-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 12px; border-radius: 20px;
    font-size: 10px; font-weight: 700; letter-spacing: .03em;
    transition: transform .12s, opacity .12s;
}
.slot-free {
    background: #ebfbee; color: #2f9e44; cursor: pointer;
    border: 1px solid #b2f2bb;
}
.slot-free:hover { transform: scale(1.05); background: #d3f9d8; }
.slot-booked {
    background: #fff5f5; color: #c92a2a; cursor: default;
    border: 1px solid #ffc9c9;
}
.slot-checkout {
    background: #fff3bf; color: #e67700; cursor: default;
    border: 1px solid #ffe066;
}
.slot-past {
    background: #f1f3f5; color: #adb5bd; cursor: default;
    border: 1px solid #dee2e6;
}
.slot-closed {
    background: #fff4e6; color: #e67700; cursor: default;
    border: 1px solid #ffd8a8;
}
.slot-maintenance {
    background: #f1f3f5; color: #adb5bd; cursor: default;
    border: 1px solid #dee2e6;
}
.slot-unavailable {
    background: #f1f3f5; color: #adb5bd; cursor: default;
    border: 1px solid #dee2e6;
}

.slot-dot { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
.slot-free .slot-dot { background: #2f9e44; }
.slot-booked .slot-dot { background: #c92a2a; }
.slot-checkout .slot-dot { background: #e67700; }
.slot-past .slot-dot { background: #adb5bd; }
.slot-closed .slot-dot { background: #e67700; }
.slot-maintenance .slot-dot { background: #f29c41; }
.slot-unavailable .slot-dot { background: #adb5bd; }
.sch-empty {
    padding: 48px 20px; text-align: center; color: #adb5bd; font-size: 13px;
}
.sch-empty-icon { font-size: 32px; margin-bottom: 8px; opacity: .5; }

/* ─── COMMUNITY ─── */
.facilities-section {
    background: var(--cream);
    color: var(--ink);
    overflow: hidden;
    padding: 72px max(40px, calc((100% - var(--max-w)) / 2)) 0;
}
.facilities-head, .reviews-head {
    display: flex; align-items: flex-end; justify-content: space-between; gap: 24px;
}
.facilities-label, .reviews-label {
    font-size: 11px; font-weight: 600; letter-spacing: .2em; text-transform: uppercase; color: var(--ore); margin-bottom: 8px;
}
.facilities-title { font-size: clamp(30px, 4vw, 44px); font-weight: 800; line-height: 1.15; color: var(--ink); }
.facilities-sub { margin-top: 9px; max-width: 650px; color: #6b7280; font-size: 13px; line-height: 1.7; }
.slider-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.slider-btn {
    width: 42px; height: 42px; border-radius: 50%; border: 1px solid rgba(13,15,30,.16); background: transparent;
    color: var(--ink); cursor: pointer; font-size: 18px; transition: .2s; display: flex; align-items: center; justify-content: center;
}
.slider-btn:hover { background: var(--ore); border-color: var(--ore); color: #fff; }
.facility-track, .review-track {
    display: flex; gap: 16px; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth;
    scrollbar-width: none; padding: 26px 0 16px;
}
.facility-track::-webkit-scrollbar, .review-track::-webkit-scrollbar { display: none; }
.facility-card {
    flex: 0 0 min(72vw, 420px); height: 380px; border-radius: 16px; overflow: hidden; position: relative;
    scroll-snap-align: start; background: var(--navy-d); color: #fff;
}
.facility-card img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s; }
.facility-card:hover img { transform: scale(1.04); }
.facility-card::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(13,15,30,.95), rgba(13,15,30,.06) 72%); }
.facility-index {
    position: absolute; top: 16px; left: 16px; z-index: 2; padding: 6px 10px; border: 1px solid rgba(255,255,255,.22);
    border-radius: 20px; background: rgba(13,15,30,.5); font-size: 10px; letter-spacing: .1em;
}
.facility-card-body { position: absolute; z-index: 2; left: 0; right: 0; bottom: 0; padding: 22px; }
.facility-card-title { font-size: 24px; font-weight: 700; color: #fff; }
.facility-card-desc { margin-top: 5px; color: rgba(255,255,255,.62); font-size: 12px; line-height: 1.6; }
.facility-score { margin-top: 12px; display: flex; align-items: center; gap: 8px; }
.facility-stars, .review-stars { color: #fbbf24; letter-spacing: 1px; }
.facility-score strong { font-size: 17px; }
.facility-score small { color: rgba(255,255,255,.5); }
.reviews-wrap {
    position: relative; margin: 48px 0 72px; padding: 8px 0 0; color: var(--ink);
    background: transparent;
}
.reviews-wrap::before { display: none; }
.reviews-head { align-items: center; }
.reviews-heading-row { display: flex; align-items: center; gap: 20px; }
.reviews-title { font-size: clamp(26px, 3vw, 38px); font-weight: 800; line-height: 1.2; color: var(--ink); }
.reviews-summary { color: #7b746e; font-size: 12px; margin-top: 7px; }
.reviews-score {
    display: flex; align-items: center; gap: 12px; min-width: 130px; padding: 11px 14px; border: 1px solid #f1dfd1;
    border-radius: 14px; background: rgba(255,247,240,.9);
}
.reviews-score strong { color: var(--ink); font-size: 25px; line-height: 1; }
.reviews-score-stars { color: #fbbf24; font-size: 12px; letter-spacing: 1px; }
.reviews-score small { display: block; margin-top: 2px; color: #92877d; font-size: 9px; }
.write-review-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 44px; padding: 0 19px; border-radius: 12px;
    background: var(--ore); box-shadow: 0 10px 25px rgba(232,108,42,.25); color: #fff !important; font-size: 13px; font-weight: 700; transition: .2s;
}
.write-review-btn:hover { background: var(--ore-d); transform: translateY(-1px); }
.review-track { gap: 14px; padding: 28px 2px 2px; }
.review-card {
    position: relative; flex: 0 0 min(82vw, 460px); padding: 22px; border: 0; border-radius: 18px;
    background: #fff; box-shadow: 0 14px 38px rgba(67,45,28,.11); scroll-snap-align: start;
}
.review-user-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.review-user { display: flex; align-items: center; gap: 10px; min-width: 0; }
.review-avatar {
    width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #ff8a4c, var(--ore)); color: #fff; font-weight: 800;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.review-name { color: var(--ink); font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.review-member { color: #18976b; font-size: 10px; margin-top: 2px; }
.review-comment { margin-top: 20px; min-height: 54px; color: #5e5a56; font-size: 13.5px; line-height: 1.8; }
.review-ratings { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 12px; }
.review-rating-chip { padding: 5px 9px; border: 1px solid #f3ded0; border-radius: 20px; background: #fff5ee; color: #c85e25; font-size: 10px; }
.review-images { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; margin-top: 12px; }
.review-images img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 7px; }
.review-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 14px; color: #aaa19a; font-size: 10px; }
.reviews-empty { width: 100%; padding: 34px; border: 1px dashed #e5d8ce; border-radius: 14px; color: #9a918a; text-align: center; font-size: 13px; }

.community-section {
    background: var(--navy-d);
    padding-top: 64px;
    padding-bottom: 64px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 56px;
    align-items: center;
    padding-left:  max(40px, calc((100% - var(--max-w)) / 2));
    padding-right: max(40px, calc((100% - var(--max-w)) / 2));
}
.community-img-wrap { position: relative; }
.community-img-main { border-radius: 12px; overflow: hidden; height: 320px; }
.community-img-main img { width: 100%; height: 100%; object-fit: cover; filter: saturate(.8); transition: filter .4s; }
.community-img-main:hover img { filter: saturate(1); }
.community-stat {
    position: absolute; bottom: -20px; left: -20px;
    background: var(--ore); border-radius: 10px;
    padding: 16px 22px; min-width: 140px;
    box-shadow: 0 8px 28px rgba(232,108,42,.35);
}
.cstat-label { font-size: 10px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.8); margin-bottom: 4px; }
.cstat-num { font-family: 'Bebas Neue', sans-serif; font-size: 34px; color: #fff; line-height: 1; }
.cstat-sub { font-size: 11px; color: rgba(255,255,255,.75); margin-top: 2px; }
.community-text { padding-left: 8px; }
.community-tag { font-size: 11px; font-weight: 600; letter-spacing: .18em; text-transform: uppercase; color: var(--ore); margin-bottom: 10px; }
.community-title { font-size: clamp(26px,4vw,40px); font-weight: 800; color: #fff; line-height: 1.2; margin-bottom: 14px; }
.community-desc { font-size: 13.5px; color: rgba(255,255,255,.5); line-height: 1.85; margin-bottom: 28px; }

/* ─── PROMOTIONS ─── */
.promo-section {
    background: var(--cream);
    padding-top: 60px;
    padding-bottom: 60px;
    overflow: hidden;
}
.promo-header { text-align: center; margin-bottom: 32px; padding: 0 max(40px, calc((100% - var(--max-w)) / 2)); }
.promo-tag { font-size: 11px; font-weight: 600; letter-spacing: .18em; text-transform: uppercase; color: var(--ore); margin-bottom: 6px; }
.promo-title { font-size: clamp(40px,6vw,56px); font-weight: 800; color: var(--ink); }

.promo-container { display: block; width: 100%; padding: 0 5%; }
.promo-card {
    border-radius: 12px; overflow: hidden; height: 400px; position: relative;
    cursor: pointer; width: 100%;
}
.promo-card img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.promo-card:hover img { transform: scale(1.06); }
.promo-card::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,.7) 0%, rgba(0,0,0,.1) 100%);
    transition: opacity .3s;
}
.promo-card:hover::after { opacity: .8; }
.promo-card-inner { position: absolute; bottom: 16px; left: 16px; z-index: 1; }
.promo-card-title { font-family: 'Bebas Neue', sans-serif; font-size: 80px; color: #fff; line-height: 1.1; letter-spacing: .06em; }
.promo-card-sub { font-size: 33px; color: rgba(255, 238, 0, 0.7); margin-top: 3px; }



/* ─── FOOTER ─── */
.footer {
    background: var(--navy-d);
    padding-top: 48px;
    padding-bottom: 0;
    border-top: 1px solid var(--border);
}
.footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; padding-bottom: 36px; }
.footer-brand { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: .06em; color: #fff; margin-bottom: 4px; }
.footer-brand-sub { font-size: 12px; color: rgba(255,255,255,.35); margin-bottom: 22px; }
.footer-addr-title { font-family: 'Kanit', sans-serif; font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 6px; }
.footer-addr p { font-size: 12px; color: rgba(255,255,255,.45); line-height: 1.9; }
.footer-map { border-radius: 10px; overflow: hidden; height: 200px; border: 1px solid rgba(255,255,255,.1); margin-top: 4px; }
.footer-map iframe { width: 100%; height: 100%; border: none; }
.footer-social { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.social-badge {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    flex-shrink: 0;
    padding: 5px 14px;
    background: rgba(255,255,255,.06);
    border-radius: 20px;
    font-size: 11px;
    color: rgba(255,255,255,.5);
    transition: background .2s, color .2s;
}
.social-badge:hover { background: var(--ore); color: #fff; }
.footer-bottom {
    border-top: 1px solid var(--border); padding: 16px 0;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.footer-copy { font-size: 11px; color: rgba(255,255,255,.22); }
.footer-links { display: flex; gap: 20px; }
.footer-links a { font-size: 11px; color: rgba(255,255,255,.28); transition: color .2s; }
.footer-links a:hover { color: rgba(255,255,255,.7); }

/* ─── SCROLL TOP ─── */
#scroll-top {
    position: fixed; bottom: 22px; right: 22px;
    width: 40px; height: 40px;
    background: var(--ore); color: #fff;
    border: none; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 16px;
    opacity: 0; pointer-events: none;
    transition: opacity .3s, transform .2s;
    z-index: 300;
}
#scroll-top.show { opacity: 1; pointer-events: auto; }
#scroll-top:hover { transform: translateY(-2px); }

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {

    .nav-right { display: none; }
    .nav-hamburger { display: flex; }
    .about-section, .community-section, .footer-grid { grid-template-columns: 1fr; gap: 32px; }
    .about-section, .courts-section, .booking-section,
    .community-section, .footer {
        padding-left: 20px !important;
        padding-right: 20px !important;
    }
    .promo-header { padding-left: 20px !important; padding-right: 20px !important; }
    .booking-layout { grid-template-columns: 1fr; }
    .community-stat { left: 0; bottom: -16px; }
    .community-img-wrap { margin-bottom: 36px; }
    .hero-stats { display: none; }
    .courts-grid { grid-template-columns: repeat(2,1fr); }
    .courses-section { padding-left: 20px !important; padding-right: 20px !important; }
    .courses-grid { grid-template-columns: repeat(2,1fr); }
    .facilities-section { padding-left: 20px; padding-right: 20px; }
    .facilities-head, .reviews-head { align-items: flex-start; flex-direction: column; }
    .facility-card { flex-basis: 84vw; height: 350px; }
    .reviews-wrap { margin: 36px 0 52px; padding: 4px 0 0; }
    .reviews-heading-row { align-items: flex-start; flex-direction: column; gap: 14px; }
    .reviews-score { min-width: 0; }
    .review-card { flex-basis: 88vw; }
}
@media (max-width: 480px) {
    .courts-grid { grid-template-columns: 1fr; }
    .courses-grid { grid-template-columns: 1fr; }
    .hero-title { font-size: 52px; }
}

.icon-footer{
    width: 15px;
    height: auto;
    vertical-align: middle;
    margin-right: 5px;
}

/* ─── HERO / NAV BUTTONS ─── */
.home-content .btn-primary,
.home-content .btn-white,
.home-content .nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 13px 28px;
    font-family: 'Kanit', sans-serif;
    font-size: 14px;
    font-weight: 600;
    border-radius: 8px;
    transition: background .2s, border-color .2s, transform .15s;
    white-space: nowrap;
}

/* ปุ่มส้ม (จองสนาม / สมัครสมาชิก) */
.home-content .btn-primary {
    background: var(--ore);
    color: #fff;
    border: 1px solid var(--ore);
}
.home-content .btn-primary:hover {
    background: var(--ore-d);
    border-color: var(--ore-d);
    transform: translateY(-1px);
}

/* ปุ่มขอบขาว โปร่งใส (เข้าสู่ระบบ) */
.home-content .btn-white {
    background: transparent;
    color: #fff;
    border: 1px solid rgba(255,255,255,.35);
}
.home-content .btn-white:hover {
    background: rgba(255,255,255,.08);
    border-color: rgba(255,255,255,.6);
}

/* ─── ครึ่งสนาม ─── */
.half-court-divider {
    margin: 14px 0 10px;
    border-top: 1px dashed rgba(255,255,255,.15);
}
.half-court-actions {
    display: flex;
    gap: 8px;
}
.court-btn-half {
    flex: 1;
    padding: 7px;
    background: rgba(255,255,255,.05);
    color: rgba(255,255,255,.8);
    font-family: 'Kanit', sans-serif;
    font-size: 12px;
    font-weight: 500;
    border-radius: 6px;
    text-align: center;
    border: 1px solid rgba(255,255,255,.1);
    transition: background .2s, color .2s, border-color .2s;
    pointer-events: none; /* คลิกไม่ได้แต่โฮเวอร์สีติดเหมือนเดิม */
}
.court-btn-half:hover {
    background: var(--ore);
    color: #fff;
    border-color: var(--ore);
}

/* ─── PACKAGES SECTION ─── */
.packages-section {
    background: var(--navy-d);
    padding-top: 72px;
    padding-bottom: 72px;
    padding-left:  max(40px, calc((100% - var(--max-w)) / 2));
    padding-right: max(40px, calc((100% - var(--max-w)) / 2));
}
.packages-header { text-align: center; margin-bottom: 40px; }
.packages-label {
    font-size: 11px; font-weight: 600; letter-spacing: .2em;
    text-transform: uppercase; color: var(--ore); margin-bottom: 8px;
}
.packages-title { font-size: clamp(26px, 4vw, 40px); font-weight: 800; color: #fff; margin-bottom: 8px; }
.packages-subtitle { font-size: 13.5px; color: rgba(255,255,255,.45); }

.packages-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
@media (max-width: 768px) { .packages-grid { grid-template-columns: 1fr; } }

.package-card2 {
    background: var(--navy);
    border-radius: 18px;
    padding: 28px 24px;
    border: 1px solid rgba(255,255,255,.07);
    display: flex;
    flex-direction: column;
    transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease;
    position: relative;
}
.package-card2:hover {
    transform: translateY(-6px);
    border-color: rgba(232,108,42,.4);
    box-shadow: 0 20px 36px rgba(0,0,0,.28);
}
.package-card2.featured { border-color: var(--ore); }
.package-badge-featured {
    position: absolute; top: 12px; right: 12px; left: auto; z-index: 1;
    background: var(--ore); color: #fff;
    font-size: 10px; font-weight: 700;
    padding: 5px 12px; border-radius: 20px; letter-spacing: .03em;
    box-shadow: 0 4px 10px rgba(232,108,42,.35);
}
.package-name {
    font-family: 'Kanit', sans-serif; font-size: 19px; font-weight: 700;
    color: #fff; margin-bottom: 8px;
}
.package-desc {
    font-size: 12.5px; color: rgba(255,255,255,.45);
    line-height: 1.7; margin-bottom: 22px;
    min-height: 42px;
}
.package-price-block {
    padding-top: 18px; padding-bottom: 22px;
    border-top: 1px dashed rgba(255,255,255,.1);
    margin-top: auto;
}
.package-price-label {
    font-size: 10px; font-weight: 700; color: var(--ore);
    text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px;
}
.package-price {
    font-family: 'Bebas Neue', sans-serif; font-size: 40px;
    color: #fff; letter-spacing: .02em; line-height: 1;
}
.package-price small { font-family: 'Sarabun', sans-serif; font-size: 13px; color: rgba(255,255,255,.4); font-weight: 400; }
.package-btn {
    display: block; text-align: center;
    padding: 12px; border-radius: 9px;
    background: var(--ore); color: #fff;
    font-family: 'Kanit', sans-serif; font-size: 13.5px; font-weight: 600;
    transition: background .2s;
}
.package-btn:hover { background: var(--ore-d); }

.packages-empty {
    text-align: center; color: rgba(255,255,255,.35); padding: 56px 0; font-size: 13.5px;
}
.packages-empty-icon { font-size: 34px; margin-bottom: 10px; opacity: .6; }


.package-thumb2 { height: 170px; margin: -28px -24px 20px; position: relative; overflow: hidden; border-radius: 18px 18px 0 0; }
.package-thumb2 img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
.package-card2:hover .package-thumb2 img { transform: scale(1.08); }
.package-thumb2-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(13,15,30,.55) 0%, rgba(13,15,30,0) 45%);
}
</style>

<div class="home-content">
@php
    $imagePlaceholder = "data:image/svg+xml;charset=UTF-8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500"><defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#1e2235" offset="0%"/><stop stop-color="#13162a" offset="100%"/></linearGradient></defs><rect width="800" height="500" fill="url(#g)"/><circle cx="400" cy="210" r="58" fill="#e86c2a" fill-opacity="0.18"/><path d="M260 340h280" stroke="#e86c2a" stroke-width="18" stroke-linecap="round" stroke-opacity="0.35"/><path d="M320 235l40 40 120-120" fill="none" stroke="#f6f5f0" stroke-width="22" stroke-linecap="round" stroke-linejoin="round" stroke-opacity="0.55"/></svg>');
    $imageFallback = "onerror=\"this.onerror=null;this.src='" . $imagePlaceholder . "';\"";
@endphp


<div class="mobile-menu" id="mobile-menu">
    <a href="{{ route('booking.index') }}" class="nav-link">ดูตารางสนาม</a>
    @guest
        <a href="{{ route('login') }}" class="nav-link">เข้าสู่ระบบ</a>
        <a href="{{ route('register') }}" class="nav-btn" style="margin-top:4px;text-align:center;padding:10px;">สมัครสมาชิก</a>
    @else
        <a href="{{ route('booking.index') }}" class="nav-btn" style="margin-top:4px;text-align:center;padding:10px;">จองสนาม</a>
    @endguest
</div>

{{-- ═══ HERO ═══ --}}
<section id="hero-section" class="hero" style="background-image:url('{{ $heroSlides[0] ?? ($site['hero_img_1'] ?? '') }}')">
    <div id="hero-bg-fader" class="hero-bg-fader"></div>
    <div class="hero-content" data-aos="fade-up" data-aos-duration="1200">
        <p class="hero-eyebrow">THATA SPORT HQ & Basketball Chonburi</p>
        <h1 class="hero-title">THATA<br><span>Homecourt</span></h1>
        <p class="hero-sub">ระบบจองสนามบาสเกตบอลมาตรฐานสากล<br>พร้อมให้บริการ 7 วัน 365 วัน</p>
        <div class="hero-actions" data-aos="fade-up" data-aos-delay="400">
            @guest
                <a href="{{ route('login') }}" class="btn-white">เข้าสู่ระบบ</a>
                <a href="{{ route('register') }}" class="btn-primary">สมัครสมาชิก</a>
            @else
                <a href="{{ route('booking.index') }}" class="btn-primary">จองสนาม ↗</a>
            @endguest
        </div>
    </div>
    <div class="hero-stats">
        <div class="hstat"><div class="hstat-num">{{ $courts->count() }}</div><div class="hstat-label">สนาม</div></div>
        <div class="hstat"><div class="hstat-num">7</div><div class="hstat-label">วันต่อสัปดาห์</div></div>
        <div class="hstat"><div class="hstat-num">14</div><div class="hstat-label">ชั่วโมง/วัน</div></div>
        <div class="hstat"><div class="hstat-num">24/7</div><div class="hstat-label">จองออนไลน์</div></div>
    </div>
</section>

{{-- ═══ TICKER ═══ --}}
<div class="ticker">
    <div class="ticker-track">
        <span>THATA HOMECOURT</span><span>·</span>
        <span>PREMIUM COURTS AVAILABLE</span><span>·</span>
        <span>BOOK YOUR SLOT TODAY</span><span>·</span>
        <span>PROFESSIONAL STANDARD</span><span>·</span>
        <span>BANGSAEN BASKETBALL CLUB</span><span>·</span>
        <span>THATA HOMECOURT</span><span>·</span>
        <span>PREMIUM COURTS AVAILABLE</span><span>·</span>
        <span>BOOK YOUR SLOT TODAY</span><span>·</span>
        <span>PROFESSIONAL STANDARD</span><span>·</span>
        <span>BANGSAEN BASKETBALL CLUB</span><span>·</span>
    </div>
</div>

{{-- ═══ ABOUT ═══ --}}
<section class="about-section">
    <div>
        <p class="about-label">About Court</p>
        <h2 class="about-title">{!! nl2br(e($site['about_title'])) !!}</h2>
        <p class="about-desc">
            {{ $site['about_desc'] }}
        </p>
        <div class="about-checks">
            <div class="check-row"><div class="check-icon">✓</div>สนามได้รับรองมาตรฐาน 2 สนาม</div>
            <div class="check-row"><div class="check-icon">✓</div>แสงสว่างเพียงพอด้วยระบบ LED</div>
            <div class="check-row"><div class="check-icon">✓</div>บริการลูกค้าตลอด 7 วัน</div>
            <div class="check-row"><div class="check-icon">✓</div>จองออนไลน์ได้ 24 ชั่วโมง</div>
        </div>
        <a href="{{ route('booking.index') }}" class="btn-orange-sm">จองสนามเลย</a>
    </div>
    <div class="about-images">
        <div class="about-img main" style="height:200px;">
            <img src="{{ $site['about_img_1'] }}" alt="court" {!! $imageFallback !!}>
        </div>
        <div class="about-img" style="height:160px;">
            <img src="{{ $site['about_img_2'] }}" alt="court" {!! $imageFallback !!}>
        </div>
        <div class="about-img" style="height:160px;">
            <img src="{{ $site['about_img_3'] }}" alt="court" {!! $imageFallback !!}>
        </div>
    </div>
</section>

{{-- ═══ COURTS ═══ --}}
<section class="courts-section" data-aos="fade-up">
    <div class="courts-header">
        <p class="courts-label">Available Basketball Courts</p>
        <p class="courts-subtitle">สถานะการให้บริการสนามบาส</p>
        <p class="courts-count">{{ $courts->count() }} COURTS</p>
    </div>
    <div class="courts-grid">
        @php
        $courtImgs = [
            'https://images.unsplash.com/photo-1577416412292-747c6607f055?q=80&w=2340&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'
        ];
        @endphp
        @foreach($courts as $court)
            @php
                $isOpen = $court->court_status === 'open' &&
                    !($court->closed_from && $court->closed_until &&
                      now()->between($court->closed_from, $court->closed_until));
                $uploadedImg = \App\Models\Setting::getVal('court_img_' . $court->id);
                $img = $uploadedImg ?: $courtImgs[$loop->index % count($courtImgs)];
            @endphp

            <div class="court-card" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 150 }}">
                <div class="court-thumb">
                    <img src="{{ $img }}" alt="{{ $court->name }}" {!! $imageFallback !!}>
                    <div class="court-thumb-overlay"></div>
                    <div class="court-num">{{ $loop->iteration }}</div>
                </div>
                <div class="court-body">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                        <div class="court-name" style="margin-bottom:0;">{{ $court->name }}</div>
                        <span style="font-size: 10px; background: rgba(255,255,255,0.1); color: #ccc; padding: 2px 8px; border-radius: 4px; font-weight: 600;">Indoor</span>
                    </div>
                    <p style="font-size: 12px; color: rgba(255,255,255,0.4); margin-bottom: 12px; font-family: 'Kanit', sans-serif;">สนามบาสเกตบอลพื้นยางสังเคราะห์ มาตรฐานสากล</p>
                    <div class="court-status-row" style="margin-bottom: 16px;">
                        <div class="sdot {{ $isOpen ? 'open' : 'closed' }}"></div>
                        <span class="stext {{ $isOpen ? 'open' : 'closed' }}">
                            {{ $isOpen ? 'พร้อมให้บริการ' : 'ปิดปรับปรุง' }}
                        </span>
                    </div>
                    @if($isOpen)
                        <!-- คลิกเพื่อเลื่อนลงไปที่ตารางตารางการจองสนามพร้อมเปลี่ยนสนามอัตโนมัติ -->
                        <a href="javascript:void(0);" onclick="scrollToBookingAndSelect({{ $court->id }})" class="court-btn-book">ดูช่วงเวลา</a>

                        <div class="half-court-divider"></div>
                        <div class="half-court-actions">
                            @php
                                $sections = collect();
                                if (method_exists($court, 'sections') && $court->sections) {
                                    $sections = $court->sections;
                                } elseif (method_exists($court, 'courtSections') && $court->courtSections) {
                                    $sections = $court->courtSections;
                                } elseif (isset($court->sections)) {
                                    $sections = $court->sections;
                                }
                                $usesSectionSystem = $sections->isNotEmpty();
                            @endphp

                            @if($usesSectionSystem)
                                @php
                                    $defaultSectionId = method_exists($court, 'defaultSection') && $court->defaultSection() ? $court->defaultSection()->id : null;
                                    $halfSections = $sections->filter(function($s) use ($defaultSectionId) {
                                        $isActive = !isset($s->is_active) || $s->is_active;
                                        return $s->id !== $defaultSectionId && $isActive;
                                    });
                                @endphp

                                @if($halfSections->isNotEmpty())
                                    @foreach($halfSections as $section)
                                        <a href="javascript:void(0);" class="court-btn-half">
                                            {{ $section->name ?? 'ครึ่งสนาม' }}
                                        </a>
                                    @endforeach
                                @else
                                    <div class="court-btn-disabled" style="flex: 1; padding: 7px; font-size: 12px;">ครึ่งสนามปิดให้บริการ</div>
                                @endif

                            @else
                                <a href="javascript:void(0);" class="court-btn-half">
                                    {{ $court->half_a_name ?? 'ครึ่ง A' }}
                                </a>
                                <a href="javascript:void(0);" class="court-btn-half">
                                    {{ $court->half_b_name ?? 'ครึ่ง B' }}
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="court-btn-disabled">ไม่พร้อมให้บริการ</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ═══ BOOKING CALENDAR ═══ --}}
<section class="booking-section" id="booking-section">
    <p class="booking-label">Check Courts Booking</p>
    <h2 class="booking-title">ดูตารางการจองสนาม</h2>

    <div class="booking-layout">

        {{-- LEFT: Calendar Card --}}
        <div class="bk-cal-card">
            <div class="bk-cal-header">
                <span class="bk-cal-month-label" id="cal-month">เมษายน 2568</span>
                <div class="bk-cal-nav-btns">
                    <button onclick="calPrev()">&#8249;</button>
                    <button onclick="calNext()">&#8250;</button>
                </div>
            </div>
            <div class="bk-cal-body">
                <div class="bk-cal-days-header">
                    <span>อา</span><span>จ</span><span>อ</span><span>พ</span>
                    <span>พฤ</span><span>ศ</span><span>ส</span>
                </div>
                <div class="bk-cal-grid" id="cal-grid"></div>
            </div>
            <div class="bk-cal-legend">
                <div class="bk-legend-item"><div class="bk-legend-dot today-d"></div> วันนี้</div>
                <div class="bk-legend-item"><div class="bk-legend-dot free"></div> มีว่าง</div>
                <div class="bk-legend-item"><div class="bk-legend-dot booked"></div> เต็ม</div>
            </div>
        </div>

        {{-- RIGHT: Schedule Card --}}
        <div class="bk-sch-card">
            <div class="bk-sch-header">
                <div>
                    <div class="bk-sch-date" id="bk-date-label">เลือกวันที่เพื่อดูตาราง</div>
                    <div class="bk-sch-time" id="bk-time-label"></div>
                </div>
                <!-- แถบสีดำแสดงปุ่มเลือกสนาม (Tabs) -->
                <div class="bk-sch-courts" id="bk-court-pills"></div>
            </div>
            <div class="sch-wrap">
                <table class="sch-table">
                    <thead id="sch-thead">
                        <tr><th>เวลา</th></tr>
                    </thead>
                    <tbody id="sch-tbody">
                        <tr>
                            <td colspan="5">
                                <div class="sch-empty">
                                    <div class="sch-empty-icon">📅</div>
                                    เลือกวันที่เพื่อดูตารางจอง
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

{{-- ═══ COMMUNITY ═══ --}}
<section class="community-section" data-aos="fade-up">
    <div class="community-img-wrap" data-aos="zoom-in" data-aos-delay="200">
        <div class="community-img-main">
            <img src="{{ $site['community_img'] }}" alt="community" {!! $imageFallback !!}>
        </div>
        <div class="community-stat">
            <p class="cstat-label">โค้ช DREAM</p>
            <p class="cstat-num">12 รุ่น</p>
            <p class="cstat-sub">143 คน</p>
        </div>
    </div>
    <div class="community-text" data-aos="fade-up" data-aos-delay="400">
        <p class="community-tag">The Community</p>
        <h2 class="community-title">มาร่วมสร้างประสบการณ์ดีๆกับเรา</h2>
        <p class="community-desc">
            เข้าร่วมเครือข่ายนักบาสเกตบอลคุณภาพพบปะผู้เล่นระดับสูงและโค้ชมืออาชีพในพื้นที่บางแสน
        </p>
    </div>
</section>

{{-- ═══ COURSES / BASKETBALL SCHOOL ═══ --}}
<section class="courses-section" data-aos="fade-up">
    <div class="courses-header">
        <p class="courses-label">Basketball Clinic</p>
        <h2 class="courses-title">โรงเรียนสอนบาสเกตบอล</h2>
        <p class="courses-subtitle">คอร์สเรียนบาสเกตบอลกับโค้ชมืออาชีพ เลือกคอร์สที่ใช่สำหรับคุณ</p>
    </div>

@if(($trainingCourses ?? collect())->isEmpty())
    <div class="courses-empty">
        <div class="courses-empty-icon">🏀</div>
        ขณะนี้ยังไม่มีคอร์สเปิดรับสมัคร
    </div>
@else
    <div class="courses-grid">
        @foreach($trainingCourses as $tCourse)
            @php
                $tPackage = $tCourse->packages->first();
                $courseType = $tCourse->course_type;
            @endphp
            <div class="course-card2" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 100 }}">
                <div class="course-thumb2">
                    <img src="{{ $tCourse->image_url ?: 'https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=800&auto=format&fit=crop' }}"
                         alt="{{ $tCourse->course_name }}" {!! $imageFallback !!}>
                    <div class="course-thumb2-overlay"></div>
                    @if($tPackage && $tPackage->is_featured)
                        <span class="course-badge-featured">⭐ แนะนำ</span>
                    @endif
                    <div class="course-name2">{{ $tCourse->course_name }}</div>
                </div>
                <div class="course-body2">
                    @if($courseType === 'schedule')
                    <div class="course-meta-row">
                        @foreach($tCourse->targetGroups as $group)
                            <span class="course-chip">{{ $group->target_group }}</span>
                        @endforeach
                        <span class="course-chip course-chip-age">อายุ {{ $tCourse->age_range_label }}</span>
                    </div>

                    @if($tCourse->description)
                        <p class="course-desc2">{{ $tCourse->description }}</p>
                    @endif
                    @endif

                    @if($courseType === 'schedule')
                    <div class="course-info-block">
                        @forelse($tCourse->schedules as $tSchedule)
                            <div class="course-info-line">
                                <svg class="course-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>{{ $tSchedule->day_type_label }} · {{ \Illuminate\Support\Carbon::parse($tSchedule->start_time)->format('H:i') }}-{{ \Illuminate\Support\Carbon::parse($tSchedule->end_time)->format('H:i') }} น.</span>
                                @if($tSchedule->is_limited_spots)
                                    <span class="course-spots-tag">{{ $tSchedule->spots_label }}</span>
                                @endif
                            </div>
                        @empty
                            <div class="course-info-line course-info-muted">ยังไม่กำหนดรอบเวลาเรียน</div>
                        @endforelse
                    </div>
                    @else
                        <span class="course-chip session-chip">Session Course</span>
                        <p class="course-desc2">คอร์สรายครั้ง ดูรายละเอียดเพิ่มเติมจากภาพประกอบ</p>
                    @endif

                    @if($tPackage)
                        @if($tCourse->packages->count() > 1)
                            <div class="mb-3 flex flex-wrap gap-2">
                                @foreach($tCourse->packages as $coursePackage)
                                    <span class="course-chip">{{ $coursePackage->total_sessions }} ครั้ง · {{ number_format($coursePackage->total_price, 0) }} บาท</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="course-price-row">
                            <div>
                                <div class="course-price-tag">เริ่มต้น</div>
                                <div class="course-price">฿{{ number_format($tPackage->total_price, 0) }}</div>
                                <div class="course-price-sub">
                                    {{ $tPackage->total_sessions }} ครั้ง ({{ number_format($tPackage->price_per_session, 0) }} บาท/ครั้ง) · {{ $tPackage->validity_label }}
                                </div>
                            </div>
                            <a href="{{ route('courses.show', $tCourse) }}" class="course-btn-enroll">
                                สมัครเรียนเลย
                                <svg class="course-btn-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
</section>

@if($packages->isNotEmpty())
    {{-- ═══ PACKAGES ═══ --}}
    <section class="packages-section" id="packages" data-aos="fade-up">
        <div class="packages-header">
            <p class="packages-label">Package</p>
            <h2 class="packages-title">แพ็กเกจจองเทรนเนอร์ส่วนตัว</h2>
            <p class="packages-subtitle">เลือกแพ็กเกจที่เหมาะกับความต้องการของคุณ</p>
        </div>

        @if($packages->isEmpty())
            <div class="packages-empty">
                <div class="packages-empty-icon">📦</div>
                ขณะนี้ยังไม่มีแพ็กเกจเปิดให้บริการ
            </div>
        @else
            <div class="packages-grid">
                @foreach($packages as $package)
                    <div class="package-card2 {{ $loop->first ? 'featured' : '' }}" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 100 }}">
                        <div class="package-thumb2">
                            <img src="{{ $package->image ? asset('storage/' . $package->image) : 'https://images.unsplash.com/photo-1519861531473-9200262188bf?q=80&w=800&auto=format&fit=crop' }}"
                                 alt="{{ $package->name }}" {!! $imageFallback !!}>
                            <div class="package-thumb2-overlay"></div>
                        </div>
                        <p class="package-name">{{ $package->name }}</p>
                        <p class="package-desc">{{ $package->description }}</p>
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:14px;">
                            <svg style="width:14px;height:14px;color:var(--ore);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span style="font-size:12.5px;color:rgba(255,255,255,.6);">
                                ใช้ได้ <strong style="color:#fff;">{{ $package->num_of_use }}</strong> ครั้ง
                            </span>
                        </div>
                        <div class="package-price-block">
                            <p class="package-price-label">ราคา</p>
                            <p class="package-price">฿{{ number_format($package->price, 0) }}</p>
                        </div>
                        @guest
                        <a href="{{ route('login') }}" class="package-btn">เข้าสู่ระบบเพื่อซื้อ</a>
                        @else
                        <form action="{{ route('package-checkout.purchase', $package) }}" method="POST" style="margin:0;">
                            @csrf
                            <button type="submit" class="package-btn" style="width:100%;border:none;cursor:pointer;font:inherit;">
                                เลือกแพ็กเกจนี้
                            </button>
                        </form>
                        @endguest
                        </div>
                @endforeach
            </div>
        @endif
    </section>
@endif

{{-- ═══ PROMOTIONS ═══ --}}
<section class="promo-section" data-aos="fade-up">
    <div class="promo-header">
        <p class="promo-tag">{{ $site['promo_subtitle'] }}</p>
        <h2 class="promo-title">{{ $site['promo_title'] }}</h2>
    </div>

    <a href="{{ route('booking.index') }}" class="promo-container" aria-label="ไปหน้าจองสนาม">
        @php
            $promoImg = $site['promo_image'];
            $promoTitle = $site['promo_card_title'];
            $promoSub = $site['promo_card_sub'];
        @endphp

        <div class="promo-card">
            @if($promoImg)
                <img src="{{ $promoImg }}" alt="promotion" {!! $imageFallback !!}>
            @else
                <div style="width:100%; height:100%; background:linear-gradient(135deg, var(--ore) 0%, var(--ore-d) 100%); display:flex; align-items:center; justify-content:center;">
                    <div style="text-align:center; color:#fff;">
                        <div style="font-size:16px; font-weight:600;">Promotion</div>
                    </div>
                </div>
            @endif
            <div class="promo-card-inner">
                <div class="promo-card-title">{{ $promoTitle }}</div>
                <div class="promo-card-sub">{{ $promoSub }}</div>
            </div>
        </div>
    </a>
</section>

{{-- ═══ FACILITIES & MEMBER REVIEWS ═══ --}}
@include('partials.home-facilities')

{{-- ═══ FOOTER ═══ --}}
<footer class="footer" data-aos="fade-in">
    <div class="footer-grid">
        <div>
            <p class="footer-brand">THATA Homecourt</p>
            <p class="footer-brand-sub"> - THATA SPORT HQ & Basketball Chonburi</p>
            <div class="footer-addr">
                <p class="footer-addr-title">สถานที่</p>
                <p>
                    บริษัท ทาท่า สปอร์ต จำกัด (สำนักงานใหญ่)<br>
                    17/87 ซอยนพรัตน์ ถ.พระยาสัจจา<br>
                    ต.บางปลาสร้อย อ.เมืองชลบุรี<br>
                    จ.ชลบุรี 20000
                </p>
            </div>
            <div style="margin-top:20px;">
                <p class="footer-addr-title" style="margin-bottom:10px;">ติดตามข่าวสาร</p>
                <div class="footer-social">
                    <a href="https://www.facebook.com/thatahomecourts/" class="social-badge" target="_blank">
                        <img class="icon-footer" src="https://cdn-icons-png.flaticon.com/128/5968/5968764.png" alt="" {!! $imageFallback !!}> THATA Homecourt - THATA SPORT HQ & Basketball Chonburi
                    </a>
                    <a href="https://www.youtube.com/THATASPORT" class="social-badge" target="_blank">
                        <img class="icon-footer" src="https://cdn-icons-png.flaticon.com/128/1384/1384060.png" alt="">THATA SPORT
                    </a>
                    <a href="https://www.instagram.com/thata_homecourt" class="social-badge" target="_blank">
                        <img class="icon-footer" src="https://128/174/174855.png" alt="">thata_homecourt
                    </a>
                    <a href="https://line.me/R/ti/p/%40THATA-HC" class="social-badge" target="_blank">
                        <img class="icon-footer" src="https://cdn-icons-png.flaticon.com/128/2111/2111498.png" alt="">THATA Homecourt
                    </a>
                    <a href="javascript:void(0)" onclick="copyPhone(this)" class="social-badge">
                        <img class="icon-footer" src="https://cdn-icons-png.flaticon.com/128/5585/5585856.png" alt="">
                        <span class="phone-num">081-246-0000</span>
                    </a>
                </div>
            </div>
        </div>
        <div>
            <p class="footer-addr-title" style="margin-bottom:12px;">แผนที่</p>
            <div class="footer-map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d62113.64127603402!2d100.8897096!3d13.3438947!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311d4bd8ee3abfa9%3A0x49305e14f78f3b2c!2zVEhBVEEgSE9NRUNPVVJUIOKAkyBUSEFUQSBTUE9SVCBIUSAmIEJhc2tldGJhbGwgKENob25idXJpKSDguJfguLLguJfguYjguLIg4Liq4Liz4LiZ4Lix4LiB4LiH4Liy4LiZ4LmD4Lir4LiN4LmIIOC5geC4peC4sOC4quC4meC4suC4oeC4muC4suC4quC5gOC4geC4leC4muC4reC4pSDguIrguKXguJrguLjguKPguLU!5e0!3m2!1sen!2sth!4v1783309229694!5m2!1sen!2sth" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p class="footer-copy">© 2026 THATA HOMECOURT – THATA SPORT HQ & Basketball (Chonburi).</p>
        <div class="footer-links">
            <a href="https://www.facebook.com/thatahomecourts/" target="_blank">ติดตามเรา</a>
            <a href="mailto:thatahomecourt@gmail.com" target="_blank">ติดต่อ</a>
        </div>
    </div>
</footer>

<button id="scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<script>
const HERO_SLIDES = @json($heroSlides);

/**
 * แปลงข้อมูลสนามและดึงแป้นย่อย (Sections / ครึ่งสนาม) ของแต่ละสนามจากฐานข้อมูล
 * เพื่อนำมาใช้สร้าง Tabs เลือกสนามและคอลัมน์ในตารางจอง
 */
@php
    $courtsAllData = $courts->map(function($c) {
        $sections = collect();
        if (method_exists($c, 'sections') && $c->sections) {
            $sections = $c->sections;
        } elseif (method_exists($c, 'courtSections') && $c->courtSections) {
            $sections = $c->courtSections;
        } elseif (isset($c->sections)) {
            $sections = $c->sections;
        }

        $defaultSectionId = method_exists($c, 'defaultSection') && $c->defaultSection() ? $c->defaultSection()->id : null;

        // กรองเอาเฉพาะครึ่งสนามหรือแป้นย่อยที่ไม่ใช่เต็มสนามหลัก
        $halfSections = $sections->filter(function($s) use ($defaultSectionId) {
            $isActive = !isset($s->is_active) || $s->is_active;
            return $s->id !== $defaultSectionId && $isActive;
        });

        // ถ้ามีข้อมูล Sections ให้ใช้
        if ($halfSections->isNotEmpty()) {
            $sectionsList = $halfSections->map(fn($s) => ['id' => $c->id, 'section_id' => $s->id, 'name' => $s->name])->values();
        } else {
            // เช็คว่าเคยแบ่งครึ่งสนามแบบระบบเดิม (มีชื่อครึ่ง A/B) ไว้หรือไม่
            if (!empty($c->half_a_name) || !empty($c->half_b_name)) {
                $sectionsList = collect([
                    ['id' => $c->id, 'section_id' => 'half_a', 'name' => $c->half_a_name ?? 'ครึ่ง A'],
                    ['id' => $c->id, 'section_id' => 'half_b', 'name' => $c->half_b_name ?? 'ครึ่ง B']
                ]);
            } else {
                // กรณียังไม่มีการแบ่งสนามเลย ให้แสดงเป็นแบบเต็มสนาม (1 คอลัมน์)
                $sectionsList = collect([
                    ['id' => $c->id, 'section_id' => null, 'name' => 'เต็มสนาม']
                ]);
            }
        }

        return [
            'id' => $c->id,
            'name' => $c->name,
            'sections' => $sectionsList
        ];
    })->values();
@endphp

const COURTS_DATA = @json($courtsAllData);
let currentCourtId = COURTS_DATA.length > 0 ? COURTS_DATA[0].id : null;

/**
 * ดึงรายการแป้นย่อย (2 แป้น) ของสนามที่ผู้ใช้งานกำลังเลือกดูอยู่ปัจจุบัน
 */
function getActiveCourts() {
    const court = COURTS_DATA.find(c => c.id == currentCourtId);
    if (!court) return [];
    return court.sections.map(s => ({
        id: court.id,
        section_id: s.section_id,
        name: s.name
    }));
}

/**
 * ฟังก์ชันสำหรับเลื่อนหน้าจอไปยังส่วนตารางจองและเลือกสนามนั้นทันที
 */
function scrollToBookingAndSelect(courtId) {
    currentCourtId = courtId;
    buildCourtHeaders();
    if (selDate) {
        renderSch(selDate);
    }
    const bookingSection = document.getElementById('booking-section');
    if (bookingSection) {
        bookingSection.scrollIntoView({ behavior: 'smooth' , block: 'center'});
    }
}

/**
 * ระบบสลับภาพพื้นหลัง Hero Section อัตโนมัติ
 */
function initHeroSlideshow() {
    const hero = document.getElementById('hero-section');
    const fader = document.getElementById('hero-bg-fader');
    if (!hero || !Array.isArray(HERO_SLIDES) || HERO_SLIDES.length <= 1) return;

    let idx = 0;
    const uniqueSlides = [...new Set(HERO_SLIDES.filter(Boolean))];
    if (uniqueSlides.length <= 1) return;

    const rotationMs = 5000;
    const fadeMs = 900;

    setInterval(() => {
        const nextIdx = (idx + 1) % uniqueSlides.length;
        const nextImage = uniqueSlides[nextIdx];
        if (!fader) {
            idx = nextIdx;
            hero.style.backgroundImage = `url('${nextImage}')`;
            return;
        }

        fader.style.backgroundImage = `url('${nextImage}')`;
        fader.style.opacity = '1';

        setTimeout(() => {
            hero.style.backgroundImage = `url('${nextImage}')`;
            fader.style.opacity = '0';
            idx = nextIdx;
        }, fadeMs);
    }, rotationMs);
}

document.querySelectorAll('[data-slider]').forEach(button => {
    button.addEventListener('click', () => {
        const track = document.getElementById(button.dataset.slider);
        if (!track || !track.firstElementChild) return;
        const distance = track.firstElementChild.getBoundingClientRect().width + 16;
        track.scrollBy({ left: distance * Number(button.dataset.direction), behavior: 'smooth' });
    });
});

// ─── SCROLL TOP ───
// ควบคุมการแสดงผลปุ่ม Scroll to Top
window.addEventListener('scroll', () => {
    document.getElementById('scroll-top').classList.toggle('show', window.scrollY > 300);
});

/**
 * สร้างปุ่มเลือกสนาม (Tabs) ด้านบน และหัวตารางคอลัมน์แป้นย่อย (2 แป้น) ตามสนามที่เลือก
 */
function buildCourtHeaders() {
    const pills = document.getElementById('bk-court-pills');
    const thead = document.getElementById('sch-thead');

    // 1. สร้างปุ่มแท็บรายชื่อสนามในแถบสีดำ
    let pillsHtml = '';
    COURTS_DATA.forEach(c => {
        const activeClass = c.id == currentCourtId ? 'active' : '';
        pillsHtml += `<span class="bk-court-pill ${activeClass}" onclick="changeCourt(${c.id})">${c.name}</span>`;
    });
    pills.innerHTML = pillsHtml;

    // 2. สร้าง Header คอลัมน์ตารางเฉพาะ 2 แป้นของสนามที่เลือก
    let activeCourts = getActiveCourts();
    let theadHtml = '<tr><th>เวลา</th>';
    activeCourts.forEach(c => {
        theadHtml += `<th>${c.name}</th>`;
    });
    theadHtml += '</tr>';
    thead.innerHTML = theadHtml;
}

/**
 * ฟังก์ชันทำงานเมื่อผู้ใช้คลิกเปลี่ยนสนามที่แท็บด้านบน
 */
function changeCourt(courtId) {
    currentCourtId = courtId;
    buildCourtHeaders();
    if (selDate) {
        renderSch(selDate); // โหลดตารางเวลาใหม่ตามสนามที่เปลี่ยน
    }
}

// ─── CALENDAR ENGINE ───
const MONTHS = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

let calYear, calMonth, selDate = null;
let monthStatus = {};

// โหลดสถานะการจองรายเดือนจากระบบเพื่อทำจุดสีแสดงสถานะในปฏิทิน
async function loadMonthStatus() {
    const m = calYear + '-' + String(calMonth + 1).padStart(2,'0');
    try {
        const res = await fetch('{{ route("month.availability") }}?month=' + m);
        monthStatus = (await res.json()).days || {};
    } catch (e) {
        monthStatus = {};
    }
    renderCal();
}

// วาดปฏิทินรายเดือน
function renderCal() {
    const dim = new Date(calYear, calMonth + 1, 0).getDate();
    const fd  = new Date(calYear, calMonth, 1).getDay();
    const td  = new Date();
    document.getElementById('cal-month').textContent = MONTHS[calMonth] + ' ' + (calYear + 543);
    const g = document.getElementById('cal-grid');
    g.innerHTML = '';
    for (let i = 0; i < fd; i++) {
        const el = document.createElement('div'); el.className = 'bk-day empty'; g.appendChild(el);
    }
    for (let d = 1; d <= dim; d++) {
        const ds = calYear + '-' + String(calMonth + 1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        const el = document.createElement('div');
        let cls = 'bk-day';
        const isPast = new Date(calYear, calMonth, d) < new Date(td.getFullYear(), td.getMonth(), td.getDate());
        if (td.getFullYear()===calYear && td.getMonth()===calMonth && td.getDate()===d) cls += ' today';
        if (selDate === ds) cls += ' selected';
        const dsStatus = monthStatus[ds];
        if (isPast || dsStatus === 'past') {
            /* วันในอดีต */
        } else if (dsStatus === 'full') {
            cls += ' is-full';    // จุดสีแดง (เต็ม)
        } else {
            cls += ' has-free';   // จุดสีเขียว (มีว่าง)
        }
        el.className = cls; el.textContent = d;
        el.onclick = () => selectDate(ds, d);
        g.appendChild(el);
    }
}

// เมื่อผู้ใช้เลือกวันที่ในปฏิทิน
function selectDate(ds, d) {
    selDate = ds;
    renderCal();
    const mo  = parseInt(ds.split('-')[1]) - 1;
    const yr  = parseInt(ds.split('-')[0]);
    const now = new Date();
    const dayNames = ['อาทิตย์','จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์'];
    const dow = new Date(yr, mo, d).getDay();
    document.getElementById('bk-date-label').textContent =
        'วัน' + dayNames[dow] + ' ที่ ' + d + ' ' + MONTHS[mo] + ' ' + (yr + 543);
    document.getElementById('bk-time-label').textContent =
        'อัพเดทเมื่อ ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ' น.';
    renderSch(ds);
}

// สร้างตารางแสดงช่วงเวลาและสถานะการจองของแป้นในแต่ละวัน
async function renderSch(ds) {
    let activeCourts = getActiveCourts();
    const tb = document.getElementById('sch-tbody');
    const colCount = activeCourts.length + 1;
    tb.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center;padding:24px;color:#adb5bd;font-size:13px;">กำลังโหลดข้อมูล...</td></tr>`;

    let slots = {};
    try {
        const res  = await fetch('{{ route("schedule") }}?date=' + ds);
        const data = await res.json();
        slots = data.slots || {};
    } catch (e) {
        tb.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center;padding:24px;color:#e53e3e;font-size:13px;">ไม่สามารถโหลดข้อมูลได้</td></tr>`;
        return;
    }

    const now = new Date();
    const todayDs = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
    const currentTotalMinutes = now.getHours() * 60 + now.getMinutes();

    tb.innerHTML = '';

    const startDayMinutes = 6 * 60;  // เริ่มต้น 06:00 น.
    const endDayMinutes = 22 * 60;   // สิ้นสุด 22:00 น.

    // วนลูปสร้างตารางทีละช่วง 30 นาที
    for (let m = startDayMinutes; m < endDayMinutes; m += 30) {
        const startH = Math.floor(m / 60);
        const startM = m % 60;
        const endH = Math.floor((m + 30) / 60);
        const endM = (m + 30) % 60;

        const startStr = String(startH).padStart(2, '0') + ':' + String(startM).padStart(2, '0');
        const endStr = String(endH).padStart(2, '0') + ':' + String(endM).padStart(2, '0');
        const startTime = startStr + ':00';

        const tr = document.createElement('tr');
        let html = `<td><span class="hour-chip">${startStr} - ${endStr}</span></td>`;
        const isTimePast = (ds === todayDs) && (currentTotalMinutes >= m);

        activeCourts.forEach(c => {
            let courtSlots = slots[c.id] || {};
            let status = courtSlots[startTime] || 'available';

            if (c.section_id && slots[c.section_id]) {
                status = slots[c.section_id][startTime] || status;
            }

            if (isTimePast) {
                status = 'past';
            }

            // แสดงสถานะรูปแบบปุ่มต่างๆ ตามเงื่อนไข
            if (status === 'pending_payment') {
                html += '<td><span class="slot-badge slot-checkout"><span class="slot-dot"></span>กำลังจอง</span></td>';
            } else if (status === 'booked') {
                html += '<td><span class="slot-badge slot-booked"><span class="slot-dot"></span>จอง</span></td>';
            } else if (status === 'past') {
                html += '<td><span class="slot-badge slot-past"><span class="slot-dot"></span>ผ่านมาแล้ว</span></td>';
            } else if (status === 'closed') {
                html += '<td><span class="slot-badge slot-closed"><span class="slot-dot"></span>ปิด</span></td>';
            } else if (status === 'maintenance') {
                html += '<td><span class="slot-badge slot-maintenance"><span class="slot-dot"></span>ปิดปรับปรุง</span></td>';
            } else if (status === 'unavailable') {
                html += '<td><span class="slot-badge slot-unavailable"><span class="slot-dot"></span>ปิดชั่วคราว</span></td>';
            } else {
                html += `<td><span class="slot-badge slot-free" onclick="bookSlot('${startStr}',${c.id},'${c.section_id}','${ds}')"><span class="slot-dot"></span>ว่าง</span></td>`;
            }
        });
        tr.innerHTML = html;
        tb.appendChild(tr);
    }
}

/**
 * นำทางไปยังหน้าจองสนามเมื่อคลิกช่องเวลาที่ว่าง
 */
function bookSlot(h, courtId, sectionId, ds) {
    let url = '{{ route("booking.index") }}?date=' + ds + '&hour=' + h + '&court_id=' + courtId;
    if (sectionId && sectionId !== 'null' && sectionId !== 'undefined') {
        if (!isNaN(sectionId)) {
            url += '&court_section_id=' + sectionId;
        } else {
            url += '&type=' + sectionId;
        }
    }
    window.location.href = url;
}

// ควบคุมการเปลี่ยนเดือนในปฏิทิน
function calPrev() {
    const now = new Date();
    if (calYear === now.getFullYear() && calMonth === now.getMonth()) return;
    calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; } loadMonthStatus();
}
function calNext() {
    const now = new Date();
    const maxMonth = now.getMonth() + 1;
    const maxYear = now.getFullYear() + (maxMonth > 11 ? 1 : 0);
    const maxMo = maxMonth % 12;
    if (calYear === maxYear && calMonth === maxMo) return;
    calMonth++; if (calMonth > 11) { calMonth = 0; calYear++; } loadMonthStatus();
}

// เริ่มต้นโหลดระบบปฏิทินและตารางเมื่อหน้าเว็บโหลดเสร็จ
const now2 = new Date();
calYear = now2.getFullYear();
calMonth = now2.getMonth();
buildCourtHeaders();
renderCal();
loadMonthStatus();
const todayDs = calYear + '-' + String(calMonth+1).padStart(2,'0') + '-' + String(now2.getDate()).padStart(2,'0');
selectDate(todayDs, now2.getDate());
initHeroSlideshow();

/**
 * คัดลอกเบอร์โทรศัพท์พร้อมแสดงเอฟเฟกต์แจ้งเตือน
 */
function copyPhone(btn) {
    const phone = '081-246-0000';
    const doFeedback = () => {
        const label = btn.querySelector('.phone-num');
        const original = label.textContent;
        label.textContent = 'คัดลอกแล้ว!';
        btn.style.background = 'var(--green)';
        setTimeout(() => {
            label.textContent = original;
            btn.style.background = '';
        }, 1800);
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(phone).then(doFeedback).catch(() => fallbackCopy(phone, doFeedback));
    } else {
        fallbackCopy(phone, doFeedback);
    }
}

function fallbackCopy(text, cb) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); cb(); } catch (e) {}
    document.body.removeChild(ta);
}
</script>

</div>
@endsection
