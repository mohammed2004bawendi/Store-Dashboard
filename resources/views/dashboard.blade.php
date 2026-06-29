@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <section class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                <i data-lucide="activity" class="h-3.5 w-3.5 text-emerald-600"></i>
                نظرة عامة
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-950">أداء المتجر</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">تابع العملاء والمنتجات والطلبات من مساحة واحدة.</p>
        </div>

        <a href="/ai-assistant" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-300">
            <i data-lucide="bot" class="h-4 w-4"></i>
            اسأل المساعد
        </a>
    </section>

    <section id="stats" class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <article class="stat-card">
            <div class="stat-icon bg-blue-50 text-blue-600">
                <i data-lucide="users" class="h-5 w-5"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500">عدد الزبائن</p>
                <p id="customers-count" class="mt-1 text-3xl font-bold tracking-tight text-slate-950">...</p>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon bg-violet-50 text-violet-600">
                <i data-lucide="package" class="h-5 w-5"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500">عدد المنتجات</p>
                <p id="products-count" class="mt-1 text-3xl font-bold tracking-tight text-slate-950">...</p>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon bg-emerald-50 text-emerald-600">
                <i data-lucide="file-text" class="h-5 w-5"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500">عدد الطلبات</p>
                <p id="orders-count" class="mt-1 text-3xl font-bold tracking-tight text-slate-950">...</p>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <article class="chart-panel">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <h3 class="flex items-center gap-2 text-base font-bold text-slate-950">
                        <i data-lucide="line-chart" class="h-5 w-5 text-blue-600"></i>
                        الطلبات خلال الشهور
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">اتجاه عدد الطلبات شهريا.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Orders</span>
            </div>
            <div class="h-72">
                <canvas id="ordersChart"></canvas>
            </div>
        </article>

        <article class="chart-panel">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <h3 class="flex items-center gap-2 text-base font-bold text-slate-950">
                        <i data-lucide="banknote" class="h-5 w-5 text-emerald-600"></i>
                        المبيعات الشهرية
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">إجمالي المبيعات حسب الشهر.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Sales</span>
            </div>
            <div class="h-72">
                <canvas id="salesChart"></canvas>
            </div>
        </article>
    </section>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let ordersChartInstance = null;
    let salesChartInstance = null;

    async function fetchDashboard() {
        try {
            const dashRes = await fetch("/api/dashboard", {
                headers: { Authorization: `Bearer ${token}` }
            });

            if (!dashRes.ok) throw new Error('Dashboard request failed');

            const data = await dashRes.json();
            document.getElementById('customers-count').textContent = data.customersCount;
            document.getElementById('products-count').textContent = data.productsCount;
            document.getElementById('orders-count').textContent = data.ordersCount;

            renderCharts(data.monthlyOrders || [], data.monthlySales || []);
        } catch (err) {
            redirectToLogin();
        }
    }

    function renderCharts(monthlyOrders, monthlySales) {
        Chart.defaults.font.family = "'Inter', 'Segoe UI', Tahoma, sans-serif";
        Chart.defaults.color = '#64748b';

        const gridColor = 'rgba(148, 163, 184, 0.18)';
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        const salesCtx = document.getElementById('salesChart').getContext('2d');

        if (ordersChartInstance) ordersChartInstance.destroy();
        if (salesChartInstance) salesChartInstance.destroy();

        ordersChartInstance = new Chart(ordersCtx, {
            type: 'line',
            data: {
                labels: monthlyOrders.map(i => i.month),
                datasets: [{
                    label: 'عدد الطلبات',
                    data: monthlyOrders.map(i => i.count),
                    fill: true,
                    backgroundColor: 'rgba(37, 99, 235, 0.10)',
                    borderColor: '#2563eb',
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2563eb',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    tension: 0.42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 12 }
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor }, border: { display: false } }
                }
            }
        });

        salesChartInstance = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: monthlySales.map(i => i.month),
                datasets: [{
                    label: 'المبيعات',
                    data: monthlySales.map(i => i.total),
                    backgroundColor: '#10b981',
                    borderRadius: 12,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 12 }
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: { beginAtZero: true, grid: { color: gridColor }, border: { display: false } }
                }
            }
        });
    }

    fetchDashboard();
    lucide.createIcons();
</script>
@endsection
