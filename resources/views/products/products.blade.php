@extends('layouts.app')

@section('title', 'المنتجات')

@section('content')
<div class="flex justify-between items-center mb-6">


    <div class="flex items-center gap-2 text-gray-700 hover:text-blue-600 transition">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                <h2 class="text-2xl font-bold text-gray-800">المنتجات</h2>
    </div>

    <button onclick="toggleCreateForm()"
        class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700 transition flex items-center gap-2 text-sm">
    <i data-lucide="plus-circle" class="w-4 h-4"></i> إضافة طلبية
</button>

</div>

<style>
@keyframes fadeInUp {
  0% { opacity: 0; transform: translateY(20px); }
  100% { opacity: 1; transform: translateY(0); }
}
.animate-fadeInUp {
  animation: fadeInUp 0.4s ease-out;
}
#popup-overlay .success {
  background-color: #ecfdf5;
  color: #065f46;
}
#popup-overlay .error {
  background-color: #fef2f2;
  color: #991b1b;
}
</style>



<div id="create-form" class="bg-white p-4 rounded shadow mb-6 hidden">
    <h3 class="text-lg font-bold text-gray-700 mb-4 text-center">
        <i class="fas fa-cart-plus ml-1"></i> منتج جديد
    </h3>
    <form onsubmit="createProduct(event)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="text" id="new-name" placeholder="اسم المنتج" class="border rounded px-3 py-2" required>
        <input type="text" id="new-description" placeholder="الوصف" class="border rounded px-3 py-2" required>
        <input type="number" id="new-price" placeholder="السعر $" class="border rounded px-3 py-2" required>
        <input type="number" id="new-quantity" placeholder="الكمية" class="border rounded px-3 py-2" required>

        <select id="new-status" class="border rounded px-3 py-2 md:col-span-2" required>
            <option value="">اختر الحالة</option>
            <option value="متوفر">متوفر</option>
            <option value="غير متوفر">غير متوفر</option>
        </select>

        <div class="flex justify-start gap-3 mt-4">
            <button type="submit" class="bg-green-600 text-white text-sm px-6 py-2 rounded hover:bg-green-700 transition">
                <i class="fas fa-save ml-1"></i> حفظ المنتج
            </button>
            <button type="button" onclick="toggleCreateForm()" class="bg-gray-200 text-gray-700 text-sm px-6 py-2 rounded hover:bg-gray-300 transition">
                إلغاء
            </button>
        </div>
    </form>
</div>

<!-- 🔎 أدوات الفلترة -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 bg-white p-4 rounded shadow">
    <div class="relative w-full md:w">
        <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-400">
            <i data-lucide="search" class="w-4 h-4"></i>
        </span>
        <input type="text" id="search" placeholder="البحث بالاسم .."
               class="w-full rounded border px- py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-400">
    </div>
    <select id="status" class="border rounded px-3 py-2 col-span-1">
        <option value="">كل الحالات</option>
        <option value="متوفر">متوفر</option>
        <option value="غير متوفر">غير متوفر</option>
    </select>
    <input type="number" id="min_price" placeholder="أقل سعر" class="border rounded px-3 py-2 col-span-1">
    <input type="number" id="max_price" placeholder="أعلى سعر" class="border rounded px-3 py-2 col-span-1">
</div>

<!-- 🧾 نتائج المنتجات -->
<div id="product-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <p class="col-span-full text-gray-500">جارٍ تحميل المنتجات...</p>
</div>

<!-- ✅ الترقيم -->
<div id="pagination" class="mt-6 flex justify-center gap-2"></div>
<!-- ✅ إشعار وسط الشاشة -->
<div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
    <div id="popup-message" class="text-center px-8 py-6 rounded-lg shadow-lg text-lg font-semibold max-w-sm w-full animate-fadeInUp">
        <!-- الرسالة ستظهر هنا -->
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>


function showPopup(message, type = 'success') {
    const overlay = document.getElementById("popup-overlay");
    const popup = document.getElementById("popup-message");

    popup.innerText = message;
    popup.className = `text-center px-8 py-6 rounded-lg shadow-lg text-lg font-semibold max-w-sm w-full animate-fadeInUp ${type}`;

    overlay.classList.remove("hidden");

    setTimeout(() => {
        overlay.classList.add("hidden");
    }, 5000); // يغلق بعد 3 ثوانٍ
}







    let currentPage = 1;

    document.addEventListener("DOMContentLoaded", () => {
        ["search", "status", "min_price", "max_price"].forEach(id =>
            document.getElementById(id).addEventListener("input", () => loadProducts(1))
        );
        loadProducts(1);
    });

    function toggleCreateForm() {
        const form = document.getElementById("create-form");
        form.classList.toggle("hidden");
    }

    async function createProduct(event) {
        event.preventDefault();
        const data = {
            name: document.getElementById("new-name").value,
            description: document.getElementById("new-description").value,
            price: parseInt(document.getElementById("new-price").value),
            quantity: parseInt(document.getElementById("new-quantity").value),
            status: document.getElementById("new-status").value,
        };

        try {
            const res = await fetch('/api/products', {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            });

            const result = await res.json();

            if (res.ok) {
                Swal.fire({
    icon: 'success',
    title: 'تم إضافة المنتج!',
    text: 'تم حفظ المنتج بنجاح.',
    confirmButtonText: 'حسناً'
});

                toggleCreateForm();
                loadProducts(currentPage);
            } else {
                Swal.fire({
    icon: 'error',
    title: 'فشل!',
    text: result.message || 'حدث خطأ أثناء الإضافة.',
    confirmButtonText: 'موافق'
});

            }

        } catch (err) {
            Swal.fire({
    icon: 'error',
    title: 'فشل!',
    text: result.message || 'حدث خطأ أثناء الإضافة.',
    confirmButtonText: 'موافق'
});

        }
    }

    async function loadProducts(page = 1) {
        currentPage = page;
        const container = document.getElementById("product-container");
        const pagination = document.getElementById("pagination");

        const params = new URLSearchParams({
            page,
            search: document.getElementById("search").value,
            status: document.getElementById("status").value,
            min_price: document.getElementById("min_price").value,
            max_price: document.getElementById("max_price").value
        });

        container.innerHTML = `<p class="col-span-full text-gray-500">جارٍ تحميل المنتجات...</p>`;
        pagination.innerHTML = "";

        try {
            const response = await fetch(`/api/products?${params.toString()}`, {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json"
                }
            });

            const data = await response.json();
            if (!response.ok || !data.data) throw new Error("Unauthorized");

            if (data.data.length === 0) {
                container.innerHTML = `<p class="col-span-full text-gray-500">لا توجد منتجات حسب الفلاتر.</p>`;
                return;
            }

            const cards = data.data.map(product => `
    <a href="/products/${product.id}" class="block transition hover:shadow-md rounded-lg">
        <div class="bg-white p-4 rounded-lg shadow h-full">
            <div class="flex justify-between items-center mb-2">
                <span class="text-lg font-semibold text-gray-800">${product.name}</span>
                <span class="text-xs text-gray-500">#${product.id}</span>
            </div>
            <p class="text-sm text-gray-600 mb-1">${product.description ?? 'بدون وصف'}</p>
            <div class="flex justify-between items-center mt-4 text-sm">
                <span class="text-blue-600 font-bold">${product.price} $</span>
                <span class="text-gray-500">الكمية: ${product.quantity}</span>
            </div>
            <span class="inline-block mt-3 text-xs px-2 py-1 rounded-full ${
                product.status === 'متوفر' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
            }">${product.status}</span>
        </div>
    </a>
`).join('');


            container.innerHTML = cards;

            // الترقيم
            const meta = data.meta;
            const links = [];
            for (let i = 1; i <= meta.last_page; i++) {
                links.push(`<button onclick="loadProducts(${i})"
                    class="px-3 py-1 rounded text-sm ${
                        i === meta.current_page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    }">${i}</button>`);
            }
            pagination.innerHTML = links.join("");
        } catch (error) {
            container.innerHTML = `<p class="col-span-full text-red-500">فشل في تحميل المنتجات.</p>`;
        }
    }
</script>
@endsection
