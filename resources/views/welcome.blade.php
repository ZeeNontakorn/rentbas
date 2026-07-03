@extends('layouts.app')
@section('title', 'Basketball Court Booking System')
@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800;900&family=Sarabun:wght@300;400;500;600&display=swap');

:root {
    --org: #f97316;
    --org-d: #ea6c0c;
    --navy: #1e2235;
    --navy-d: #13162a;
    --ink: #0d0f1e;
    --cream: #f6f5f0;
    --white: #ffffff;
    --gray: #6b7280;
    --border: rgba(255,255,255,0.08);
    --green: #22c55e;
    --red: #ef4444;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: 'Sarabun', sans-serif; background: #fff; color: #374151; -webkit-font-smoothing: antialiased; }
h1,h2,h3,h4,h5 { font-family: 'Kanit', sans-serif; }
a { text-decoration: none; color: inherit; }
img { display: block; max-width: 100%; }

/* ══════════════════════════════════
   NAVBAR
══════════════════════════════════ */
.navbar {
    background: var(--navy-d);
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 48px;
    position: sticky;
    top: 0;
    z-index: 200;
    border-bottom: 1px solid var(--border);
}
.nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: 'Kanit', sans-serif;
    font-weight: 700;
    font-size: 15px;
    color: #fff;
}
.nav-logo-ball {
    width: 34px; height: 34px;
    background: var(--org);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.nav-right { display: flex; align-items: center; gap: 8px; }
.nav-link {
    padding: 7px 16px;
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,0.75);
    border-radius: 6px;
    transition: color .2s, background .2s;
}
.nav-link:hover { color: #fff; background: rgba(255,255,255,0.06); }
.nav-btn {
    padding: 8px 20px;
    background: var(--org);
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    font-weight: 600;
    border-radius: 6px;
    transition: background .2s;
}
.nav-btn:hover { background: var(--org-d); }
.nav-hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 4px; }
.nav-hamburger span { display: block; width: 22px; height: 2px; background: #fff; border-radius: 2px; transition: .3s; }
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

/* ══════════════════════════════════
   HERO BANNER
══════════════════════════════════ */
.hero {
    position: relative;
    height: 62vh;
    min-height: 400px;
    overflow: hidden;
}
.hero-slide {
    position: absolute; inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transition: opacity 1.4s ease;
}
.hero-slide.active { opacity: 1; }
.hero-slide::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(160deg, rgba(13,15,30,.78) 0%, rgba(13,15,30,.45) 60%, rgba(0,0,0,.2) 100%);
}
.hero-content {
    position: absolute; inset: 0; z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0 24px;
}
.hero-title {
    font-size: clamp(30px, 6vw, 62px);
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 10px;
    text-shadow: 0 2px 20px rgba(0,0,0,.35);
}
.hero-sub {
    font-size: 15px;
    color: rgba(255,255,255,.7);
    font-weight: 300;
    margin-bottom: 32px;
}
.hero-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
.btn-white {
    padding: 11px 28px;
    border: 2px solid rgba(255,255,255,.65);
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 14px;
    font-weight: 600;
    border-radius: 8px;
    transition: all .2s;
}
.btn-white:hover { border-color: #fff; background: rgba(255,255,255,.1); }
.btn-orange-solid {
    padding: 11px 28px;
    background: var(--org);
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 14px;
    font-weight: 600;
    border-radius: 8px;
    transition: background .2s, transform .15s;
}
.btn-orange-solid:hover { background: var(--org-d); transform: translateY(-1px); }
.hero-dots {
    position: absolute; bottom: 20px; left: 50%;
    transform: translateX(-50%);
    z-index: 3;
    display: flex; gap: 6px;
}
.hdot {
    width: 6px; height: 6px; border-radius: 3px;
    background: rgba(255,255,255,.3);
    cursor: pointer;
    transition: width .3s, background .3s;
}
.hdot.active { width: 20px; background: var(--org); }

/* ══════════════════════════════════
   ABOUT COURT
══════════════════════════════════ */
.about-section {
    background: var(--cream);
    padding: 72px 80px;
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
    color: var(--org);
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
    line-height: 1.8;
    margin-bottom: 22px;
}
.about-checks {
    display: flex;
    gap: 0;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 28px;
}
.check-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--ink);
    font-weight: 500;
}
.check-icon {
    width: 20px; height: 20px;
    background: var(--org);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #fff;
    font-size: 10px;
}
.btn-orange-sm {
    display: inline-block;
    padding: 10px 24px;
    background: var(--org);
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    transition: background .2s;
}
.btn-orange-sm:hover { background: var(--org-d); }

/* About images collage */
.about-images {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 200px 160px;
    gap: 10px;
}
.about-img {
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}
.about-img.main { grid-column: 1 / 3; }
.about-img img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform .4s ease;
}
.about-img:hover img { transform: scale(1.04); }

/* ══════════════════════════════════
   AVAILABLE COURTS (dark)
══════════════════════════════════ */
.courts-section {
    background: var(--navy-d);
    padding: 60px 80px;
}
.courts-header { text-align: center; margin-bottom: 40px; }
.courts-label {
    font-size: 20px;
    font-weight: 700;
    color: var(--org);
    font-family: 'Kanit', sans-serif;
    margin-bottom: 4px;
}
.courts-subtitle {
    font-size: 14px;
    color: rgba(255,255,255,.55);
    margin-bottom: 4px;
}
.courts-count {
    font-family: 'Kanit', sans-serif;
    font-size: 32px;
    font-weight: 900;
    color: #fff;
    letter-spacing: .02em;
}
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
.court-card:hover { border-color: rgba(249,115,22,.4); transform: translateY(-4px); }
.court-thumb {
    height: 130px;
    position: relative;
    overflow: hidden;
}
.court-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.court-card:hover .court-thumb img { transform: scale(1.06); }
.court-thumb-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(19,22,42,.95) 0%, transparent 60%);
}
.court-num {
    position: absolute;
    bottom: 10px; left: 14px;
    font-family: 'Kanit', sans-serif;
    font-size: 36px;
    font-weight: 900;
    color: rgba(255,255,255,.85);
    line-height: 1;
}
.court-body { padding: 14px 16px 16px; }
.court-name {
    font-family: 'Kanit', sans-serif;
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 6px;
}
.court-status-row {
    display: flex; align-items: center; gap: 6px;
    margin-bottom: 12px;
}
.sdot {
    width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
}
.sdot.open { background: var(--green); box-shadow: 0 0 6px rgba(34,197,94,.5); }
.sdot.closed { background: var(--red); }
.stext { font-size: 11px; font-weight: 500; }
.stext.open { color: var(--green); }
.stext.closed { color: var(--red); }
.court-btn-book {
    display: block;
    width: 100%;
    padding: 9px;
    background: var(--org);
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    font-weight: 600;
    border-radius: 6px;
    text-align: center;
    transition: background .2s;
}
.court-btn-book:hover { background: var(--org-d); }
.court-btn-disabled {
    display: block;
    width: 100%;
    padding: 9px;
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.25);
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    font-weight: 600;
    border-radius: 6px;
    text-align: center;
    cursor: not-allowed;
}

/* ══════════════════════════════════
   BOOKING CALENDAR SECTION
══════════════════════════════════ */
.booking-section {
    background: #fff;
    padding: 64px 80px;
}
.booking-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--org);
    margin-bottom: 6px;
}
.booking-title {
    font-size: clamp(22px, 3.5vw, 36px);
    font-weight: 800;
    color: var(--ink);
    margin-bottom: 28px;
}
.booking-layout {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
    align-items: start;
}

/* Promo poster */
.bk-poster {
    border-radius: 12px;
    overflow: hidden;
    height: 380px;
    position: relative;
    background: #111;
}
.bk-poster img {
    width: 100%; height: 100%;
    object-fit: cover;
    opacity: .75;
}
.bk-poster-info {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background: linear-gradient(to top, rgba(0,0,0,.88), transparent);
    padding: 24px 16px 18px;
}
.bk-poster-tag {
    font-size: 10px;
    letter-spacing: .12em;
    color: var(--org);
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.bk-poster-title {
    font-family: 'Kanit', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    line-height: 1.35;
}
.bk-poster-date {
    font-size: 11px;
    color: rgba(255,255,255,.5);
    margin-top: 4px;
}

/* Calendar + schedule panel */
.bk-panel {
    background: #f9fafb;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}
.bk-panel-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-bottom: 1px solid #e5e7eb;
    background: #fff;
    flex-wrap: wrap;
    gap: 8px;
}
.bk-panel-date { font-size: 13px; font-weight: 600; color: var(--ink); }
.bk-panel-time { font-size: 12px; color: var(--gray); }

/* Mini calendar inside panel */
.mini-cal-wrap { padding: 14px 18px; border-bottom: 1px solid #e5e7eb; background: #fff; }
.mini-cal-nav {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 10px;
}
.mini-cal-nav button {
    background: none; border: 1px solid #e5e7eb;
    border-radius: 6px; width: 26px; height: 26px;
    cursor: pointer; color: var(--gray); font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.mini-cal-nav button:hover { background: #f3f4f6; }
.mini-cal-month { font-size: 13px; font-weight: 600; color: var(--ink); }
.mini-cal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; }
.mcd { font-size: 10px; color: #9ca3af; text-align: center; padding: 3px 0; }
.mck {
    font-size: 12px; text-align: center; padding: 5px 2px;
    border-radius: 5px; cursor: pointer; color: #6b7280;
    transition: background .12s, color .12s; position: relative;
}
.mck:hover { background: #f3f4f6; color: var(--ink); }
.mck.empty { cursor: default; }
.mck.today { font-weight: 600; color: var(--org); }
.mck.selected { background: var(--org); color: #fff !important; font-weight: 600; }
.mck.has-free::after {
    content: ''; position: absolute;
    bottom: 2px; left: 50%; transform: translateX(-50%);
    width: 4px; height: 4px; border-radius: 50%; background: var(--org);
}
.mck.selected::after { background: #fff; }

/* Schedule table */
.sch-wrap { overflow-x: auto; }
.sch-table { width: 100%; border-collapse: collapse; font-size: 12px; min-width: 420px; }
.sch-table thead th {
    padding: 9px 12px;
    background: #f3f4f6;
    font-family: 'Kanit', sans-serif;
    font-size: 12px;
    font-weight: 600;
    color: var(--ink);
    text-align: center;
    border-bottom: 1px solid #e5e7eb;
}
.sch-table thead th:first-child { text-align: left; width: 100px; }
.sch-table tbody td {
    padding: 6px 12px;
    text-align: center;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}
.sch-table tbody td:first-child {
    text-align: left;
    font-weight: 600;
    color: var(--ink);
    font-size: 12px;
    white-space: nowrap;
}
.sch-table tbody tr:last-child td { border-bottom: none; }
.sch-table tbody tr:hover td { background: #fafafa; }
.slot-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 600;
    cursor: pointer; transition: opacity .15s;
}
.slot-badge:hover { opacity: .8; }
.slot-free { background: #dcfce7; color: #15803d; }
.slot-booked { background: #fee2e2; color: #b91c1c; cursor: default; }
.slot-dot { width: 5px; height: 5px; border-radius: 50%; }
.slot-free .slot-dot { background: #16a34a; }
.slot-booked .slot-dot { background: #dc2626; }

/* ══════════════════════════════════
   COMMUNITY SECTION
══════════════════════════════════ */
.community-section {
    background: var(--navy-d);
    padding: 64px 80px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 56px;
    align-items: center;
}
.community-img-wrap { position: relative; }
.community-img-main {
    border-radius: 12px;
    overflow: hidden;
    height: 320px;
}
.community-img-main img {
    width: 100%; height: 100%;
    object-fit: cover;
    filter: saturate(.8);
    transition: filter .4s;
}
.community-img-main:hover img { filter: saturate(1); }
.community-stat {
    position: absolute;
    bottom: -20px; left: -20px;
    background: var(--org);
    border-radius: 10px;
    padding: 16px 22px;
    min-width: 140px;
    box-shadow: 0 8px 28px rgba(249,115,22,.35);
}
.cstat-label {
    font-size: 10px; font-weight: 600;
    letter-spacing: .1em; text-transform: uppercase;
    color: rgba(255,255,255,.8); margin-bottom: 4px;
}
.cstat-num {
    font-family: 'Kanit', sans-serif;
    font-size: 30px; font-weight: 900;
    color: #fff; line-height: 1;
}
.cstat-sub { font-size: 11px; color: rgba(255,255,255,.75); margin-top: 2px; }
.community-text { padding-left: 8px; }
.community-tag {
    font-size: 11px; font-weight: 600;
    letter-spacing: .18em; text-transform: uppercase;
    color: var(--org); margin-bottom: 10px;
}
.community-title {
    font-size: clamp(26px, 4vw, 40px);
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
    margin-bottom: 14px;
}
.community-desc {
    font-size: 13.5px;
    color: rgba(255,255,255,.5);
    line-height: 1.8;
    margin-bottom: 28px;
}
.btn-ghost-white {
    display: inline-block;
    padding: 10px 26px;
    border: 1.5px solid rgba(255,255,255,.3);
    color: rgba(255,255,255,.75);
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    transition: all .2s;
}
.btn-ghost-white:hover { border-color: #fff; color: #fff; background: rgba(255,255,255,.06); }

/* ══════════════════════════════════
   PROMOTIONS
══════════════════════════════════ */
.promo-section {
    background: var(--cream);
    padding: 60px 80px;
}
.promo-header { text-align: center; margin-bottom: 32px; }
.promo-tag {
    font-size: 11px; font-weight: 600;
    letter-spacing: .18em; text-transform: uppercase;
    color: var(--org); margin-bottom: 6px;
}
.promo-title {
    font-size: clamp(24px, 4vw, 38px);
    font-weight: 800;
    color: var(--ink);
}
.promo-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
.promo-card {
    border-radius: 10px;
    overflow: hidden;
    height: 190px;
    position: relative;
    cursor: pointer;
}
.promo-card img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform .4s;
}
.promo-card:hover img { transform: scale(1.05); }
.promo-card::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,.65) 0%, rgba(0,0,0,.15) 100%);
    transition: opacity .3s;
}
.promo-card:hover::after { opacity: .75; }
.promo-card-inner {
    position: absolute;
    bottom: 14px; left: 14px;
    z-index: 1;
}
.promo-card-title {
    font-family: 'Kanit', sans-serif;
    font-size: 18px;
    font-weight: 800;
    color: #fff;
    text-shadow: 0 2px 8px rgba(0,0,0,.4);
    line-height: 1.1;
}
.promo-card-sub {
    font-size: 11px;
    color: rgba(255,255,255,.7);
    margin-top: 3px;
}

/* ══════════════════════════════════
   FOOTER
══════════════════════════════════ */
.footer {
    background: var(--navy-d);
    padding: 44px 80px 0;
    border-top: 1px solid var(--border);
}
.footer-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: start;
    padding-bottom: 36px;
}
.footer-brand {
    font-family: 'Kanit', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 4px;
}
.footer-brand-sub {
    font-size: 12px;
    color: rgba(255,255,255,.4);
    margin-bottom: 20px;
}
.footer-addr-title {
    font-family: 'Kanit', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 6px;
}
.footer-addr p {
    font-size: 12px;
    color: rgba(255,255,255,.45);
    line-height: 1.85;
}
.footer-map {
    border-radius: 10px;
    overflow: hidden;
    height: 150px;
    border: 1px solid rgba(255,255,255,.08);
}
.footer-map iframe {
    width: 100%; height: 100%;
    border: none;
    filter: grayscale(1) invert(1) brightness(.6);
}
.footer-bottom {
    border-top: 1px solid var(--border);
    padding: 16px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.footer-copy { font-size: 11px; color: rgba(255,255,255,.25); }
.footer-social { display: flex; gap: 6px; flex-wrap: wrap; }
.social-badge {
    padding: 5px 12px;
    background: rgba(255,255,255,.06);
    border-radius: 20px;
    font-size: 11px;
    color: rgba(255,255,255,.5);
    transition: background .2s, color .2s;
}
.social-badge:hover { background: var(--org); color: #fff; }
.footer-links { display: flex; gap: 20px; }
.footer-links a { font-size: 11px; color: rgba(255,255,255,.3); transition: color .2s; }
.footer-links a:hover { color: rgba(255,255,255,.7); }

/* ══════════════════════════════════
   SCROLL TOP
══════════════════════════════════ */
#scroll-top {
    position: fixed;
    bottom: 22px; right: 22px;
    width: 40px; height: 40px;
    background: var(--org);
    color: #fff;
    border: none; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    font-size: 14px;
    box-shadow: 0 4px 16px rgba(249,115,22,.4);
    opacity: 0; pointer-events: none;
    transition: opacity .3s, transform .2s;
    z-index: 300;
}
#scroll-top.show { opacity: 1; pointer-events: auto; }
#scroll-top:hover { transform: translateY(-2px); }

/* ══════════════════════════════════
   RESPONSIVE
══════════════════════════════════ */
@media (max-width: 1024px) {
    .about-section,
    .courts-section,
    .booking-section,
    .community-section,
    .promo-section,
    .footer { padding-left: 40px; padding-right: 40px; }
    .courts-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .navbar { padding: 0 20px; }
    .nav-right { display: none; }
    .nav-hamburger { display: flex; }
    .about-section,
    .community-section,
    .footer-grid { grid-template-columns: 1fr; gap: 32px; }
    .about-section,
    .courts-section,
    .booking-section,
    .community-section,
    .promo-section,
    .footer { padding: 44px 20px; }
    .booking-layout { grid-template-columns: 1fr; }
    .bk-poster { height: 200px; }
    .promo-grid { grid-template-columns: 1fr; }
    .community-stat { left: 0; bottom: -16px; }
    .community-img-wrap { margin-bottom: 32px; }
    .footer { padding-bottom: 0; }
}
@media (max-width: 480px) {
    .courts-grid { grid-template-columns: 1fr; }
    .hero-title { font-size: 28px; }
}
</style>

{{-- ═══════════════════════════════════════════════ --}}
{{-- NAVBAR --}}
{{-- ═══════════════════════════════════════════════ --}}
<nav class="navbar">
    <a href="{{ route('home') }}" class="nav-logo">
        <div class="nav-logo-ball">🏀</div>
        Basketball Court Booking
    </a>
    <div class="nav-right">
        <a href="{{ route('booking.index') }}" class="nav-link">ดูตารางสนาม</a>
        @guest
            <a href="{{ route('login') }}" class="nav-link">เข้าสู่ระบบ</a>
            <a href="{{ route('register') }}" class="nav-btn">สมัครสมาชิก</a>
        @else
            <a href="{{ route('booking.index') }}" class="nav-btn">จองสนาม</a>
        @endguest
    </div>
    <div class="nav-hamburger" onclick="toggleMenu()" id="hamburger">
        <span></span><span></span><span></span>
    </div>
</nav>
<div class="mobile-menu" id="mobile-menu">
    <a href="{{ route('booking.index') }}" class="nav-link">ดูตารางสนาม</a>
    @guest
        <a href="{{ route('login') }}" class="nav-link">เข้าสู่ระบบ</a>
        <a href="{{ route('register') }}" class="nav-btn" style="margin-top:4px;text-align:center;padding:10px;">สมัครสมาชิก</a>
    @else
        <a href="{{ route('booking.index') }}" class="nav-btn" style="margin-top:4px;text-align:center;padding:10px;">จองสนาม</a>
    @endguest
</div>

{{-- ═══════════════════════════════════════════════ --}}
{{-- HERO --}}
{{-- ═══════════════════════════════════════════════ --}}
<section class="hero">
    <div class="hero-slide active" style="background-image:url('https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=2000&auto=format&fit=crop')"></div>
    <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1504450758481-7338eba7524a?q=80&w=2000&auto=format&fit=crop')"></div>
    <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1519766304817-4f37bda74b38?q=80&w=2000&auto=format&fit=crop')"></div>

    <div class="hero-content">
        <h1 class="hero-title">Basketball Court Booking</h1>
        <p class="hero-sub">ยินดีต้อนรับเข้าสู่ เว็บไซต์ จองสนามบาส</p>
        <div class="hero-actions">
            @guest
                <a href="{{ route('login') }}" class="btn-white">เข้าสู่ระบบ</a>
                <a href="{{ route('register') }}" class="btn-orange-solid">สมัครสมาชิก</a>
            @else
                <a href="{{ route('booking.index') }}" class="btn-orange-solid">จองสนาม</a>
            @endguest
        </div>
    </div>
    <div class="hero-dots" id="hero-dots">
        <div class="hdot active" onclick="goSlide(0)"></div>
        <div class="hdot" onclick="goSlide(1)"></div>
        <div class="hdot" onclick="goSlide(2)"></div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════ --}}
{{-- ABOUT COURT --}}
{{-- ═══════════════════════════════════════════════ --}}
<section class="about-section">
    <div>
        <p class="about-label">About Court</p>
        <h2 class="about-title">สนามที่ได้มาตรฐาน<br>ระบบการจองที่ทันสมัย</h2>
        <p class="about-desc">
            การจะบริหารจัดการสนามบาสเกตบอลได้อย่างมีประสิทธิภาพ จำเป็นต้องมีระบบที่ดีและทันสมัย
            เราจัดหาสนามที่มีคุณภาพระดับสากล พร้อมระบบจองออนไลน์ที่สะดวก รวดเร็ว และปลอดภัย
        </p>
        <div class="about-checks">
            <div class="check-row"><div class="check-icon">✓</div> สนามได้รับรอง 4 สนาม</div>
            <div class="check-row"><div class="check-icon">✓</div> มีแสงสว่างที่เพียงพอ</div>
            <div class="check-row"><div class="check-icon">✓</div> บริการลูกค้าตลอด 24 ชั่วโมง</div>
        </div>
        <a href="{{ route('booking.index') }}" class="btn-orange-sm">จองสนาม</a>
    </div>
    <div class="about-images">
        <div class="about-img main" style="height:200px;">
            <img src="https://images.unsplash.com/photo-1504450758481-7338eba7524a?q=80&w=900&auto=format&fit=crop" alt="court">
        </div>
        <div class="about-img" style="height:160px;">
            <img src="https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=600&auto=format&fit=crop" alt="court">
        </div>
        <div class="about-img" style="height:160px;">
            <img src="https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?q=80&w=600&auto=format&fit=crop" alt="court">
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════ --}}
{{-- AVAILABLE COURTS --}}
{{-- ═══════════════════════════════════════════════ --}}
<section class="courts-section">
    <div class="courts-header">
        <p class="courts-label">Available Basketball Courts</p>
        <p class="courts-subtitle">สถานะการให้บริการสนามบาส</p>
        <p class="courts-count">{{ $courts->count() }} COURT</p>
    </div>
    <div class="courts-grid">
        @php
        $courtImgs = [
            'https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1504450758481-7338eba7524a?q=80&w=600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?q=80&w=600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1519766304817-4f37bda74b38?q=80&w=600&auto=format&fit=crop',
        ];
        @endphp
        @foreach($courts as $court)
            @php
                $isOpen = $court->court_status === 'open' &&
                    !($court->closed_from && $court->closed_until &&
                      now()->between($court->closed_from, $court->closed_until));
                $img = $courtImgs[$loop->index % count($courtImgs)];
            @endphp
            <div class="court-card">
                <div class="court-thumb">
                    <img src="{{ $img }}" alt="{{ $court->name }}">
                    <div class="court-thumb-overlay"></div>
                    <div class="court-num">{{ $loop->iteration }}</div>
                </div>
                <div class="court-body">
                    <div class="court-name">{{ $court->name }}</div>
                    <div class="court-status-row">
                        <div class="sdot {{ $isOpen ? 'open' : 'closed' }}"></div>
                        <span class="stext {{ $isOpen ? 'open' : 'closed' }}">
                            {{ $isOpen ? 'พร้อมให้บริการ' : 'ปิดปรับปรุง' }}
                        </span>
                    </div>
                    @if($isOpen)
                        <a href="{{ route('booking.index') }}" class="court-btn-book">พร้อมจอง</a>
                    @else
                        <div class="court-btn-disabled">ไม่พร้อมให้บริการ</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ═══════════════════════════════════════════════ --}}
{{-- BOOKING CALENDAR --}}
{{-- ═══════════════════════════════════════════════ --}}
<section class="booking-section">
    <p class="booking-label">Check Courts Booking</p>
    <h2 class="booking-title">ดูตารางการจองสนาม</h2>

    <div class="booking-layout">
        {{-- Promo poster --}}
        <div class="bk-poster">
            <img src="https://images.unsplash.com/photo-1519766304817-4f37bda74b38?q=80&w=600&auto=format&fit=crop" alt="promo">
            <div class="bk-poster-info">
                <p class="bk-poster-tag">กิจกรรม</p>
                <p class="bk-poster-title">เยาวกรีฑา<br>สกายแลนด์สกิลส์</p>
                <p class="bk-poster-date">วันที่ สิ้น 31 มีนาคม</p>
            </div>
        </div>

        {{-- Panel --}}
        <div class="bk-panel">
            <div class="bk-panel-top">
                <span class="bk-panel-date" id="bk-date-label">เลือกวันที่</span>
                <span class="bk-panel-time" id="bk-time-label">เวลา --:--</span>
            </div>

            {{-- Mini Calendar --}}
            <div class="mini-cal-wrap">
                <div class="mini-cal-nav">
                    <button onclick="calPrev()">&#8249;</button>
                    <span class="mini-cal-month" id="cal-month"></span>
                    <button onclick="calNext()">&#8250;</button>
                </div>
                <div class="mini-cal-grid" id="cal-grid"></div>
            </div>

            {{-- Schedule --}}
            <div class="sch-wrap">
                <table class="sch-table">
                    <thead>
                        <tr>
                            <th>เวลา</th>
                            <th>สนาม 1</th>
                            <th>สนาม 2</th>
                            <th>สนาม 3</th>
                            <th>สนาม 4</th>
                        </tr>
                    </thead>
                    <tbody id="sch-tbody">
                        <tr>
                            <td colspan="5" style="text-align:center;padding:24px;color:#9ca3af;font-size:13px;">
                                เลือกวันที่เพื่อดูตาราง
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════ --}}
{{-- COMMUNITY --}}
{{-- ═══════════════════════════════════════════════ --}}
<section class="community-section">
    <div class="community-img-wrap">
        <div class="community-img-main">
            <img src="https://images.unsplash.com/photo-1519766304817-4f37bda74b38?q=80&w=900&auto=format&fit=crop" alt="community">
        </div>
        <div class="community-stat">
            <p class="cstat-label">โค้ม DREAM</p>
            <p class="cstat-num">12 รุ่น</p>
            <p class="cstat-sub">143 คน</p>
        </div>
    </div>
    <div class="community-text">
        <p class="community-tag">The Community</p>
        <h2 class="community-title">มาร้างประสบการณ์ดีๆ<br>กับเรา</h2>
        <p class="community-desc">
            จากผู้ขึ้นชอบ บาสเกตบอลทั่วโลก<br>เข้ามาใช้งาน สนามบาสของเรา
        </p>
        <a href="{{ route('register') }}" class="btn-ghost-white">เข้าร่วมเลย</a>
    </div>
</section>

{{-- ═══════════════════════════════════════════════ --}}
{{-- PROMOTIONS --}}
{{-- ═══════════════════════════════════════════════ --}}
<section class="promo-section">
    <div class="promo-header">
        <p class="promo-tag">รับชมโปรโมชั่นสุดพิเศษ</p>
        <h2 class="promo-title">Preview Promotion</h2>
    </div>
    <div class="promo-grid">
        <div class="promo-card">
            <img src="https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=800&auto=format&fit=crop" alt="promo">
            <div class="promo-card-inner">
                <p class="promo-card-title">BASKETBALL</p>
                <p class="promo-card-sub">โปรโมชั่นพิเศษ</p>
            </div>
        </div>
        <div class="promo-card">
            <img src="https://images.unsplash.com/photo-1504450758481-7338eba7524a?q=80&w=800&auto=format&fit=crop" alt="promo">
            <div class="promo-card-inner">
                <p class="promo-card-title">BASKETBALL</p>
                <p class="promo-card-sub">ส่วนลดพิเศษ</p>
            </div>
        </div>
        <div class="promo-card">
            <img src="https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?q=80&w=800&auto=format&fit=crop" alt="promo">
            <div class="promo-card-inner">
                <p class="promo-card-title">BASKETBALL</p>
                <p class="promo-card-sub">สมาชิกใหม่</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════ --}}
{{-- FOOTER --}}
{{-- ═══════════════════════════════════════════════ --}}
<footer class="footer">
    <div class="footer-grid">
        <div>
            <p class="footer-brand">Basketball Court Booking</p>
            <p class="footer-brand-sub">BCBS · ระบบจองสนามบาสเกตบอล</p>
            <div class="footer-addr">
                <p class="footer-addr-title">สถานที่</p>
                <p>
                    บางแสน (Main)<br>
                    สนามบาส บางแสนคลับ<br>
                    169 ถ.ลงหาดบางแสน อ.ชลบุรี
                </p>
            </div>
            <div style="margin-top:20px;">
                <p class="footer-addr-title" style="margin-bottom:10px;">ติดตามข่าวสาร</p>
                <div class="footer-social">
                    <a href="#" class="social-badge">📘 สนามบาส บางแสนคลับ</a>
                    <a href="#" class="social-badge">▶ BangsanclubYT</a>
                    <a href="#" class="social-badge">🏀 BSC_THAILAND</a>
                </div>
            </div>
        </div>
        <div class="footer-map">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3876.8!2d100.924!3d13.285!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTPCsDE3JzA2LjAiTiAxMDDCsDU1JzI2LjQiRQ!5e0!3m2!1sth!2sth!4v1680000000000!5m2!1sth!2sth"
                allowfullscreen loading="lazy">
            </iframe>
        </div>
    </div>
    <div class="footer-bottom">
        <p class="footer-copy">© 2026 Basketball Booking System</p>
        <div class="footer-links">
            <a href="#">Follow us on social media</a>
            <a href="#">Terms and Conditions</a>
        </div>
    </div>
</footer>

<button id="scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<script>
// ─── HERO SLIDESHOW ───
const slides = document.querySelectorAll('.hero-slide');
const dots   = document.querySelectorAll('.hdot');
let cur = 0, timer;

function goSlide(n) {
    slides[cur].classList.remove('active');
    dots[cur].classList.remove('active');
    cur = n;
    slides[cur].classList.add('active');
    dots[cur].classList.add('active');
    clearInterval(timer);
    timer = setInterval(nextSlide, 5000);
}
function nextSlide() { goSlide((cur + 1) % slides.length); }
timer = setInterval(nextSlide, 5000);

// ─── MOBILE MENU ───
function toggleMenu() {
    document.getElementById('mobile-menu').classList.toggle('open');
}

// ─── SCROLL TOP ───
window.addEventListener('scroll', () => {
    document.getElementById('scroll-top').classList.toggle('show', window.scrollY > 300);
});

// ─── CALENDAR ───
const MONTHS = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
const DAYS   = ['อา','จ','อ','พ','พฤ','ศ','ส'];
const HOURS  = [];
for (let h = 8; h <= 21; h++) HOURS.push(String(h).padStart(2,'0') + ':00');

let calYear, calMonth, selDate = null;
const bCache = {};

function sr(s) { let st=s>>>0; return ()=>{st=(Math.imul(1664525,st)+1013904223)>>>0;return st/0x100000000;}; }
function dseed(ds) { return ds.split('-').reduce((a,b)=>(a*31+parseInt(b))|0,7); }
function getB(ds) {
    if (!bCache[ds]) { const r=sr(dseed(ds)); bCache[ds]=HOURS.map(()=>Array.from({length:4},()=>r()>0.42)); }
    return bCache[ds];
}
function hasFree(ds) { return getB(ds).flat().some(v=>!v); }

function renderCal() {
    const dim = new Date(calYear, calMonth+1, 0).getDate();
    const fd  = new Date(calYear, calMonth, 1).getDay();
    const td  = new Date();
    document.getElementById('cal-month').textContent = MONTHS[calMonth] + ' ' + (calYear+543);
    const g = document.getElementById('cal-grid');
    g.innerHTML = '';
    DAYS.forEach(d => {
        const el = document.createElement('div');
        el.className = 'mcd'; el.textContent = d; g.appendChild(el);
    });
    for (let i=0;i<fd;i++) { const el=document.createElement('div'); el.className='mck empty'; g.appendChild(el); }
    for (let d=1;d<=dim;d++) {
        const ds = calYear+'-'+String(calMonth+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
        const el = document.createElement('div');
        let cls = 'mck';
        if (td.getFullYear()===calYear&&td.getMonth()===calMonth&&td.getDate()===d) cls+=' today';
        if (selDate===ds) cls+=' selected';
        if (hasFree(ds)) cls+=' has-free';
        el.className=cls; el.textContent=d;
        el.onclick=()=>selectDate(ds,d);
        g.appendChild(el);
    }
}

function selectDate(ds, d) {
    selDate = ds;
    renderCal();
    const mo = parseInt(ds.split('-')[1])-1;
    const yr = parseInt(ds.split('-')[0]);
    const now = new Date();
    document.getElementById('bk-date-label').textContent = 'วันที่ '+d+' '+MONTHS[mo]+' '+(yr+543);
    document.getElementById('bk-time-label').textContent = 'เวลา '+String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0');
    renderSch(ds);
}

function renderSch(ds) {
    const book = getB(ds);
    const tb = document.getElementById('sch-tbody');
    tb.innerHTML = '';
    HOURS.forEach((h,hi) => {
        const tr = document.createElement('tr');
        let html = '<td>'+h+'</td>';
        for (let ci=0;ci<4;ci++) {
            const bk = book[hi][ci];
            if (bk) {
                html += '<td><span class="slot-badge slot-booked"><span class="slot-dot"></span>จอง</span></td>';
            } else {
                html += '<td><span class="slot-badge slot-free" onclick="bookSlot(\''+h+'\','+(ci+1)+',\''+ds+'\')"><span class="slot-dot"></span>ว่าง</span></td>';
            }
        }
        tr.innerHTML = html; tb.appendChild(tr);
    });
}

function bookSlot(h, c, ds) {
    const p=ds.split('-'), d=parseInt(p[2]), mo=parseInt(p[1])-1, yr=parseInt(p[0]);
    window.location.href = '{{ route("booking.index") }}?date='+ds+'&hour='+h+'&court='+c;
}

function calPrev() { calMonth--; if(calMonth<0){calMonth=11;calYear--;} renderCal(); }
function calNext() { calMonth++; if(calMonth>11){calMonth=0;calYear++;} renderCal(); }

const now2 = new Date();
calYear=now2.getFullYear(); calMonth=now2.getMonth();
renderCal();
const todayDs=calYear+'-'+String(calMonth+1).padStart(2,'0')+'-'+String(now2.getDate()).padStart(2,'0');
selectDate(todayDs, now2.getDate());
</script>

@endsection
