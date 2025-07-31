@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
        <i data-lucide="layout-dashboard" class="w-6 h-6 text-blue-600"></i>
        لوحة التحكم
    </h1>
</div>

<div id="stats" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-4 rounded-lg shadow flex items-center gap-4">
        <i data-lucide="users" class="w-8 h-8 text-blue-500"></i>
        <div>
            <h3 class="text-sm text-gray-500">عدد الزبائن</h3>
            <p id="customers-count" class="text-2xl font-bold text-blue-600">...</p>
        </div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex items-center gap-4">
        <i data-lucide="package" class="w-8 h-8 text-blue-500"></i>
        <div>
            <h3 class="text-sm text-gray-500">عدد المنتجات</h3>
            <p id="products-count" class="text-2xl font-bold text-blue-600">...</p>
        </div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex items-center gap-4">
        <i data-lucide="file-text" class="w-8 h-8 text-blue-500"></i>
        <div>
            <h3 class="text-sm text-gray-500">عدد الطلبات</h3>
            <p id="orders-count" class="text-2xl font-bold text-blue-600">...</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4 text-gray-700 flex items-center gap-2">
            <i data-lucide="bar-chart" class="w-5 h-5 text-blue-600"></i>
            عدد الطلبات خلال الشهور
        </h2>
        <canvas id="ordersChart" height="100"></canvas>
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4 text-gray-700 flex items-center gap-2">
            <i data-lucide="dollar-sign" class="w-5 h-5 text-green-600"></i>
            المبيعات الشهرية (دولار)
        </h2>
        <canvas id="salesChart" height="100"></canvas>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/lucide@latest"></script> 
<script>

    async function fetchDashboard() {
        try{
            const dashRes = await fetch("/api/dashboard", {
                headers: { Authorization: `Bearer ${token}` }
            });

            const data = await dashRes.json();
            console.log(data)
            document.getElementById('customers-count').textContent = data.customersCount;
            document.getElementById('products-count').textContent = data.productsCount;
            document.getElementById('orders-count').textContent = data.ordersCount;

            renderCharts(data.monthlyOrders, data.monthlySales);
        } catch (err) {
            redirectToLogin();
        }

    }
    function renderCharts(monthlyOrders, monthlySales) {
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'line',
            data: {
                labels: monthlyOrders.map(i => i.month),
                datasets: [{
                    label: 'عدد الطلبات',
                    data: monthlyOrders.map(i => i.count),
                    fill: true,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderColor: '#3B82F6',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, stepSize: 1 } }
            }
        });

        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: monthlySales.map(i => i.month),
                datasets: [{
                    label: 'المبيعات $',
                    data: monthlySales.map(i => i.total),
                    backgroundColor: '#10B981'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function logout() {
        fetch("/api/logout", {
            method: "POST",
            headers: { Authorization: `Bearer ${token}` }
        }).finally(() => {
            localStorage.removeItem("token");
            window.location.href = "/login";
        });
    }

    function redirectToLogin() {
        localStorage.removeItem("token");
        window.location.href = "/login";
    }

    fetchDashboard();

    lucide.createIcons();
</script>
@endsection
