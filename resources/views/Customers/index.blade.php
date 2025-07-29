@extends('layouts.app')

@section('title', 'الزبائن')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-slate-800">الزبائن</h2>
    <button onclick="toggleCreateForm()" class="bg-indigo-600 text-white px-5 py-2 rounded hover:bg-indigo-700 text-sm flex items-center gap-1">
        <i data-lucide="plus-circle" class="w-4 h-4"></i> إضافة زبون جديد
    </button>
</div>

<!-- نموذج إضافة زبون -->
<div id="create-form" class="bg-white p-4 rounded-lg shadow mb-6 hidden">
    <h3 class="text-lg font-bold text-slate-700 mb-4 text-center flex items-center justify-center gap-2">
        <i data-lucide="user-plus" class="w-5 h-5"></i> زبون جديد
    </h3>
    <form onsubmit="createCustomer(event)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="text" id="new-name" placeholder="اسم الزبون" class="border rounded-md px-3 py-2" required>
        <input type="text" id="new-phone" placeholder="رقم الهاتف" class="border rounded-md px-3 py-2" required>
        <input type="text" id="new-address" placeholder="العنوان" class="border rounded-md px-3 py-2 md:col-span-2" required>

        <div class="flex justify-start gap-3 mt-4">
            <button type="submit" class="bg-emerald-600 text-white text-sm px-6 py-2 rounded-md hover:bg-emerald-700 transition flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> حفظ الزبون
            </button>
            <button type="button" onclick="toggleCreateForm()" class="bg-gray-200 text-gray-700 text-sm px-6 py-2 rounded-md hover:bg-gray-300 transition">إلغاء</button>
        </div>
    </form>
</div>

<!-- 🔍 فلاتر البحث -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-white p-4 rounded-lg shadow">
    <div class="relative">
        <input type="text" id="filter-name" placeholder="بحث بالاسم..." class="border rounded-md px-10 py-2 w-full">
        <i data-lucide="search" class="absolute right-3 top-2.5 w-4 h-4 text-gray-400"></i>
    </div>
    <div class="relative">
        <input type="text" id="filter-phone" placeholder="بحث برقم الهاتف..." class="border rounded-md px-10 py-2 w-full">
        <i data-lucide="phone" class="absolute right-3 top-2.5 w-4 h-4 text-gray-400"></i>
    </div>
</div>

<!-- عرض الزبائن -->
<div id="customers-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <p class="col-span-full text-slate-500">جارٍ تحميل الزبائن...</p>
</div>

<div id="pagination" class="flex justify-center mt-6 gap-2"></div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
let currentPage = 1;

function toggleCreateForm() {
    document.getElementById("create-form").classList.toggle("hidden");
}

async function createCustomer(event) {
    event.preventDefault();
    const data = {
        name: document.getElementById("new-name").value,
        phone: document.getElementById("new-phone").value,
        address: document.getElementById("new-address").value,
    };

    try {
        const res = await fetch(`/api/customers`, {
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
            Swal.fire({ icon: 'success', title: 'تم إضافة الزبون!', text: 'تم حفظ الزبون بنجاح.' });
            toggleCreateForm();
            loadCustomers(1);
        } else {
            Swal.fire({ icon: 'error', title: 'فشل!', text: 'فشل في إضافة الزبون.' });
        }

    } catch (err) {
        alert("⚠️ حدث خطأ");
    }
}

async function loadCustomers(page = 1) {
    currentPage = page;
    const container = document.getElementById("customers-container");
    const pagination = document.getElementById("pagination");

    const nameFilter = document.getElementById("filter-name")?.value || '';
    const phoneFilter = document.getElementById("filter-phone")?.value || '';

    const params = new URLSearchParams();
    if (nameFilter) params.append("search", nameFilter);
    if (phoneFilter) params.append("phone", phoneFilter);
    params.append("page", page);
    params.append("per_page", 9);

    container.innerHTML = `<p class="col-span-full text-slate-500">جارٍ التحميل...</p>`;
    pagination.innerHTML = "";

    try {
        const res = await fetch(`/api/customers?${params.toString()}`, {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: "application/json",
            }
        });

        const data = await res.json();

        if (!res.ok || !data.data || data.data.length === 0) {
            container.innerHTML = `<p class="col-span-full text-slate-400">لا توجد نتائج مطابقة.</p>`;
            return;
        }

        const list = data.data.map(c => `
            <a href="/customers/${c.id}" class="block bg-white p-4 rounded-xl shadow hover:shadow-md transition duration-200">
                <h3 class="text-lg font-semibold text-slate-800 mb-1 flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-primary"></i> ${c.name}
                </h3>
                <p class="text-sm text-slate-600 flex items-center gap-1">
                    <i data-lucide="phone" class="w-4 h-4 text-sky-600"></i> ${c.phone}
                </p>
                <p class="text-sm text-slate-600 flex items-center gap-1 mt-1">
                    <i data-lucide="map-pin" class="w-4 h-4 text-lime-600"></i> ${c.address}
                </p>
            </a>
        `).join('');

        container.innerHTML = list;
        lucide.createIcons();

        // Pagination
        const meta = data.meta;
        const pages = [];
        for (let i = 1; i <= meta.last_page; i++) {
            pages.push(`<button onclick="loadCustomers(${i})"
                class="px-3 py-1 rounded text-sm ${i === meta.current_page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}">
                ${i}</button>`);
        }
        pagination.innerHTML = pages.join("");

    } catch (error) {
        container.innerHTML = `<p class="col-span-full text-rose-500">فشل في تحميل الزبائن.</p>`;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    loadCustomers();

    ["filter-name", "filter-phone"].forEach(id => {
        document.getElementById(id).addEventListener("input", () => loadCustomers(1));
    });

    lucide.createIcons();
});
</script>
@endsection
