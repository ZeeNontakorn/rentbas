@extends('layouts.app')

@section('title', 'Dashboard')

@php
    // ---- KPI card presentation config (matches the Ref design) ----
    $trendChip = function ($t) {
        if ($t === null) return null;
        $up = $t >= 0;
        return [
            'up' => $up,
            'text' => ($up ? '↗ +' : '↘ ') . abs($t) . '%',
        ];
    };

    // Build an SVG polyline path from a list of values, fitted to w×h.
    $sparkPath = function (array $vals, $w, $h) {
        $n = count($vals);
        if ($n < 2) return '';
        $max = max($vals); $min = min($vals);
        $range = max($max - $min, 1);
        $pts = [];
        foreach (array_values($vals) as $i => $v) {
            $x = round($i / ($n - 1) * $w, 1);
            $y = round($h - (($v - $min) / $range) * $h, 1);
            $pts[] = "$x,$y";
        }
        return 'M' . implode(' L', $pts);
    };

    $kpiCards = [
        ['key' => 'today_bookings',   'label' => "Today's Bookings",     'grad' => ['#fb923c', '#f97316'], 'icon' => 'calendar', 'spark' => true],
        ['key' => 'utilization',      'label' => 'Court Utilization Rate','grad' => ['#34d399', '#10b981'], 'icon' => 'check',    'suffix' => '%'],
        ['key' => 'booked_hours',     'label' => 'Booked Hours',          'grad' => ['#818cf8', '#6366f1'], 'icon' => 'clock',    'suffix' => 'h'],
        ['key' => 'active_customers', 'label' => 'Active Customers',      'grad' => ['#38bdf8', '#0ea5e9'], 'icon' => 'users'],
        ['key' => 'pending',          'label' => 'Pending Bookings',      'grad' => ['#fbbf24', '#f59e0b'], 'icon' => 'alert'],
        ['key' => 'cancellation_rate','label' => 'Cancellation Rate',     'grad' => ['#fb7185', '#e11d48'], 'icon' => 'x',        'suffix' => '%', 'invert' => true],
    ];

    $icons = [
        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'check'    => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'clock'    => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'users'    => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4 0m8 0a4 4 0 10-3-7.75',
        'alert'    => 'M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z',
        'x'        => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    ];

    $occColors = ['available' => '#10b981', 'occupied' => '#f97316', 'maintenance' => '#ef4444'];
@endphp

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-sm text-gray-500">ภาพรวมสุขภาพธุรกิจสนามบาส · {{ now()->translatedFormat('l d F Y') }}</p>
            </div>
            <span class="text-xs text-gray-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                อัปเดตล่าสุด {{ now()->format('H:i') }}
            </span>
        </div>

        <!-- ============ KPI CARDS ============ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @foreach($kpiCards as $card)
                @php
                    $k = $kpis[$card['key']];
                    $chip = $trendChip($k['trend'] ?? null);
                    // For cancellation, a rising number is bad — flip the chip colour.
                    $good = $chip ? ($chip['up'] xor ($card['invert'] ?? false)) : true;
                @endphp
                <div class="relative overflow-hidden rounded-2xl p-5 text-white shadow-lg"
                     style="background-image: linear-gradient(135deg, {{ $card['grad'][0] }}, {{ $card['grad'][1] }});">
                    <div class="flex items-start justify-between">
                        <div class="w-10 h-10 rounded-xl bg-white/25 flex items-center justify-center backdrop-blur-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons[$card['icon']] }}"/>
                            </svg>
                        </div>
                        @if($chip)
                            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $good ? 'bg-white/25' : 'bg-black/20' }}">
                                {{ $chip['text'] }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-4 text-sm font-medium text-white/85">{{ $card['label'] }}</div>
                    <div class="text-3xl font-extrabold tracking-tight">
                        {{ number_format($k['value'], ($card['key'] === 'cancellation_rate') ? 1 : 0) }}{{ $card['suffix'] ?? '' }}
                    </div>

                    @if(($card['spark'] ?? false) && !empty($k['spark']))
                        <svg viewBox="0 0 120 32" preserveAspectRatio="none" class="w-full h-8 mt-2 opacity-90">
                            <path d="{{ $sparkPath($k['spark'], 120, 30) }}" fill="none" stroke="white" stroke-width="2"
                                  stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                        </svg>
                    @else
                        <div class="h-8 mt-2"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- ============ BOOKING TREND + CANCELLATION ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

            <!-- Booking Trend (area) -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800">Booking Trend</h3>
                        <p class="text-xs text-gray-400">Daily bookings — last 30 days</p>
                    </div>
                    <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full">
                        Total: {{ number_format($trendTotal) }}
                    </span>
                </div>
                {!! $trendChart->container() !!}
            </div>

            <!-- Cancellation Analysis (donut) -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h3 class="font-bold text-gray-800">Cancellation Analysis</h3>
                        <p class="text-xs text-gray-400">Reasons this month</p>
                    </div>
                    @if($cancelIsMock)
                        <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">ตัวอย่าง</span>
                    @endif
                </div>
                {!! $cancelChart->container() !!}
            </div>
        </div>

        <!-- ============ MONTHLY VOLUME + PEAK HOURS ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

            <!-- Monthly Booking Volume (bars) -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800">Monthly Booking Volume</h3>
                        <p class="text-xs text-gray-400">Last 12 months</p>
                    </div>
                    <span class="text-xs font-semibold {{ $yoy >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50' }} px-2.5 py-1 rounded-full">
                        {{ $yoy >= 0 ? '+' : '' }}{{ $yoy }}% YoY
                    </span>
                </div>
                {!! $monthlyChart->container() !!}
            </div>

            <!-- Peak Booking Hours -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="mb-4">
                    <h3 class="font-bold text-gray-800">Peak Booking Hours</h3>
                    <p class="text-xs text-gray-400">Utilization by time slot, today</p>
                </div>
                {!! $peakChart->container() !!}
            </div>
        </div>

        <!-- ============ COURT UTILIZATION (rings) ============ -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <div class="mb-5">
                <h3 class="font-bold text-gray-800">Court Utilization</h3>
                <p class="text-xs text-gray-400">Usage per court, this week</p>
            </div>
            @if(count($courtCharts))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($courtCharts as $cc)
                    <div class="flex flex-col items-center">
                        {!! $cc['chart']->container() !!}
                        <span class="-mt-3 text-xs text-gray-400">{{ $cc['hours'] }}h / สัปดาห์</span>
                    </div>
                @endforeach
            </div>
            @else
                <p class="text-sm text-gray-400 text-center py-6">ยังไม่มีข้อมูลสนาม</p>
            @endif
        </div>

        <!-- ============ OCCUPANCY TIMELINE ============ -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                <div>
                    <h3 class="font-bold text-gray-800">Occupancy Timeline</h3>
                    <p class="text-xs text-gray-400">Live status across courts, today {{ sprintf('%02d:00', $occupancyHours[0] ?? 8) }}–22:00</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:{{ $occColors['available'] }}"></span>Available</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:{{ $occColors['occupied'] }}"></span>Occupied</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:{{ $occColors['maintenance'] }}"></span>Maintenance</span>
                </div>
            </div>
            @if(count($occupancy))
            <div class="overflow-x-auto">
                <div class="min-w-[640px]">
                    <!-- hour axis -->
                    <div class="flex items-center mb-1 pl-16">
                        @foreach($occupancyHours as $h)
                            <div class="flex-1 text-center text-[9px] text-gray-400">{{ sprintf('%02d', $h) }}</div>
                        @endforeach
                    </div>
                    @foreach($occupancy as $row)
                        <div class="flex items-center gap-1 mb-1">
                            <span class="w-16 text-xs font-medium text-gray-600 shrink-0 truncate">{{ $row['name'] }}</span>
                            @foreach($row['cells'] as $cell)
                                <div class="flex-1 h-6 rounded-sm transition hover:opacity-75"
                                     style="background: {{ $occColors[$cell] }};"
                                     title="{{ ucfirst($cell) }}"></div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
            @else
                <p class="text-sm text-gray-400 text-center py-6">ยังไม่มีข้อมูลสนาม</p>
            @endif
        </div>

        <!-- ============ TODAY'S SCHEDULE + UPCOMING ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

            <!-- Today's Schedule -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="mb-4">
                    <h3 class="font-bold text-gray-800">Today's Schedule</h3>
                    <p class="text-xs text-gray-400">All confirmed sessions for today</p>
                </div>
                @php
                    $stateBadge = [
                        'completed' => ['bg-gray-100 text-gray-500', 'เสร็จแล้ว', '#9ca3af'],
                        'ongoing'   => ['bg-emerald-100 text-emerald-600', 'กำลังใช้งาน', '#10b981'],
                        'upcoming'  => ['bg-blue-100 text-blue-600', 'กำลังจะถึง', '#3b82f6'],
                    ];
                @endphp
                <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                    @forelse($todaysSchedule as $s)
                        @php $b = $stateBadge[$s['state']]; @endphp
                        <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:bg-slate-50 transition">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $b[2] }};"></span>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $s['name'] }}</div>
                                    <div class="text-xs text-gray-400">{{ $s['time'] }} · {{ $s['court'] }} · {{ rtrim(rtrim(number_format($s['hours'], 1), '0'), '.') }}h</div>
                                </div>
                            </div>
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $b[0] }}">{{ $b[1] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-8">วันนี้ยังไม่มีการจองที่ยืนยันแล้ว</p>
                    @endforelse
                </div>
            </div>

            <!-- Upcoming Bookings -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="mb-4">
                    <h3 class="font-bold text-gray-800">Upcoming Bookings</h3>
                    <p class="text-xs text-gray-400">Starting soon</p>
                </div>
                <div class="space-y-2">
                    @forelse($upcoming as $u)
                        <div class="flex items-center justify-between p-3 rounded-xl border-l-4 border-orange-400 bg-orange-50/50">
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $u['name'] }}</div>
                                <div class="text-xs text-gray-400">{{ $u['court'] }} · {{ $u['time'] }}</div>
                            </div>
                            <span class="text-xs font-semibold text-orange-600 whitespace-nowrap">in {{ $u['in'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-8">ไม่มีคิวที่กำลังจะถึง</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ============ TOP CUSTOMERS + RECENT ACTIVITIES ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

            <!-- Top Customers -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="mb-4">
                    <h3 class="font-bold text-gray-800">Top Customers</h3>
                    <p class="text-xs text-gray-400">By total hours booked, this month</p>
                </div>
                @php $avatarGrad = [['#fb923c','#f97316'],['#818cf8','#6366f1'],['#34d399','#10b981'],['#f472b6','#ec4899'],['#38bdf8','#0ea5e9']]; @endphp
                <div class="space-y-2">
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
                <div class="mb-4">
                    <h3 class="font-bold text-gray-800">Recent Activities</h3>
                    <p class="text-xs text-gray-400">Live activity feed</p>
                </div>
                @php
                    $actDot = ['new' => '#3b82f6', 'cancel' => '#ef4444', 'confirm' => '#10b981', 'user' => '#8b5cf6'];
                @endphp
                <div class="space-y-3">
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

        <!-- ============ MEMBERSHIP + VISIT STATS (team lead request) ============ -->
        @php
            $statCards = [
                ['title' => 'สถิติผู้สมัครสมาชิก (Users)', 'week' => $memberStats['week'], 'month' => $memberStats['month']],
                ['title' => 'สถิติผู้เข้าชมเว็บไซต์ (Visits)', 'week' => $visitStats['week'], 'month' => $visitStats['month']],
            ];
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            @foreach($statCards as $sc)
                @php $scMax = max($sc['week'], $sc['month'], 1); @endphp
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800">{{ $sc['title'] }}</h3>
                    <p class="text-xs text-gray-400 mb-6">เปรียบเทียบสัปดาห์นี้ กับ ยอดรวมเดือนนี้</p>

                    <div class="flex items-end justify-around gap-6 h-48">
                        <div class="flex justify-center items-end h-full w-24">
                            <div class="w-full rounded-lg transition-all duration-700"
                                 style="height: {{ max($sc['week'] / $scMax * 100, 3) }}%; background: #60a5fa;"></div>
                        </div>
                        <div class="flex justify-center items-end h-full w-24">
                            <div class="w-full rounded-lg transition-all duration-700"
                                 style="height: {{ max($sc['month'] / $scMax * 100, 3) }}%; background: #4ade80;"></div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 mt-1 pt-3 flex justify-around text-center">
                        <div class="w-24">
                            <div class="text-sm text-gray-500">สัปดาห์นี้</div>
                            <div class="text-xl font-bold text-gray-800">{{ number_format($sc['week']) }}</div>
                        </div>
                        <div class="w-24">
                            <div class="text-sm text-gray-500">เดือนนี้</div>
                            <div class="text-xl font-bold text-gray-800">{{ number_format($sc['month']) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>

@push('scripts')
    <script src="{{ \ArielMejiaDev\LarapexCharts\LarapexChart::cdn() }}"></script>
    {!! $trendChart->script() !!}
    {!! $cancelChart->script() !!}
    {!! $monthlyChart->script() !!}
    {!! $peakChart->script() !!}
    @foreach($courtCharts as $cc)
        {!! $cc['chart']->script() !!}
    @endforeach
@endpush
@endsection
