@extends('layouts.app')

@section('title', 'THATA Homecourt - THATA SPORT HQ & Basketball Chonburi ')

@section('content')
@php
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
    /* ใส่ Overlay สีดำจางๆ ทับรูปสนามบาส */
    background: linear-gradient(rgba(0, 31, 63, 0.8), rgba(0, 17, 34, 0.8)),
                url('{{ $site['courts_bg'] }}');
    background-size: cover;      /* ให้รูปขยายเต็มพื้นที่ */
    background-position: center;
    background-attachment: fixed; /* (Optional) ทำเอฟเฟกต์ Parallax ให้รูปนิ่งขณะเลื่อน */

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
.bk-sch-courts { display: flex; gap: 6px; }
.bk-court-pill {
    font-size: 10px; font-weight: 600;
    padding: 3px 10px; border-radius: 20px;
    background: rgba(255,255,255,.1); color: rgba(255,255,255,.7);
    letter-spacing: .04em;
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
.slot-past .slot-dot { background: #adb5bd; }
.slot-closed .slot-dot { background: #e67700; }
.slot-maintenance .slot-dot { background: #f29c41; }
.slot-unavailable .slot-dot { background: #adb5bd; }
.sch-empty {
    padding: 48px 20px; text-align: center; color: #adb5bd; font-size: 13px;
}
.sch-empty-icon { font-size: 32px; margin-bottom: 8px; opacity: .5; }

/* ─── COMMUNITY ─── */
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
            <div class="check-row"><div class="check-icon">✓</div>สนามได้รับรองมาตรฐาน 4 สนาม</div>
            <div class="check-row"><div class="check-icon">✓</div>แสงสว่างเพียงพอ ระบบ LED</div>
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
                        <a href="{{ route('booking.index', ['court_id' => $court->id]) }}" class="court-btn-book">จอง</a>
                    @else
                        <div class="court-btn-disabled">ไม่พร้อมให้บริการ</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ═══ BOOKING CALENDAR ═══ --}}
<section class="booking-section">
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
                @php $tPackage = $tCourse->packages->first(); @endphp
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
                        <div class="course-meta-row">
                            @foreach($tCourse->targetGroups as $group)
                                <span class="course-chip">{{ $group->target_group }}</span>
                            @endforeach
                            <span class="course-chip course-chip-age">อายุ {{ $tCourse->age_range_label }}</span>
                        </div>

                        @if($tCourse->description)
                            <p class="course-desc2">{{ $tCourse->description }}</p>
                        @endif

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

                        @if($tPackage)
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
                        <img class="icon-footer" src="https://cdn-icons-png.flaticon.com/128/174/174855.png" alt="">thata_homecourt
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

// ─── COURTS DATA จาก DB ───
const COURTS = @json($courts->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values());

// ─── HERO SLIDESHOW ───
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

// ─── SCROLL TOP ───
window.addEventListener('scroll', () => {
    document.getElementById('scroll-top').classList.toggle('show', window.scrollY > 300);
});

// ─── BUILD COURT HEADERS (dynamic) ───
function buildCourtHeaders() {
    const pills = document.getElementById('bk-court-pills');
    const thead = document.getElementById('sch-thead');
    let pillsHtml = '';
    let theadHtml = '<tr><th>เวลา</th>';
    COURTS.forEach(c => {
        pillsHtml += `<span class="bk-court-pill">${c.name}</span>`;
        theadHtml += `<th>${c.name}</th>`;
    });
    theadHtml += '</tr>';
    pills.innerHTML = pillsHtml;
    thead.innerHTML = theadHtml;
}

// ─── CALENDAR ENGINE ───
const MONTHS = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

const HOURS = [];
for (let h = 6; h < 22; h++) HOURS.push(String(h).padStart(2,'0') + ':00');

let calYear, calMonth, selDate = null;
let monthStatus = {};   // { '2026-07-05':'full', '2026-07-06':'free', ... } จาก backend

// ดึงสรุปว่าง/เต็มของเดือนที่กำลังแสดง แล้ววาดปฏิทินใหม่
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
        const dsStatus = monthStatus[ds];   // 'free' | 'full' | 'past' | undefined
        if (isPast || dsStatus === 'past') {
            /* วันผ่านแล้ว — ไม่มีจุด */
        } else if (dsStatus === 'full') {
            cls += ' is-full';    // จุดแดง = เต็มทุก slot
        } else {
            cls += ' has-free';   // จุดเขียว = ยังมีว่าง (default ระหว่างรอโหลด)
        }
        el.className = cls; el.textContent = d;
        el.onclick = () => selectDate(ds, d);
        g.appendChild(el);
    }
}

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

async function renderSch(ds) {
    const tb = document.getElementById('sch-tbody');
    const colCount = COURTS.length + 1;
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
    const currentHour = now.getHours(); // ดึงชั่วโมงปัจจุบัน (เช่น 10)

    tb.innerHTML = '';
    HOURS.forEach(h => {
        const startTime = h + ':00'; // e.g. '10:00:00'
        const hEnd = String(parseInt(h) + 1).padStart(2,'0') + ':00'; // '10:00' → '11:00'
        const tr = document.createElement('tr');
        let html = '<td><span class="hour-chip">' + h + ' - ' + hEnd + '</span></td>';
        
        // เช็กว่ารอบนี้เป็นรอบที่ผ่านมาแล้วหรือยัง (สำหรับเคสที่เป็นวันนี้)
        const isTimePast = (ds === todayDs) && (currentHour >= parseInt(h));

        COURTS.forEach(c => {
            let status = (slots[c.id] && slots[c.id][startTime]) || 'available';

            // ถ้าเวลาปัจจุบันเลยเวลาเริ่มรอบมาแล้ว ให้บังคับเป็นสถานะ 'past' ทันที
            if (isTimePast) {
                status = 'past';
            }

            if (status === 'booked') {
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
                html += `<td><span class="slot-badge slot-free" onclick="bookSlot('${h}',${c.id},'${ds}')"><span class="slot-dot"></span>ว่าง</span></td>`;
            }
        });
        tr.innerHTML = html;
        tb.appendChild(tr);
    });
}

function bookSlot(h, courtId, ds) {
    window.location.href = '{{ route("booking.index") }}?date=' + ds + '&hour=' + h + '&court_id=' + courtId;
}

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

const now2 = new Date();
calYear = now2.getFullYear();
calMonth = now2.getMonth();
buildCourtHeaders();
renderCal();
loadMonthStatus();   // โหลดสถานะจริงของเดือนแล้ววาดจุดสีทับ
const todayDs = calYear + '-' + String(calMonth+1).padStart(2,'0') + '-' + String(now2.getDate()).padStart(2,'0');
selectDate(todayDs, now2.getDate());
initHeroSlideshow();

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