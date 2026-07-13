<script>
    var options =
    {
        chart: {
            type: '{!! $chart->type() !!}',
            height: {!! $chart->height() !!},
            width: '{!! $chart->width() !!}',
            toolbar: {!! $chart->toolbar() !!},
            zoom: {!! $chart->zoom() !!},
            fontFamily: '{!! $chart->fontFamily() !!}',
            foreColor: '{!! $chart->foreColor() !!}',
            sparkline: {!! $chart->sparkline() !!},
            @if($chart->stacked())
            stacked: {!! $chart->stacked() !!},
            @endif
        },
        plotOptions: {
            bar: {!! $chart->horizontal() !!},
            {{-- Occupancy Timeline heatmap: map 0/1/2 to fixed status colours --}}
            @if($chart->type() === 'heatmap')
            heatmap: {
                shadeIntensity: 0,
                enableShades: false,
                radius: 2,
                colorScale: {
                    ranges: [
                        { from: 0, to: 0, color: '#10b981', name: 'ว่าง' },
                        { from: 1, to: 1, color: '#f97316', name: 'มีคนใช้' },
                        { from: 2, to: 2, color: '#ef4444', name: 'ปิดซ่อม' },
                    ],
                },
            },
            @endif
        },
        colors: {!! $chart->colors() !!},
        series: {!! $chart->dataset() !!},
        dataLabels: {!! $chart->dataLabels() !!},
        @if($chart->labels())
        
            labels: {!! json_encode($chart->labels(), true) !!},
        @endif
        title: {
            text: "{!! $chart->title() !!}"
        },
        subtitle: {
            text: '{!! $chart->subtitle() !!}',
            align: '{!! $chart->subtitlePosition() !!}'
        },
        xaxis: {!! $chart->xAxis() !!},
        yaxis: {
            labels : {
                show: {!! json_encode($chart->showYAxisLabels(), true) !!},
            }
        },
        @if ($chart->yAxis())
            yaxis: {!! $chart->yAxis() !!},
        @endif
        grid: {!! $chart->grid() !!},
        markers: {!! $chart->markers() !!},
        @if($chart->stroke())
            stroke: {!! $chart->stroke() !!},
        @endif
        legend: {
            show: {!! $chart->showLegend() !!}
        },
        states: {!! json_encode($chart->states()['states']) !!}
    }

    var chartElement = document.querySelector("#{!! $chart->id() !!}");
    var chart = new ApexCharts(chartElement, options);
    chart.render().then(function () {
        if (!options.legend.show) {
            chartElement.querySelectorAll('.apexcharts-legend').forEach(function (legend) {
                legend.remove();
            });
        }
    });

</script>
