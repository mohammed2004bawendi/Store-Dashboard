@extends('layouts.app')

@section('title', 'الطلبات')

@section('content')
<div class="flex justify-between items-center mb-6">



   <div class="flex items-center gap-3 text-gray-700 hover:text-indigo-600 transition">
    <i data-lucide="package" class="w-6 h-6 text-indigo-500"></i>
    <h2 class="text-2xl font-bold text-gray-800">الطلبات</h2>
</div>


    <div class="flex gap-3">
       <a href="{{ route('orders.export') }}"
   class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition flex items-center gap-2 text-sm">
    <i data-lucide="download" class="w-4 h-4"></i> تصدير الطلبات
</a>

<button onclick="toggleCreateForm()"
        class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700 transition flex items-center gap-2 text-sm">
    <i data-lucide="plus-circle" class="w-4 h-4"></i> إضافة طلبية
</button>

    </div>
</div>


<!-- ✅ نموذج إنشاء طلبية -->
<div id="create-order-form" class="bg-white p-4 rounded shadow mb-6 hidden">
    <h3 class="text-lg font-bold text-gray-800 mb-4">طلبية جديدة</h3>
    <form onsubmit="createOrder(event)" class="space-y-3">
        <input type="text" id="c-name" placeholder="الاسم" class="w-full rounded border px-3 py-2" required>
        <input type="text" id="c-phone" placeholder="الهاتف" class="w-full rounded border px-3 py-2" required>
        <input type="text" id="c-address" placeholder="العنوان" class="w-full rounded border px-3 py-2" required>
        <!-- ✅ الحالة -->
<div>
    <label for="status" class="block mb-1 font-semibold text-sm text-gray-700">الحالة</label>
    <select id="status" class="w-full rounded border px-3 py-2">
        <option value="قيد التنفيذ">قيد التنفيذ</option>
        <option value="تم التوصيل">تم التوصيل</option>
        <option value="ملغي">ملغي</option>
    </select>
</div>

        <!-- ✅ إدخال المنتجات بشكل ديناميكي -->
<!-- ✅ إدخال المنتجات -->
<div class="space-y-2 mt-4">
    <label class="block text-sm font-semibold text-gray-700 mb-1">إضافة منتج</label>
    <div class="flex gap-2">
        <input type="number" id="prod-id" placeholder="ID المنتج"
               class="flex-1 rounded border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <input type="number" id="prod-qty" placeholder="الكمية"
               class="flex-1 rounded border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">

        <button type="button" onclick="addProduct()"
                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition flex items-center gap-1 text-sm">
            <i data-lucide="plus-circle" class="w-4 h-4"></i> إضافة
        </button>
    </div>

    <!-- ✅ المنتجات المضافة -->
    <div id="products-list" class="flex flex-wrap gap-2 mt-2"></div>
</div>

<!-- ✅ أزرار الحفظ والإلغاء -->
<div class="flex justify-start gap-3 mt-6">
    <button type="submit"
            class="bg-green-600 hover:bg-green-700 transition text-white text-sm px-6 py-2 rounded flex items-center gap-1">
        <i data-lucide="save" class="w-4 h-4"></i> حفظ الطلبية
    </button>

    <button type="button" onclick="toggleCreateForm()"
            class="bg-gray-300 hover:bg-gray-400 transition text-gray-700 text-sm px-6 py-2 rounded flex items-center gap-1">
        <i data-lucide="x-circle" class="w-4 h-4"></i> إلغاء
    </button>
</div>


    </form>
</div>

<!-- ✅ بحث وفلترة مع أيقونة بحث رسمية -->
<div class="flex flex-col md:flex-row items-center gap-4 mb-6 bg-white p-4 rounded shadow">
    <div class="relative w-full md:w-1/2">
        <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-400">
            <i data-lucide="search" class="w-4 h-4"></i>
        </span>
        <input type="text" id="search" placeholder="رقم الطلب أو اسم العميل..."
               class="w-full rounded border px-10 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-400">
    </div>

    <select id="status-filter" class="w-full md:w-1/4 rounded border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <option value="">كل الحالات</option>
        <option value="قيد التنفيذ">قيد التنفيذ</option>
        <option value="تم التوصيل">تم التوصيل</option>
        <option value="ملغي">ملغي</option>
    </select>
</div>


<div id="orders-summary" class="bg-white p-4 rounded shadow mb-4 text-sm text-gray-700 font-semibold flex justify-between items-center">
    <span id="orders-count" class="flex items-center gap-2">
        <i data-lucide="package" class="w-4 h-4 text-indigo-600"></i>
        <span id="orders-count-text">عدد الطلبات: ...</span>
    </span>
    <span id="orders-total" class="flex items-center gap-2">
        <i data-lucide="coins" class="w-4 h-4 text-yellow-500"></i>
        <span id="orders-total-text">الإجمالي: ...</span>
    </span>
</div>


<!-- 🧾 نتائج الطلبات -->
<div id="order-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <p class="col-span-full text-gray-500">جارٍ تحميل الطلبات...</p>
</div>

<!-- ✅ الترقيم -->
<div id="pagination" class="mt-6 flex justify-center gap-2"></div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentPage = 1;

document.addEventListener("DOMContentLoaded", () => {
    loadOrders(1);
});

document.getElementById("search").addEventListener("input", () => loadOrders(1));
document.getElementById("status-filter").addEventListener("change", () => loadOrders(1));

function getStatusClass(status) {
    switch (status) {
        case 'تم التوصيل':
            return 'bg-green-100 text-green-800';
        case 'قيد التنفيذ':
            return 'bg-yellow-100 text-yellow-800';
        case 'ملغي':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-600';
    }
}

async function loadOrders(page = 1) {
     currentPage = page;

    const container = document.getElementById("order-container");
    const pagination = document.getElementById("pagination");
    const search = document.getElementById("search").value;
    const status = document.getElementById("status-filter").value;

    container.innerHTML = `<p class="col-span-full text-gray-500">جارٍ تحميل الطلبات...</p>`;
    pagination.innerHTML = "";

    const params = new URLSearchParams();
    if (search) params.append("search", search);
    if (status) params.append("status", status);
    params.append("page", page);

    try {
        const response = await fetch(`/api/orders?${params.toString()}`, {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: "application/json"
            }
        });

        const data = await response.json();
        if (!response.ok || !data.data) throw new Error("Unauthorized");

  // ✅ تحديث التجميع
        document.getElementById("orders-count-text").innerText = `عدد الطلبات: ${data.meta.total_orders}`;
        document.getElementById("orders-total-text").innerText = `الإجمالي: ${data.meta.total_amount.toLocaleString()} د.ل`;


        if (data.data.length === 0) {
            container.innerHTML = `<p class="col-span-full text-gray-500">لا توجد طلبات حتى الآن.</p>`;
            return;
        }

        const cards = data.data.map(order => `
            <div onclick="window.location.href='/orders/${order.id}'"
                 class="bg-white p-4 rounded-lg shadow hover:shadow-md transition cursor-pointer flex flex-col justify-between h-full">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">#طلب رقم: ${order.id}</h3>
                    <p class="text-sm text-gray-600 mt-1"><strong>العميل:</strong> ${order.customer.name}</p>
                    <p class="text-sm text-gray-600 mt-1"><strong>العنوان:</strong> ${order.customer.address}</p>
                    <p class="text-sm text-gray-600 mt-1 flex items-center gap-2">
                    <strong>الحالة:</strong>
                    <span class="${getStatusClass(order.status)} text-xs font-medium px-3 py-1 rounded-full">${order.status}</span>
                    </p>
                    <p class="text-sm text-blue-700 font-bold mt-1"><strong>الإجمالي:</strong> ${order.total_price} د.ل</p>
                    <p class="text-xs text-gray-400 mt-2">${new Date(order.created_at).toLocaleString()}</p>

                </div>
            </div>
        `).join("");

        container.innerHTML = cards;

        const meta = data.meta;
        const links = [];
        for (let i = 1; i <= meta.last_page; i++) {
            links.push(`<button onclick="loadOrders(${i})"
                class="px-3 py-1 rounded text-sm ${
                    i === meta.current_page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }">${i}</button>`);
        }
        pagination.innerHTML = links.join("");

    } catch (error) {
        container.innerHTML = `<p class="col-span-full text-red-500">فشل في تحميل الطلبات.</p>`;
    }
}

function toggleCreateForm() {
    document.getElementById("create-order-form").classList.toggle("hidden");
}

async function createOrder(event) {
    event.preventDefault();
    const token = localStorage.getItem("token");

    const name = document.getElementById("c-name").value;
    const phone = document.getElementById("c-phone").value;
    const address = document.getElementById("c-address").value;
    const status = document.getElementById("status").value;


    if (products.length === 0) {
        return Swal.fire("خطأ", "أضف منتجًا واحدًا على الأقل", "error");
    }

    try {
        const res = await fetch('/api/orders', {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${token}`,
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({ name, phone, address,status, products })
        });

        const data = await res.json();

        if (res.ok) {
            Swal.fire("تم", "تم إنشاء الطلب بنجاح", "success");
            products = [];
            renderProductsList();
            document.getElementById("create-order-form").classList.add("hidden");
            loadOrders(1);
        } else {
            Swal.fire("خطأ", data.message || "فشل في إنشاء الطلب", "error");
        }
    } catch {
        Swal.fire("خطأ", "فشل الاتصال بالسيرفر", "error");
    }
}


let products = [];

function addProduct() {
    const id = parseInt(document.getElementById("prod-id").value);
    const qty = parseInt(document.getElementById("prod-qty").value);

    if (!id || !qty || qty <= 0) {
        return Swal.fire("خطأ", "أدخل ID صحيح وكمية أكبر من 0", "warning");
    }

    // تحقق إن كان المنتج مضاف مسبقًا
    const exists = products.find(p => p.id === id);
    if (exists) {
        exists.quantity += qty;
    } else {
        products.push({ id, quantity: qty });
    }

    document.getElementById("prod-id").value = "";
    document.getElementById("prod-qty").value = "";

    renderProductsList();
}

function removeProduct(index) {
    products.splice(index, 1);
    renderProductsList();
}

function renderProductsList() {
    const container = document.getElementById("products-list");
    container.innerHTML = products.map((p, i) => `
        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full flex items-center gap-2">
            ID: ${p.id} × ${p.quantity}
            <button onclick="removeProduct(${i})" class="text-red-500 hover:text-red-700 font-bold">×</button>
        </span>
    `).join("");
}

</script>
@endsection
