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
            <span class="text-xs text-gray-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                อัปเดตล่าสุด {{ now()->format('H:i') }}
            </span>
        </div>

        <!-- ============ TOP CUSTOMERS + RECENT ACTIVITIES ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

            <!-- Top Customers -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2 mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800">Top Customers</h3>
                        <p class="text-xs text-gray-400">By total hours booked, this month</p>
                    </div>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="topCustomersUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                @php $avatarGrad = [['#fb923c','#f97316'],['#818cf8','#6366f1'],['#34d399','#10b981'],['#f472b6','#ec4899'],['#38bdf8','#0ea5e9']]; @endphp
                <div id="topCustomersList" class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
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
                        <h3 class="font-bold text-gray-800">Recent Activities</h3>
                        <p class="text-xs text-gray-400">Live activity feed</p>
                    </div>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Updated <span id="recentActivitiesUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </span>
                </div>
                @php
                    $actDot = ['new' => '#3b82f6', 'cancel' => '#ef4444', 'confirm' => '#10b981', 'user' => '#8b5cf6'];
                @endphp
                <div id="recentActivitiesList" class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
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
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-bold text-gray-800">Booking Trend</h3>
                    <p class="text-xs text-gray-400">Daily bookings — last 30 days · click a point to inspect that day</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full">
                        Booked: {{ number_format($trendTotal) }}
                    </span>
                    <span class="text-xs font-semibold text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full">
                        Cancelled: {{ number_format($trendCancelledTotal) }}
                    </span>
                </div>
            </div>
            <div id="bookingTrendChart"></div>
        </div>

        <!-- ============ CANCELLATION ANALYSIS + PEAK BOOKING HOURS ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

            <!-- Cancellation Analysis -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div>
                        <h3 class="font-bold text-gray-800">Cancellation Analysis</h3>
                        <p class="text-xs text-gray-400">Booked vs. cancelled · <span id="cancelDateLabel">{{ $viewDateLabel }}</span></p>
                    </div>
                    <button type="button" id="cancelTodayBtn"
                            class="shrink-0 text-[11px] font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded-full hover:bg-orange-100 transition {{ $isToday ? 'hidden' : '' }}">
                        ← Today
                    </button>
                </div>
                <p id="cancelChartEmpty" class="text-sm text-gray-400 text-center py-10 {{ $cancelTotal > 0 ? 'hidden' : '' }}">ไม่มีข้อมูลการจองในวันที่เลือก</p>
                <div id="cancelChart" class="transition-opacity {{ $cancelTotal > 0 ? '' : 'hidden' }}"></div>
            </div>

            <!-- Peak Booking Hours -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2 mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800">Peak Booking Hours</h3>
                        <p class="text-xs text-gray-400">Bookings by time slot · <span id="peakDateLabel">{{ $viewDateLabel }}</span></p>
                    </div>
                    <button type="button" id="peakTodayBtn"
                            class="shrink-0 text-[11px] font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded-full hover:bg-orange-100 transition {{ $isToday ? 'hidden' : '' }}">
                        ← Today
                    </button>
                </div>
                <div id="peakChart" class="transition-opacity"></div>
            </div>

            <!-- Court Utilization -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="mb-4">
                    <h3 class="font-bold text-gray-800">Court Utilization</h3>
                    <p class="text-xs text-gray-400">Approved vs. Pending vs. Cancelled, this week</p>
                </div>
                <div class="max-h-[360px] overflow-y-auto overflow-x-hidden pr-1">
                    <div id="courtUtilChart"></div>
                </div>
            </div>
        </div>

        <!-- ============ MONTHLY BOOKING VOLUME (full width) ============ -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-bold text-gray-800">Monthly Booking Volume</h3>
                    <p class="text-xs text-gray-400">Jan – Dec {{ $viewYear }}</p>
                </div>
                <div class="flex flex-col items-end gap-1.5">
                    <span class="text-xs font-semibold {{ $yoy >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50' }} px-2.5 py-1 rounded-full">
                        {{ $yoy >= 0 ? '+' : '' }}{{ $yoy }}% YoY
                    </span>
                    <select id="yearSelect"
                            class="text-xs border border-gray-200 rounded-lg px-2 py-1 text-gray-500 bg-white focus:outline-none focus:ring-1 focus:ring-orange-300">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $y == $viewYear ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            {!! $monthlyChart->container() !!}
        </div>

        <!-- ============ OCCUPANCY TIMELINE ============ -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                <div>
                    <h3 class="font-bold text-gray-800">Occupancy Timeline</h3>
                    <p class="text-xs text-gray-400">
                        Court status by hour · <span id="occDateLabel">{{ $viewDateLabel }}</span>
                        ({{ sprintf('%02d:00', $occupancyHours[0] ?? 8) }}–22:00)
                    </p>
                </div>
                <button type="button" id="occTodayBtn"
                        class="shrink-0 text-[11px] font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded-full hover:bg-orange-100 transition {{ $isToday ? 'hidden' : '' }}">
                    ← Today
                </button>
            </div>
            @if(count($occupancy))
                <div id="occupancyChart" class="overflow-x-auto transition-opacity"></div>
                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#22c55e"></span>ว่าง</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#ef4444"></span>ไม่ว่าง</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#94a3b8"></span>ปิดปรับปรุง</span>
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-6">ยังไม่มีข้อมูลสนาม</p>
            @endif
        </div>

        <!-- ============ MEMBERSHIP + VISIT STATS (team lead request) ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800">สถิติผู้สมัครสมาชิก (Users)</h3>
                <p class="text-xs text-gray-400 mb-2">แนวโน้มผู้สมัครสมาชิกใหม่ — 30 วันล่าสุด</p>
                {!! $memberChart->container() !!}
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800">สถิติผู้เข้าชมเว็บไซต์ (Visits)</h3>
                <p class="text-xs text-gray-400 mb-2">แนวโน้มผู้เข้าชมเว็บไซต์ — 30 วันล่าสุด</p>
                {!! $visitChart->container() !!}
            </div>
        </div>

    </div>

    <!-- Back to top -->
    <button type="button" id="backToTopBtn" aria-label="Back to top"
            class="fixed bottom-6 right-6 z-50 w-11 h-11 rounded-full bg-orange-500 text-white shadow-lg flex items-center justify-center opacity-0 pointer-events-none translate-y-2 transition-all duration-300 hover:bg-orange-600">
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
    </style>

    {{-- Booking Trend, Cancellation Analysis, Peak Booking Hours, and
         Occupancy Timeline are all hand-rolled directly in ApexCharts
         (rather than via Larapex's ->script(), which can't expose JS
         events or be updated in place). Clicking a point on the trend
         chart — or any "← Today" button — fetches fresh data for that day
         from the view-date AJAX endpoint and updates all four with
         updateSeries()/updateOptions(), with no page reload. --}}
    <script>
        (function () {
            var AJAX_URL = @json(route('admin.dashboard.view-date'));
            var TODAY_STR = @json($todayStr);

            var trendLabels = @json(array_column($trend, 'label'));
            var trendDates  = @json(array_column($trend, 'date'));
            var trendCounts = @json(array_column($trend, 'count'));
            var trendCancelledCounts = @json(array_column($trend, 'cancelled'));

            var initialCancel = @json($cancel); // { Booked: n, Cancelled: n }
            var peakLabels = @json(array_column($peakHours, 'label'));
            var initialPeakVals = @json(array_column($peakHours, 'count'));

            var occHoursLabels = @json(array_map(fn ($h) => sprintf('%02d:00', $h), $occupancyHours));
            var occStateVal = { available: 0, occupied: 1, maintenance: 2 };
            var initialOccupancy = @json($occupancy); // [{name, cells:[...]}]

            var courtNames = @json(array_column($courtStatusStats, 'name'));
            var courtApproved = @json(array_column($courtStatusStats, 'approved'));
            var courtPending = @json(array_column($courtStatusStats, 'pending'));
            var courtCancelled = @json(array_column($courtStatusStats, 'cancelled'));

            var currentViewDate = @json($viewDate);
            var isFetching = false;

            // Today's orange (matches the trend line colour) vs. the
            // muted grey used for every other x-axis label.
            var TODAY_LABEL_COLOR = '#f97316';
            var DEFAULT_LABEL_COLOR = '#94a3b8';
            var todayLabelColors = trendDates.map(function (d) {
                return d === TODAY_STR ? TODAY_LABEL_COLOR : DEFAULT_LABEL_COLOR;
            });

            // Discrete marker override for the currently-selected day: bigger
            // circle, darker orange than the rest of the (lighter orange) line.
            function selectedMarker(idx) {
                if (idx < 0) return [];
                return [{
                    seriesIndex: 0,
                    dataPointIndex: idx,
                    fillColor: '#c2410c',
                    strokeColor: '#fff',
                    strokeWidth: 2,
                    size: 7,
                }];
            }

            // ApexCharts heatmaps render series bottom-to-top: the first
            // entry in the array ends up on the BOTTOM row, not the top.
            // Reverse here so court order visually reads top-to-bottom the
            // same way it's listed everywhere else on the dashboard.
            function buildOccSeries(occArr) {
                return occArr.slice().reverse().map(function (row) {
                    return {
                        name: row.name,
                        data: row.cells.map(function (cell, i) {
                            return { x: occHoursLabels[i], y: occStateVal[cell] ?? 0 };
                        })
                    };
                });
            }

            // ---- Booking Trend (area) ----
            var trendChart = new ApexCharts(document.querySelector('#bookingTrendChart'), {
                chart: {
                    type: 'area',
                    height: 240,
                    fontFamily: 'Kanit, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    events: {
                        markerClick: function (event, chartContext, opts) {
                            selectDate(trendDates[opts.dataPointIndex]);
                        },
                        dataPointSelection: function (event, chartContext, config) {
                            selectDate(trendDates[config.dataPointIndex]);
                        }
                    }
                },
                colors: ['#f97316', '#ef4444'],
                stroke: { curve: 'smooth', width: [3, 2], dashArray: [0, 4] },
                fill: {
                    type: ['gradient', 'solid'],
                    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] },
                    opacity: [1, 1]
                },
                markers: {
                    size: [4, 3],
                    colors: ['#f97316', '#ef4444'],
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: { size: 7 },
                    discrete: selectedMarker(trendDates.indexOf(currentViewDate))
                },
                dataLabels: { enabled: false },
                grid: { show: true, borderColor: '#f1f5f9' },
                tooltip: { x: { show: true } },
                legend: { show: true, position: 'top', horizontalAlign: 'right' },
                series: [
                    { name: 'การจอง', type: 'area', data: trendCounts },
                    { name: 'ยกเลิก', type: 'line', data: trendCancelledCounts }
                ],
                xaxis: {
                    categories: trendLabels,
                    labels: { style: { fontSize: '11px', colors: todayLabelColors } }
                }
            });
            trendChart.render();

            function highlightSelectedPoint() {
                var idx = trendDates.indexOf(currentViewDate);
                trendChart.updateOptions({ markers: { discrete: selectedMarker(idx) } }, false, true);
            }

            // ---- Cancellation Analysis (donut) ----
            var cancelChart = new ApexCharts(document.querySelector('#cancelChart'), {
                chart: { type: 'donut', height: 320, fontFamily: 'Kanit, sans-serif' },
                labels: Object.keys(initialCancel),
                colors: ['#10b981', '#f43f5e'],
                series: Object.values(initialCancel),
                legend: { position: 'bottom' }
            });
            cancelChart.render();

            // ---- Peak Booking Hours (line — total bookings per hour slot) ----
            var peakChart = new ApexCharts(document.querySelector('#peakChart'), {
                chart: { type: 'line', height: 360, fontFamily: 'Kanit, sans-serif', toolbar: { show: false } },
                colors: ['#f97316'],
                stroke: { curve: 'smooth', width: 3 },
                markers: {
                    size: 4,
                    colors: ['#f97316'],
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: { size: 6 },
                },
                dataLabels: { enabled: false },
                grid: { show: true, borderColor: '#f1f5f9' },
                series: [{ name: 'จำนวนการจอง', data: initialPeakVals }],
                xaxis: { categories: peakLabels }
            });
            peakChart.render();

            // ---- Court Utilization (horizontal grouped bar) ----
            // Height grows with the number of courts so grouped bars stay
            // legible; the surrounding div (max-h-[360px] overflow-y-auto
            // in the Blade card) scrolls instead of squishing the chart.
            var courtUtilEl = document.querySelector('#courtUtilChart');
            if (courtUtilEl && courtNames.length) {
                var courtUtilChart = new ApexCharts(courtUtilEl, {
                    chart: { type: 'bar', height: Math.max(280, courtNames.length * 110), fontFamily: 'Kanit, sans-serif', toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true } },
                    colors: ['#10b981', '#94a3b8', '#ef4444'],
                    dataLabels: { enabled: false },
                    grid: { show: true, borderColor: '#f1f5f9' },
                    legend: { position: 'bottom' },
                    series: [
                        { name: 'Approved', data: courtApproved },
                        { name: 'Pending', data: courtPending },
                        { name: 'Cancelled', data: courtCancelled }
                    ],
                    xaxis: { categories: courtNames }
                });
                courtUtilChart.render();
            }

            // ---- Occupancy Timeline (heatmap) ----
            var occChartEl = document.querySelector('#occupancyChart');
            var occChart = null;
            if (occChartEl && initialOccupancy.length) {
                occChart = new ApexCharts(occChartEl, {
                    chart: { type: 'heatmap', height: Math.max(160, initialOccupancy.length * 60), fontFamily: 'Kanit, sans-serif', toolbar: { show: false } },
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
                    series: buildOccSeries(initialOccupancy)
                });
                occChart.render();
            }

            // ---- AJAX date switching (no page reload) ----
            var todayButtons = [
                document.getElementById('cancelTodayBtn'),
                document.getElementById('peakTodayBtn'),
                document.getElementById('occTodayBtn')
            ];
            var fadeTargets = document.querySelectorAll('#cancelChart, #peakChart, #occupancyChart');

            function setLoading(loading) {
                fadeTargets.forEach(function (el) { el.classList.toggle('opacity-40', loading); });
            }

            function selectDate(dateStr) {
                if (!dateStr || dateStr === currentViewDate || isFetching) return;
                fetchViewDate(dateStr);
            }

            todayButtons.forEach(function (btn) {
                if (!btn) return;
                btn.addEventListener('click', function () { fetchViewDate(TODAY_STR); });
            });

            function fetchViewDate(dateStr) {
                if (!dateStr || dateStr === currentViewDate || isFetching) return;
                isFetching = true;
                setLoading(true);

                fetch(AJAX_URL + '?view_date=' + encodeURIComponent(dateStr), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        currentViewDate = data.viewDate;

                        document.getElementById('cancelDateLabel').textContent = data.viewDateLabel;
                        document.getElementById('peakDateLabel').textContent = data.viewDateLabel;
                        var occLabel = document.getElementById('occDateLabel');
                        if (occLabel) occLabel.textContent = data.viewDateLabel;
                        todayButtons.forEach(function (btn) {
                            if (btn) btn.classList.toggle('hidden', data.isToday);
                        });

                        var cancelEl = document.getElementById('cancelChart');
                        var cancelEmptyEl = document.getElementById('cancelChartEmpty');
                        if (data.cancel.total > 0) {
                            cancelEmptyEl.classList.add('hidden');
                            cancelEl.classList.remove('hidden');
                            cancelChart.updateSeries([data.cancel.booked, data.cancel.cancelled]);
                        } else {
                            cancelEl.classList.add('hidden');
                            cancelEmptyEl.classList.remove('hidden');
                        }

                        var peakVals = data.peakHours.map(function (p) { return p.count; });
                        peakChart.updateSeries([{ name: 'จำนวนการจอง', data: peakVals }]);

                        if (occChart && data.occupancy) {
                            occChart.updateSeries(buildOccSeries(data.occupancy));
                        }

                        highlightSelectedPoint();

                        // Keep the URL shareable/refreshable without reloading.
                        var url = new URL(window.location.href);
                        url.searchParams.set('view_date', currentViewDate);
                        window.history.replaceState({}, '', url);
                    })
                    .catch(function (err) {
                        console.error('Failed to load view-date data', err);
                    })
                    .finally(function () {
                        isFetching = false;
                        setLoading(false);
                    });
            }

            // ---- Monthly Booking Volume: year switcher (full reload) ----
            var yearSelect = document.getElementById('yearSelect');
            if (yearSelect) {
                yearSelect.addEventListener('change', function () {
                    var url = new URL(window.location.href);
                    url.searchParams.set('view_year', this.value);
                    window.location.href = url.toString();
                });
            }
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
                fetch(LIVE_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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

    {{-- monthlyChart: built manually instead of ->script() because Larapex's
         blade template only reads ->horizontal() for plotOptions.bar, so
         ->setOptions(['plotOptions' => ['bar' => ['distributed' => true]]])
         in the controller never actually reaches the rendered chart. --}}
    <script>
        (function () {
            var options = {
                chart: {
                    id: '{!! $monthlyChart->id() !!}',
                    type: '{!! $monthlyChart->type() !!}',
                    height: {!! $monthlyChart->height() !!},
                    width: '{!! $monthlyChart->width() !!}',
                    toolbar: {!! $monthlyChart->toolbar() !!},
                    zoom: {!! $monthlyChart->zoom() !!},
                    fontFamily: '{!! $monthlyChart->fontFamily() !!}',
                    foreColor: '{!! $monthlyChart->foreColor() !!}',
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        distributed: true
                    }
                },
                colors: {!! $monthlyChart->colors() !!},
                series: {!! $monthlyChart->dataset() !!},
                dataLabels: {!! $monthlyChart->dataLabels() !!},
                title: {
                    text: "{!! $monthlyChart->title() !!}"
                },
                xaxis: {!! $monthlyChart->xAxis() !!},
                grid: {!! $monthlyChart->grid() !!},
                legend: {
                    show: false
                }
            };
            new ApexCharts(document.querySelector("#{!! $monthlyChart->id() !!}"), options).render();
        })();
    </script>

    {!! $memberChart->script() !!}
    {!! $visitChart->script() !!}
@endpush
@endsection
