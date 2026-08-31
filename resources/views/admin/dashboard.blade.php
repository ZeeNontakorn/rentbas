@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">Dashboard</h1>
                <p class="text-sm text-gray-500">ภาพรวมสุขภาพธุรกิจสนามบาส · {{ now()->translatedFormat('l d F Y') }}</p>
            </div>
        </div>

        <!-- ============ TOP CUSTOMERS + RECENT ACTIVITIES ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

            <!-- Top Customers -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2 mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800">ลูกค้าที่ใช้บริการมากที่สุด</h3>
                        <p class="text-xs text-gray-400">จากจำนวนชั่วโมงที่จอง</p>
                    </div>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="topCustomersUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                @php $avatarGrad = [['#fb923c','#f97316'],['#818cf8','#6366f1'],['#34d399','#10b981'],['#f472b6','#ec4899'],['#38bdf8','#0ea5e9']]; @endphp
                <div id="topCustomersList" class="space-y-2 max-h-[210px] overflow-y-auto pr-1">
                    @forelse($topCustomers as $i => $c)
                        <div class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0"
                                     style="background-image: linear-gradient(135deg, {{ $avatarGrad[$i % 5][0] }}, {{ $avatarGrad[$i % 5][1] }});">
                                    {{ $c['initials'] }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $c['name'] }}</div>
                                    <div class="text-xs text-gray-400">{{ $c['count'] }} bookings</div>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-gray-700">{{ $c['hours'] }}h</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-8">ยังไม่มีข้อมูลลูกค้าเดือนนี้</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2 mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800">กิจกรรมล่าสุด</h3>
                        <p class="text-xs text-gray-400">ติดตามกิจกรรมที่เกิดขึ้นล่าสุด</p>
                    </div>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="recentActivitiesUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                @php
                    $actDot = ['new' => '#3b82f6', 'cancel' => '#ef4444', 'confirm' => '#10b981', 'user' => '#8b5cf6'];
                @endphp
                <div id="recentActivitiesList" class="space-y-3 max-h-[210px] overflow-y-auto pr-1">
                    @forelse($recentActivities as $a)
                        <div class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full mt-1.5 shrink-0" style="background: {{ $actDot[$a['type']] ?? '#94a3b8' }};"></span>
                            <div>
                                <div class="text-sm text-gray-700">{{ $a['text'] }}</div>
                                <div class="text-xs text-gray-400">{{ $a['ago'] }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">ยังไม่มีกิจกรรม</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ============ BOOKING TREND (full width) ============ -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-start justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="font-bold text-gray-800">แนวโน้มการจอง</h3>
                    <p class="text-xs text-gray-400" id="trendSubtitle"></p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">
                        Approved: <span id="trendApprovedChip">{{ number_format($trendApprovedTotal) }}</span>
                    </span>
                    <span class="text-xs font-semibold text-rose-600 bg-rose-100 px-2.5 py-1 rounded-full">
                        Rejected: <span id="trendRejectedChip">{{ number_format($trendRejectedTotal) }}</span>
                    </span>
                    <span class="text-xs font-semibold text-slate-600 bg-slate-50 px-2.5 py-1 rounded-full">
                        Cancelled: <span id="trendCancelledChip">{{ number_format($trendCancelledTotal) }}</span>
                    </span>
                    <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full">
                        Pending: <span id="trendTotalChip">{{ number_format($trendTotal) }}</span>
                    </span>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="trendUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
            </div>
            <div id="bookingTrendChart"></div>
        </div>

        <!-- ============ CANCELLATION ANALYSIS + PEAK BOOKING HOURS ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

            <!-- Cancellation Analysis -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div>
                        <h3 class="font-bold text-gray-800">วิเคราะจำนวนจากทั้งหมด</h3>
                        <p class="text-xs text-gray-400">รอดำเนินการ, ยกเลิก, ถูกปฏิเสธ, อนุมัติแล้ว · <span id="cancelDateLabel">{{ $periodLabel }}</span></p>
                    </div>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="cancelUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                <p id="cancelChartEmpty" class="text-sm text-gray-400 text-center py-10 {{ $cancelTotal > 0 ? 'hidden' : '' }}">ไม่มีข้อมูลการจองในช่วงที่เลือก</p>
                <div id="cancelChart" class="transition-opacity {{ $cancelTotal > 0 ? '' : 'hidden' }}"></div>
            </div>

            <!-- Peak Booking Hours -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2 mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800">ชั่วโมงจองสูงสุด</h3>
                        <p class="text-xs text-gray-400">ตามช่วงเวลาชั่วโมงที่ถูกจองมากที่สุด · <span id="peakDateLabel">{{ $periodLabel }}</span></p>
                    </div>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="peakUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                <div id="peakChart" class="transition-opacity"></div>
            </div>
        </div>

        <!-- ============ COURT UTILIZATION + OCCUPANCY TIMELINE ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

            <!-- Court Utilization -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div>
                        <h3 class="font-bold text-gray-800">การใช้สนาม</h3>
                        <p class="text-xs text-gray-400">% ของชั่วโมงที่ถูกจองต่อชั่วโมงที่สนามว่าง · <span id="utilDateLabel">{{ $periodLabel }}</span></p>
                    </div>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="utilUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                @if(count($courtUtilization))
                    <div class="max-h-[360px] overflow-y-auto overflow-x-hidden pr-1">
                        <div id="courtUtilChart" class="transition-opacity"></div>
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-10" id="courtUtilEmpty">ยังไม่มีข้อมูลสนาม</p>
                    <div id="courtUtilChart" class="transition-opacity hidden"></div>
                @endif
            </div>

            <!-- Occupancy Timeline -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800">ช่วงการจอง</h3>
                        <p class="text-xs text-gray-400">
                            ระยะเวลาที่ถูกจองแบบทามไลน์ · <span id="occDateLabel">{{ $periodLabel }}</span>
                            ({{ sprintf('%02d:00', $occupancyHours[0] ?? 8) }}–22:00)
                        </p>
                    </div>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="occUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                @if(count($occupancy['rows']))
                    <div id="occupancyChart" class="overflow-x-auto transition-opacity"></div>
                    <div id="occLegendDay" class="flex items-center gap-4 mt-3 text-xs text-gray-500 {{ $occupancy['mode'] === 'day' ? '' : 'hidden' }}">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#22c55e"></span>ว่าง</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#ef4444"></span>ไม่ว่าง</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#94a3b8"></span>ปิดปรับปรุง</span>
                    </div>
                    <div id="occLegendPeriod" class="flex items-center gap-4 mt-3 text-xs text-gray-500 {{ $occupancy['mode'] === 'day' ? 'hidden' : '' }}">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#22c55e"></span>0–20%</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#facc15"></span>21–50%</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#f97316"></span>51–80%</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#ef4444"></span>81–100%</span>
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-6">ยังไม่มีข้อมูลสนาม</p>
                @endif
            </div>
        </div>

        <!-- ============ MEMBERSHIP + VISIT STATS (team lead request) ============ -->
        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <h2 class="text-sm font-semibold text-gray-500">สถิติสมาชิกและผู้เข้าชม</h2>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-bold text-gray-800">ผู้สมัครสมาชิก</h3>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="memberUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                <p class="text-xs text-gray-400 mb-2">แนวโน้มผู้สมัครสมาชิกใหม่ — <span id="memberPeriodLabel">{{ $periodLabel }}</span></p>
                <div id="memberChartLoading" class="hidden text-xs text-gray-400 py-2">กำลังโหลด...</div>
                {!! $memberChart->container() !!}
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-bold text-gray-800">ผู้เข้าชมเว็บไซต์</h3>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="visitUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                <p class="text-xs text-gray-400 mb-2">แนวโน้มผู้เข้าชมเว็บไซต์ — <span id="visitPeriodLabel">{{ $periodLabel }}</span></p>
                <div id="visitChartLoading" class="hidden text-xs text-gray-400 py-2">กำลังโหลด...</div>
                {!! $visitChart->container() !!}
            </div>
        </div>

    </div>

    <!-- Time selection bar (floating, bottom-left) -->
    <div class="fixed bottom-6 left-6 z-40 w-[320px] bg-white rounded-2xl p-4 shadow-lg border border-gray-100 flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-gray-500 mr-1">ระยะเวลา</span>

        <div id="viewTypeToggle" class="inline-flex bg-slate-100 rounded-lg p-1 text-sm">
            <button type="button" data-type="day" class="view-type-btn px-3 py-1.5 rounded-md font-semibold transition">Day</button>
            <button type="button" data-type="month" class="view-type-btn px-3 py-1.5 rounded-md font-semibold transition">Month</button>
            <button type="button" data-type="year" class="view-type-btn px-3 py-1.5 rounded-md font-semibold transition">Year</button>
        </div>

        @php
            $viewDateMonth = substr($viewDate, 5, 2);
            $viewDateYear = substr($viewDate, 0, 4);
        @endphp

        <!-- Static Month + Year picker, always visible across all view
             types. Month is only meaningful in Day view; Year is
             meaningful in Day and Month views. Each select is enabled or
             disabled for the current view type by JS below, rather than
             being swapped in/out. -->
        <div id="dateMonthYearPicker" class="w-full flex items-center gap-2">
            <select id="dayMonthSelect" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-600 focus:outline-none focus:ring-1 focus:ring-orange-400">
                @foreach($monthOptions as $m)
                    <option value="{{ $m['value'] }}" @selected($m['value'] === $viewDateMonth)>{{ $m['label'] }}</option>
                @endforeach
            </select>
            <select id="dayYearSelect" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-600 focus:outline-none focus:ring-1 focus:ring-orange-400">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" @selected((string) $y === $viewDateYear)>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <span class="text-xs text-gray-400">กำลังดู: <span id="globalPeriodLabel" class="font-semibold text-gray-600">{{ $periodLabel }}</span></span>

        <button type="button" id="periodResetBtn"
                class="ml-auto text-[11px] font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full hover:bg-orange-100 transition {{ $isCurrentPeriod ? 'hidden' : '' }}">
            <span id="periodResetLabel">← Today</span>
        </button>
    </div>
    <!-- Back to top -->
        <button type="button" id="backToTopBtn" aria-label="Back to top"
            class="fixed bottom-6 right-6 z-40 w-11 h-11 rounded-full bg-orange-500 text-white shadow-lg flex items-center justify-center opacity-0 pointer-events-none translate-y-2 transition-all duration-300 hover:bg-orange-600 shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
        </svg>
    </button>
</div>

@push('scripts')
    <script src="{{ \ArielMejiaDev\LarapexCharts\LarapexChart::cdn() }}"></script>

    <style>
        #bookingTrendChart .apexcharts-series-markers .apexcharts-marker {
            cursor: pointer;
        }
        #viewTypeToggle .view-type-btn {
            color: #64748b;
        }
        #viewTypeToggle .view-type-btn.active {
            background: #fff;
            color: #ea580c;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
        }

        /* Time selector: neutral disabled state, used both while a
           period change is in flight and for the Month/Year selects
           that don't apply to the current view type. */
        #viewTypeToggle .view-type-btn:disabled,
        #dateMonthYearPicker select:disabled,
        #periodResetBtn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>

    {{-- Global Day/Month/Year selector + Booking Trend + Cancellation
         Analysis + Peak Booking Hours + Court Utilization + Occupancy
         Timeline: all hand-rolled with raw ApexCharts (rather than via
         Larapex's ->script(), which can't expose JS events or be updated
         in place). Changing the selector, its sub-picker, the reset
         button, or clicking a point on the trend chart fetches fresh data
         from the same AJAX endpoint and updates all five charts with
         updateSeries()/updateOptions() — no page reload.

         This block also polls the SAME AJAX endpoint every 10s (same
         cadence as Top Customers / Recent Activities below) so every
         chart on the dashboard stays live without user interaction. The
         background poll runs "silent": no opacity fade, no URL rewrite —
         it just refreshes the numbers behind whatever period is currently
         selected. --}}
    <script>
        (function () {
            var AJAX_URL = @json(route('admin.dashboard.view-date'));

            // ---- Server-seeded state ----
            var state = {
                view_type: @json($viewType),
                view_date: @json($viewDate),
                chart_month: @json($chartMonth),
                chart_year: @json($chartYear),
            };
            var TODAY_STR = @json($todayStr);
            var CURRENT_MONTH_STR = @json($currentMonthStr);
            var CURRENT_YEAR = new Date().getFullYear();

            var isFetching = false;

            // Booking Trend series order: Approved (green) and Rejected
            // (grey) are drawn FIRST, Cancelled (red) and Booked (orange)
            // are drawn LAST — in ApexCharts, later series paint on top, so
            // this puts Orange and Red visually above Green and Grey.
            var TREND_COLORS = ['#10b981', '#ef4444', '#94a3b8', '#f97316'];
            var TREND_NAMES = ['อนุมัติแล้ว', 'ถูกปฏิเสธ', 'ถูกยกเลิก', 'รอดำเนินการ'];
            var TREND_WIDTHS = [2, 2, 2, 3];
            var TREND_DASH = [0, 4, 4, 0];
            var TREND_MARKER_SIZE = [3, 3, 3, 4];
            var BOOKED_SERIES_INDEX = 3; // last = topmost

            function trendSeriesFromPayload(t) {
                return [
                    { name: TREND_NAMES[0], type: 'line', data: t.approved },
                    { name: TREND_NAMES[1], type: 'line', data: t.rejected },
                    { name: TREND_NAMES[2], type: 'line', data: t.cancelled },
                    { name: TREND_NAMES[3], type: 'area', data: t.total },
                ];
            }

            function selectedMarker(idx) {
                if (idx < 0) return [];
                return [{
                    seriesIndex: BOOKED_SERIES_INDEX,
                    dataPointIndex: idx,
                    fillColor: '#c2410c',
                    strokeColor: '#fff',
                    strokeWidth: 2,
                    size: 8,
                }];
            }

            function currentSelectedKey() {
                if (state.view_type === 'month') return state.chart_month;
                if (state.view_type === 'year') return String(state.chart_year);
                return state.view_date;
            }

            function labelColors(keys, currentKey) {
                return keys.map(function (k) { return k === currentKey ? '#f97316' : '#94a3b8'; });
            }

            // ApexCharts heatmaps/bars render series bottom-to-top: the
            // first entry in the array ends up on the BOTTOM row. Reverse
            // here so court order visually reads top-to-bottom the same
            // way it's listed everywhere else on the dashboard.
            function buildDayOccSeries(rows) {
                var stateVal = { available: 0, occupied: 1, maintenance: 2 };
                return rows.slice().reverse().map(function (row) {
                    return {
                        name: row.name,
                        data: row.cells.map(function (cell, i) {
                            return { x: occHoursLabels[i], y: stateVal[cell] ?? 0 };
                        })
                    };
                });
            }

            function buildPeriodOccSeries(rows) {
                return rows.slice().reverse().map(function (row) {
                    return {
                        name: row.name,
                        data: row.cells.map(function (pct, i) {
                            return { x: occHoursLabels[i], y: pct };
                        })
                    };
                });
            }

            var occHoursLabels = @json(array_map(fn ($h) => sprintf('%02d:00', $h), $occupancyHours));

            // ---- initial payloads (first paint) ----
            var initialTrend = @json($trend);
            var initialCancel = @json($cancel);
            var initialPeak = @json($peak);
            var initialOccupancy = @json($occupancy); // { mode: 'day'|'period', rows: [...] }
            var initialCourtUtil = @json($courtUtilization); // [{name, pct}]

            // ---- Booking Trend ----
            var trendChart = new ApexCharts(document.querySelector('#bookingTrendChart'), {
                chart: {
                    type: 'area',
                    height: 240,
                    fontFamily: 'Kanit, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    events: {
                        markerClick: function (event, chartContext, opts) { selectTrendPoint(opts.dataPointIndex); },
                        dataPointSelection: function (event, chartContext, config) { selectTrendPoint(config.dataPointIndex); }
                    }
                },
                colors: TREND_COLORS,
                stroke: { curve: 'smooth', width: TREND_WIDTHS, dashArray: TREND_DASH },
                fill: {
                    type: ['solid', 'solid', 'solid', 'gradient'],
                    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] },
                    opacity: [1, 1, 1, 1]
                },
                markers: {
                    size: TREND_MARKER_SIZE,
                    colors: TREND_COLORS,
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: { size: 7 },
                    discrete: selectedMarker(initialTrend.keys.indexOf(currentSelectedKey()))
                },
                dataLabels: { enabled: false },
                grid: { show: true, borderColor: '#f1f5f9' },
                tooltip: {
                    x: { show: true },
                    custom: function (opts) {
                        var idx = opts.dataPointIndex;
                        var rowsHtml = opts.w.config.series.map(function (s, i) {
                            return '<div style="display:flex;align-items:center;gap:6px;margin:2px 0;">' +
                                '<span style="width:8px;height:8px;border-radius:50%;background:' + opts.w.config.colors[i] + ';display:inline-block;"></span>' +
                                '<span style="color:#374151;font-size:12px;">' + s.name + ': <b>' + opts.series[i][idx] + '</b></span>' +
                            '</div>';
                        }).join('');
                        return '<div style="padding:8px 12px;">' +
                            '<div style="font-weight:600;color:#111827;font-size:12px;margin-bottom:4px;">' + opts.w.globals.labels[idx] + '</div>' +
                            rowsHtml +
                            '<div style="margin-top:6px;font-size:11px;color:#f97316;font-weight:600;">คลิกเพื่อเลือก</div>' +
                        '</div>';
                    }
                },
                legend: { show: true, position: 'top', horizontalAlign: 'right' },
                series: trendSeriesFromPayload(initialTrend),
                xaxis: {
                    categories: initialTrend.labels,
                    labels: { style: { fontSize: '11px', colors: labelColors(initialTrend.keys, currentSelectedKey()) } }
                }
            });
            trendChart.render();

            var trendKeys = initialTrend.keys.slice();

            function selectTrendPoint(idx) {
                var key = trendKeys[idx];
                if (!key || isFetching) return;

                if (state.view_type === 'month') fetchChartData({ chart_month: key });
                else if (state.view_type === 'year') fetchChartData({ chart_year: key });
                else fetchChartData({ view_date: key });
            }

            // ---- Cancellation Analysis (donut) ----
            var cancelChart = new ApexCharts(document.querySelector('#cancelChart'), {
                chart: { type: 'donut', height: 320, fontFamily: 'Kanit, sans-serif' },
                labels: ['รอดำเนินการ', 'ถูกยกเลิก', 'อนุมัติแล้ว', 'ถูกปฏิเสธ'],
                colors: ['#f97316', '#94a3b8', '#10b981', '#ef4444'],
                series: [initialCancel.Booked, initialCancel.Cancelled, initialCancel.Approved, initialCancel.Rejected],
                legend: { position: 'bottom' }
            });
            cancelChart.render();

            // ---- Peak Booking Hours (line) ----
            var peakChart = new ApexCharts(document.querySelector('#peakChart'), {
            chart: { type: 'line', height: 360, fontFamily: 'Kanit, sans-serif', toolbar: { show: false } },
            colors: ['#f97316', '#94a3b8', '#10b981', '#ef4444'],
            stroke: { curve: 'smooth', width: [3, 2, 2, 2], dashArray: [0, 4, 0, 4] },
            markers: {
                size: [4, 3, 3, 3],
                colors: ['#f97316', '#94a3b8', '#10b981', '#ef4444'],
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: { size: 6 },
            },
            dataLabels: { enabled: false },
            grid: { show: true, borderColor: '#f1f5f9' },
            legend: { show: true, position: 'top', horizontalAlign: 'right' },
            series: [
                { name: 'รอดำเนินการ', data: initialPeak.total },
                { name: 'ถูกยกเลิก', data: initialPeak.cancelled },
                { name: 'อนุมัติแล้ว', data: initialPeak.approved },
                { name: 'ถูกปฏิเสธ', data: initialPeak.rejected }
            ],
            xaxis: { categories: initialPeak.labels }
        });
        peakChart.render();

            // ---- Court Utilization (horizontal bar, server-computed %) ----
            var courtUtilEl = document.querySelector('#courtUtilChart');
            var courtUtilChart = null;
            function renderCourtUtil(rows) {
                var names = rows.map(function (r) { return r.name; });
                var pcts = rows.map(function (r) { return r.pct; });
                var height = Math.max(280, names.length * 60);
                var formatUtilization = function (val) {
                    return Number(val).toFixed(2) + '%';
                };

                if (!courtUtilChart) {
                    courtUtilChart = new ApexCharts(courtUtilEl, {
                        chart: { type: 'bar', height: height, fontFamily: 'Kanit, sans-serif', toolbar: { show: false } },
                        plotOptions: { bar: { horizontal: true, distributed: true, barHeight: '55%' } },
                        colors: ['#f97316'],
                        dataLabels: { enabled: true, formatter: formatUtilization, style: { colors: ['#374151'] }, offsetX: 4 },
                        grid: { show: true, borderColor: '#f1f5f9' },
                        legend: { show: false },
                        tooltip: { y: { formatter: function (val) { return formatUtilization(val) + ' occupied'; } } },
                        xaxis: { categories: names, max: 100, labels: { formatter: function (v) { return v + '%'; } } },
                        series: [{ name: '% Utilization', data: pcts }]
                    });
                    courtUtilChart.render();
                } else {
                    courtUtilChart.updateOptions({
                        chart: { height: height },
                        xaxis: { categories: names, max: 100, labels: { formatter: function (v) { return v + '%'; } } },
                    }, false, true);
                    courtUtilChart.updateSeries([{ name: '% Utilization', data: pcts }]);
                }
            }
            if (courtUtilEl && initialCourtUtil.length) {
                renderCourtUtil(initialCourtUtil);
            }

            // ---- Occupancy Timeline (heatmap; discrete states for a single
            // day, continuous % for a month/year period) ----
            var occChartEl = document.querySelector('#occupancyChart');
            var occChart = null;
            var occLegendDay = document.getElementById('occLegendDay');
            var occLegendPeriod = document.getElementById('occLegendPeriod');

            function occOptionsFor(mode, rows) {
                if (mode === 'day') {
                    return {
                        chart: { type: 'heatmap', height: Math.max(160, rows.length * 60), fontFamily: 'Kanit, sans-serif', toolbar: { show: false } },
                        plotOptions: {
                            heatmap: {
                                radius: 4,
                                colorScale: {
                                    ranges: [
                                        { from: 0, to: 0, color: '#22c55e', name: 'ว่าง' },
                                        { from: 1, to: 1, color: '#ef4444', name: 'ไม่ว่าง' },
                                        { from: 2, to: 2, color: '#94a3b8', name: 'ปิดปรับปรุง' },
                                    ]
                                }
                            }
                        },
                        dataLabels: { enabled: false },
                        legend: { show: false },
                        grid: { padding: { right: 10 } },
                        series: buildDayOccSeries(rows)
                    };
                }

                return {
                    chart: { type: 'heatmap', height: Math.max(160, rows.length * 60), fontFamily: 'Kanit, sans-serif', toolbar: { show: false } },
                    plotOptions: {
                        heatmap: {
                            radius: 4,
                            colorScale: {
                                ranges: [
                                    { from: 0, to: 20, color: '#22c55e', name: '0-20%' },
                                    { from: 21, to: 50, color: '#facc15', name: '21-50%' },
                                    { from: 51, to: 80, color: '#f97316', name: '51-80%' },
                                    { from: 81, to: 100, color: '#ef4444', name: '81-100%' },
                                ]
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    grid: { padding: { right: 10 } },
                    tooltip: { y: { formatter: function (val) { return val + '% occupied'; } } },
                    series: buildPeriodOccSeries(rows)
                };
            }

            function renderOccupancy(mode, rows) {
                occLegendDay.classList.toggle('hidden', mode !== 'day');
                occLegendPeriod.classList.toggle('hidden', mode === 'day');

                if (occChart) {
                    occChart.destroy();
                    occChart = null;
                }
                if (!occChartEl || !rows.length) return;
                occChart = new ApexCharts(occChartEl, occOptionsFor(mode, rows));
                occChart.render();
            }
            if (occChartEl && initialOccupancy.rows.length) {
                renderOccupancy(initialOccupancy.mode, initialOccupancy.rows);
            }

            // ---- Day / Month / Year toggle ----
            var toggleBtns = document.querySelectorAll('.view-type-btn');
            var periodResetBtn = document.getElementById('periodResetBtn');
            var periodResetLabel = document.getElementById('periodResetLabel');
            var globalPeriodLabel = document.getElementById('globalPeriodLabel');
            var trendSubtitle = document.getElementById('trendSubtitle');

            // Single static Month + Year picker, shared across all view
            // types. Which select is usable depends on state.view_type
            // (see updatePickerEnablement below) rather than swapping
            // pickers in/out.
            var dayMonthSelect = document.getElementById('dayMonthSelect');
            var dayYearSelect = document.getElementById('dayYearSelect');

            function daysInMonth(year, month) {
                return new Date(year, month, 0).getDate();
            }

            // Month is only meaningful in Day view (Month/Year views pick
            // their month/year via the trend chart or the reset button).
            // Year is meaningful in Day and Month view, but not Year view
            // (there's nothing above "year" to pick it against). This is
            // a structural rule based on view type only — it does not
            // change while a fetch is in flight.
            function updatePickerEnablement() {
                var monthDisabled = state.view_type !== 'day';
                var yearDisabled = state.view_type === 'year';
                if (dayMonthSelect) dayMonthSelect.disabled = monthDisabled;
                if (dayYearSelect) dayYearSelect.disabled = yearDisabled;
            }

            function syncPickerValues() {
                if (state.view_type === 'day') {
                    if (dayMonthSelect) dayMonthSelect.value = state.view_date.slice(5, 7);
                    if (dayYearSelect) dayYearSelect.value = state.view_date.slice(0, 4);
                } else if (state.view_type === 'month') {
                    if (dayMonthSelect) dayMonthSelect.value = state.chart_month.slice(5, 7);
                    if (dayYearSelect) dayYearSelect.value = String(state.chart_year);
                } else {
                    if (dayYearSelect) dayYearSelect.value = String(state.chart_year);
                }
            }

            function syncControlsUI() {
                toggleBtns.forEach(function (btn) {
                    btn.classList.toggle('active', btn.dataset.type === state.view_type);
                });
                if (state.view_type === 'month') {

                    trendSubtitle.textContent = 'แนวโน้มรายเดือน — คลิกเพื่อเลือกเดือน';
                } else if (state.view_type === 'year') {

                    trendSubtitle.textContent = 'แนวโน้มรายปี — คลิกเพื่อเลือกปี';
                } else {

                    trendSubtitle.textContent = 'แน้วโน้มรายวัน — คลิกเพื่อเลือกวัน';
                }
                syncPickerValues();
                updatePickerEnablement();
            }
            syncControlsUI();

            toggleBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (btn.dataset.type === state.view_type || isFetching) return;
                    fetchChartData({ view_type: btn.dataset.type });
                });
            });
            periodResetBtn.addEventListener('click', function () {
                if (state.view_type === 'month') fetchChartData({ chart_month: CURRENT_MONTH_STR });
                else if (state.view_type === 'year') fetchChartData({ chart_year: CURRENT_YEAR });
                else fetchChartData({ view_date: TODAY_STR });
            });

            // Month select only fires in Day view (it's disabled
            // otherwise). Keeps the current day-of-month and swaps in the
            // new month, clamping the day down if the target month has
            // fewer days (e.g. Jan 31 -> Feb 28). A resulting future date
            // is safely caught server-side by resolveViewDate(), which
            // falls back to today.
            function onMonthSelectChange() {
                if (isFetching || state.view_type !== 'day') return;
                var day = Number(state.view_date.slice(8, 10));
                var month = Number(dayMonthSelect.value);
                var year = Number(dayYearSelect.value);
                var maxDay = daysInMonth(year, month);
                if (day > maxDay) day = maxDay;
                var newDate = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                fetchChartData({ view_date: newDate });
            }

            // Year select fires in Day view (swap the date's year, same
            // clamping as above) or Month view (keep the selected month
            // number and swap in the new year). Disabled in Year view.
            function onYearSelectChange() {
                if (isFetching) return;
                var year = Number(dayYearSelect.value);
                if (state.view_type === 'day') {
                    var day = Number(state.view_date.slice(8, 10));
                    var month = Number(dayMonthSelect.value);
                    var maxDay = daysInMonth(year, month);
                    if (day > maxDay) day = maxDay;
                    var newDate = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                    fetchChartData({ view_date: newDate });
                } else if (state.view_type === 'month') {
                    var monthPart = state.chart_month.slice(5, 7);
                    fetchChartData({ chart_year: year, chart_month: year + '-' + monthPart });
                }
            }

            if (dayMonthSelect) dayMonthSelect.addEventListener('change', onMonthSelectChange);
            if (dayYearSelect) dayYearSelect.addEventListener('change', onYearSelectChange);

            var fadeTargets = document.querySelectorAll('#cancelChart, #peakChart, #courtUtilChart, #occupancyChart, #bookingTrendChart');

            // Fades the charts while a fetch is in flight. Only used for
            // user-driven fetches — the silent background poll never
            // touches this. Controls are no longer locked while loading;
            // the Month/Year selects still go through
            // updatePickerEnablement() for their view-type-based disabled
            // state (Month only usable in Day view, Year unusable in Year
            // view), independent of loading.
            function setLoading(loading) {
                fadeTargets.forEach(function (el) { el.classList.toggle('opacity-40', loading); });
            }

            // Stamps every chart's "Updated HH:MM:SS" label. Called after
            // every successful fetchChartData response, same as Top
            // Customers / Recent Activities do on their own poll.
            var UPDATED_AT_IDS = [
                'trendUpdatedAt', 'cancelUpdatedAt', 'peakUpdatedAt',
                'utilUpdatedAt', 'occUpdatedAt', 'memberUpdatedAt', 'visitUpdatedAt',
            ];
            function stampUpdatedAt() {
                var now = new Date();
                var hh = String(now.getHours()).padStart(2, '0');
                var mm = String(now.getMinutes()).padStart(2, '0');
                var ss = String(now.getSeconds()).padStart(2, '0');
                var stamp = hh + ':' + mm + ':' + ss;
                UPDATED_AT_IDS.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.textContent = stamp;
                });
            }

            function updateStatsCharts(data) {
                var memberChart = window.dashboardMemberChart;
                var visitChart = window.dashboardVisitChart;

                if (memberChart) {
                    memberChart.updateOptions({ xaxis: { categories: data.memberLabels } }, false, true);
                    memberChart.updateSeries([{ name: 'ผู้สมัครสมาชิก', data: data.memberCounts }]);
                }
                if (visitChart) {
                    visitChart.updateOptions({ xaxis: { categories: data.visitLabels } }, false, true);
                    visitChart.updateSeries([{ name: 'ผู้เข้าชม', data: data.visitCounts }]);
                }

                document.getElementById('memberPeriodLabel').textContent = data.periodLabel;
                document.getElementById('visitPeriodLabel').textContent = data.periodLabel;
            }

            // `silent` = true for background auto-refresh: skips the
            // opacity-fade loading state and skips rewriting the URL,
            // since the selected period itself hasn't changed — only the
            // numbers inside it may have.
            function fetchChartData(overrides, silent) {
                if (isFetching) return;
                isFetching = true;
                if (!silent) setLoading(true);

                var next = Object.assign({}, state, overrides);
                var params = new URLSearchParams({
                    view_type: next.view_type,
                    view_date: next.view_date,
                    chart_month: next.chart_month,
                    chart_year: next.chart_year,
                });

                fetch(AJAX_URL + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        state.view_type = data.viewType;
                        state.view_date = data.viewDate;
                        state.chart_month = data.chartMonth;
                        state.chart_year = data.chartYear;

                        syncControlsUI();
                        globalPeriodLabel.textContent = data.periodLabel;
                        document.getElementById('cancelDateLabel').textContent = data.periodLabel;
                        document.getElementById('peakDateLabel').textContent = data.periodLabel;
                        document.getElementById('utilDateLabel').textContent = data.periodLabel;
                        document.getElementById('occDateLabel').textContent = data.periodLabel;
                        periodResetBtn.classList.toggle('hidden', data.isCurrentPeriod);

                        document.getElementById('trendTotalChip').textContent = data.trendTotal.toLocaleString();
                        document.getElementById('trendCancelledChip').textContent = data.trendCancelledTotal.toLocaleString();
                        document.getElementById('trendApprovedChip').textContent = data.trendApprovedTotal.toLocaleString();
                        document.getElementById('trendRejectedChip').textContent = data.trendRejectedTotal.toLocaleString();

                        trendKeys = data.trend.keys.slice();
                        trendChart.updateOptions({
                            xaxis: { categories: data.trend.labels, labels: { style: { fontSize: '11px', colors: labelColors(data.trend.keys, currentSelectedKey()) } } },
                            markers: { discrete: selectedMarker(data.trend.keys.indexOf(currentSelectedKey())) }
                        }, false, true);
                        trendChart.updateSeries(trendSeriesFromPayload(data.trend));

                        var cancelEl = document.getElementById('cancelChart');
                        var cancelEmptyEl = document.getElementById('cancelChartEmpty');
                        if (data.cancel.total > 0) {
                            cancelEmptyEl.classList.add('hidden');
                            cancelEl.classList.remove('hidden');
                            cancelChart.updateSeries([data.cancel.booked, data.cancel.cancelled, data.cancel.approved, data.cancel.rejected]);
                        } else {
                            cancelEl.classList.add('hidden');
                            cancelEmptyEl.classList.remove('hidden');
                        }

                        peakChart.updateSeries([
                            { name: 'รอดำเนินการ', data: data.peakHours.total },
                            { name: 'ถูกยกเลิก', data: data.peakHours.cancelled },
                            { name: 'อนุมัติแล้ว', data: data.peakHours.approved },
                            { name: 'ถูกปฏิเสธ', data: data.peakHours.rejected }
                        ]);

                        if (data.courtUtilization && data.courtUtilization.length) {
                            var utilEmpty = document.getElementById('courtUtilEmpty');
                            if (utilEmpty) utilEmpty.classList.add('hidden');
                            courtUtilEl.classList.remove('hidden');
                            renderCourtUtil(data.courtUtilization);
                        }

                        if (data.occupancy) {
                            renderOccupancy(data.occupancy.mode, data.occupancy.rows);
                        }

                        if (window.dashboardRenderTopCustomers) {
                            window.dashboardRenderTopCustomers(data.topCustomers || []);
                        }
                        updateStatsCharts(data);
                        stampUpdatedAt();

                        // Only rewrite the URL for user-driven changes.
                        // Background polling refreshes numbers in place
                        // without touching browser history.
                        if (!silent) {
                            var url = new URL(window.location.href);
                            url.searchParams.set('view_type', state.view_type);
                            url.searchParams.set('view_date', state.view_date);
                            url.searchParams.set('chart_month', state.chart_month);
                            url.searchParams.set('chart_year', state.chart_year);
                            window.history.replaceState({}, '', url);
                        }
                    })
                    .catch(function (err) {
                        console.error('Failed to load chart data', err);
                    })
                    .finally(function () {
                        isFetching = false;
                        if (!silent) setLoading(false);
                    });
            }

            // ---- Background auto-refresh ----
            // Keeps Booking Trend, Cancellation Analysis, Peak Booking
            // Hours, Court Utilization, Occupancy Timeline, and the
            // Member/Visit charts live on the same 10s cadence as Top
            // Customers / Recent Activities, without any user interaction.
            // A tick is skipped (not queued) if a user-initiated fetch is
            // already in flight, so it never fights with an explicit click
            // — it just picks back up on the next interval.
            setInterval(function () {
                if (isFetching) return;
                fetchChartData({}, true);
            }, 10000);
        })();
    </script>

    {{-- Top Customers + Recent Activities: poll every 10s and re-render the
         two scrollable lists in place, stamping "Updated HH:MM:SS". --}}
    <script>
        (function () {
            var LIVE_URL = @json(route('admin.dashboard.live-data'));
            var avatarGrad = [['#fb923c','#f97316'],['#818cf8','#6366f1'],['#34d399','#10b981'],['#f472b6','#ec4899'],['#38bdf8','#0ea5e9']];
            var actDot = { new: '#3b82f6', cancel: '#ef4444', confirm: '#10b981', user: '#8b5cf6' };

            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function renderTopCustomers(list) {
                var el = document.getElementById('topCustomersList');
                if (!el) return;
                if (!list.length) {
                    el.innerHTML = '<p class="text-sm text-gray-400 text-center py-8">ยังไม่มีข้อมูลลูกค้าเดือนนี้</p>';
                    return;
                }
                el.innerHTML = list.map(function (c, i) {
                    var g = avatarGrad[i % avatarGrad.length];
                    return (
                        '<div class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50 transition">' +
                            '<div class="flex items-center gap-3">' +
                                '<div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0" style="background-image: linear-gradient(135deg, ' + g[0] + ', ' + g[1] + ');">' + escapeHtml(c.initials) + '</div>' +
                                '<div>' +
                                    '<div class="text-sm font-semibold text-gray-800">' + escapeHtml(c.name) + '</div>' +
                                    '<div class="text-xs text-gray-400">' + c.count + ' bookings</div>' +
                                '</div>' +
                            '</div>' +
                            '<span class="text-sm font-bold text-gray-700">' + c.hours + 'h</span>' +
                        '</div>'
                    );
                }).join('');
            }
            window.dashboardRenderTopCustomers = renderTopCustomers;

            function renderRecentActivities(list) {
                var el = document.getElementById('recentActivitiesList');
                if (!el) return;
                if (!list.length) {
                    el.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">ยังไม่มีกิจกรรม</p>';
                    return;
                }
                el.innerHTML = list.map(function (a) {
                    var dot = actDot[a.type] || '#94a3b8';
                    return (
                        '<div class="flex items-start gap-3">' +
                            '<span class="w-2 h-2 rounded-full mt-1.5 shrink-0" style="background: ' + dot + ';"></span>' +
                            '<div>' +
                                '<div class="text-sm text-gray-700">' + escapeHtml(a.text) + '</div>' +
                                '<div class="text-xs text-gray-400">' + escapeHtml(a.ago) + '</div>' +
                            '</div>' +
                        '</div>'
                    );
                }).join('');
            }

            function fetchLiveData() {
                var url = new URL(LIVE_URL, window.location.origin);
                ['view_type', 'view_date', 'chart_month', 'chart_year'].forEach(function (key) {
                    var value = new URLSearchParams(window.location.search).get(key);
                    if (value) url.searchParams.set(key, value);
                });

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        renderTopCustomers(data.topCustomers);
                        renderRecentActivities(data.recentActivities);
                        var tEl = document.getElementById('topCustomersUpdatedAt');
                        var rEl = document.getElementById('recentActivitiesUpdatedAt');
                        if (tEl) tEl.textContent = data.updatedAt;
                        if (rEl) rEl.textContent = data.updatedAt;
                    })
                    .catch(function (err) {
                        console.error('Failed to refresh live data', err);
                    });
            }

            setInterval(fetchLiveData, 10000);
        })();
    </script>

    {{-- Back to top button: fades in once the page has scrolled down. --}}
    <script>
        (function () {
            var btn = document.getElementById('backToTopBtn');
            if (!btn) return;

            function toggle() {
                var scrolled = window.scrollY > 300;
                btn.classList.toggle('opacity-0', !scrolled);
                btn.classList.toggle('pointer-events-none', !scrolled);
                btn.classList.toggle('translate-y-2', !scrolled);
            }

            window.addEventListener('scroll', toggle, { passive: true });
            toggle();

            btn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();
    </script>

    {!! $memberChart->script() !!}
    <script>window.dashboardMemberChart = chart;</script>
    {!! $visitChart->script() !!}
    <script>window.dashboardVisitChart = chart;</script>
@endpush
@endsection
