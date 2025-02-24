<div wire:ignore
     x-data="chartData(@entangle('labels'), @entangle('data'))"
     x-init="initChart()">

    <x-Dashboard.spinner/>

    <div id="chart-container" class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 mx-auto h-96">
        <canvas id="myChart"></canvas>
    </div>
    <x-Dashboard.button/>

    @push('scripts')
        <script>
            function chartData(labels, data) {
                return {
                    chart: null,
                    labels: labels,
                    data: data,

                    initChart() {
                        this.createChart();
                        this.$watch('labels', () => this.recreateChart());
                        this.$watch('data', () => this.recreateChart());
                    },

                    createChart() {
                        const existingChart = Chart.getChart('myChart');
                        if (existingChart) existingChart.destroy();

                        const ctx = document.getElementById('myChart').getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: this.labels,
                                datasets: [{
                                    label: '# of Votes',
                                    data: this.data,
                                    borderWidth: 1,
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    },

                    recreateChart() {
                        if (this.chart) {
                            this.chart.destroy();
                            this.chart = null;
                        }

                        const container = document.getElementById('chart-container');
                        container.innerHTML = '<canvas id="myChart"></canvas>';

                        requestAnimationFrame(() => this.createChart());
                    }
                };
            }
        </script>
    @endpush
</div>
