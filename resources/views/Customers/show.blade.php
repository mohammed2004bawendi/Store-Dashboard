@extends('layouts.app')

@section('title', 'عرض الزبون')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
    <h2 class="text-3xl font-bold text-gray-800 mb-8 border-b pb-4 text-center flex items-center justify-center gap-2">
        <i data-lucide="badge-check" class="w-6 h-6 text-indigo-600"></i> تفاصيل الزبون
    </h2>

    <div id="customer-details">
        <p class="text-center text-gray-500">جارٍ تحميل بيانات الزبون...</p>
    </div>

    <!-- الطلبات -->
    <div id="orders-section" class="mt-10">
        <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
            <i data-lucide="clipboard-list" class="w-5 h-5 text-gray-600"></i> طلبات الزبون
        </h3>
        <div id="order-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <p class="col-span-full text-gray-400">جارٍ تحميل الطلبات...</p>
        </div>
    </div>

    <!-- أزرار التحكم -->
    <div class="mt-10 flex flex-col sm:flex-row justify-between items-center gap-4">
        <a href="/customers"
           class="bg-blue-600 text-white text-sm px-6 py-2 rounded-md hover:bg-blue-700 transition w-40 text-center flex justify-center items-center gap-2">
            <i data-lucide="arrow-right-circle" class="w-4 h-4"></i> الرجوع
        </a>

        <button id="save-btn" onclick="saveChanges()"
            class="hidden bg-green-600 text-white text-sm px-6 py-2 rounded-md hover:bg-green-700 transition w-40 flex justify-center items-center gap-2">
            <i data-lucide="save" class="w-4 h-4"></i> حفظ التعديلات
        </button>

        <button id="edit-btn" onclick="enableEditing()"
            class="bg-yellow-500 text-white text-sm px-6 py-2 rounded-md hover:bg-yellow-600 transition w-40 flex justify-center items-center gap-2">
            <i data-lucide="edit-3" class="w-4 h-4"></i> تعديل
        </button>

        <button onclick="confirmDelete()"
            class="bg-red-600 text-white text-sm px-6 py-2 rounded-md hover:bg-red-700 transition w-40 flex justify-center items-center gap-2">
            <i data-lucide="trash" class="w-4 h-4"></i> حذف
        </button>
    </div>
</div>

<!-- مودال تأكيد الحذف -->
<div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-80 text-center">
        <h3 class="text-lg font-bold mb-4 text-gray-800">هل أنت متأكد؟</h3>
        <p class="text-gray-600 mb-6">سيتم حذف الزبون نهائيًا.</p>
        <div class="flex justify-between gap-4">
            <button onclick="deleteCustomer()"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 w-full">نعم، احذفه</button>
            <button onclick="closeModal()"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 w-full">إلغاء</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const customerId = window.location.pathname.split("/").pop();
    const container = document.getElementById("customer-details");
    const saveBtn = document.getElementById("save-btn");
    const editBtn = document.getElementById("edit-btn");

    let customerData = {};

    async function fetchCustomer() {
        try {
            const res = await fetch(`/api/customers/${customerId}`, {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json"
                }
            });

            const result = await res.json();
            if (!res.ok) throw new Error("خطأ في التحميل");

            customerData = result.data;
            renderCustomerView();
        } catch (e) {
            container.innerHTML = `<p class="text-red-500 text-center">فشل في تحميل بيانات الزبون.</p>`;
        }
    }

    function renderCustomerView() {
    const c = customerData;
    container.innerHTML = `
        <div class="text-center text-gray-700 leading-8 space-y-2">
            <h3 class="text-2xl font-semibold text-blue-700 flex justify-center items-center gap-2">
                <i data-lucide="user" class="w-5 h-5 text-blue-600"></i> ${c.name}
            </h3>
            <p class="flex justify-center items-center gap-1 text-gray-600">
                <i data-lucide="phone" class="w-4 h-4 text-red-500"></i>
                <span class="font-medium">رقم الهاتف:</span> ${c.phone}
            </p>
            <p class="flex justify-center items-center gap-1 text-gray-600">
                <i data-lucide="home" class="w-4 h-4 text-green-600"></i>
                <span class="font-medium">العنوان:</span> ${c.address}
            </p>
        </div>
    `;
    saveBtn.classList.add("hidden");
    editBtn.classList.remove("hidden");
    lucide.createIcons();
}


    function enableEditing() {
    const c = customerData;
    container.innerHTML = `
        <form class="grid gap-4 text-right text-gray-700 text-sm leading-8">
            <label class="block">
                <span class="font-semibold">الاسم</span>
                <input id="name" type="text" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-400" value="${c.name}">
            </label>
            <label class="block">
                <span class="font-semibold">رقم الهاتف</span>
                <input id="phone" type="text" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-400" value="${c.phone}">
            </label>
            <label class="block">
                <span class="font-semibold">العنوان</span>
                <textarea id="address" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-400">${c.address}</textarea>
            </label>
        </form>
    `;
    saveBtn.classList.remove("hidden");
    editBtn.classList.add("hidden");
}

    async function saveChanges() {
        const updated = {
            name: document.getElementById("name").value,
            phone: document.getElementById("phone").value,
            address: document.getElementById("address").value,
        };

        try {
            const response = await fetch(`/api/customers/${customerId}`, {
                method: "PUT",
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(updated)
            });

            const result = await response.json();

            if (response.ok) {
                customerData = { ...customerData, ...updated };
                renderCustomerView();
                Swal.fire({
                    icon: 'success',
                    title: 'تم التحديث',
                    text: result.message || 'تم تحديث بيانات الزبون بنجاح',
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'فشل',
                    text: result.message || 'فشل في التحديث',
                });
            }

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'حدث خطأ أثناء حفظ البيانات',
            });
        }
    }

    function confirmDelete() {
        document.getElementById("confirmModal").classList.remove("hidden");
    }

    function closeModal() {
        document.getElementById("confirmModal").classList.add("hidden");
    }

    async function deleteCustomer() {
        try {
            const response = await fetch(`/api/customers/${customerId}`, {
                method: 'DELETE',
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json',
                }
            });

            const result = await response.json();

            if (response.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم الحذف',
                    text: result.message || 'تم حذف الزبون بنجاح',
                }).then(() => {
                    window.location.href = "/customers";
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'فشل',
                    text: result.message || 'فشل في حذف الزبون',
                });
            }

        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'فشل في حذف الزبون',
            });
        }
    }

    async function fetchOrders() {
    const orderContainer = document.getElementById("order-list");
    orderContainer.innerHTML = `<p class="col-span-full text-gray-500">جارٍ تحميل الطلبات...</p>`;

    try {
        const res = await fetch(`/api/customers/${customerId}/orders`, {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: "application/json"
            }
        });

        const data = await res.json();
        if (!res.ok || !data.data) throw new Error("فشل في تحميل الطلبات");

        if (data.data.length === 0) {
            orderContainer.innerHTML = `<p class="col-span-full text-gray-500">لا توجد طلبات لهذا الزبون.</p>`;
            return;
        }

        const cards = data.data.map(order => `
            <div class="bg-white rounded-lg shadow-md p-4 flex flex-col justify-between hover:shadow-lg transition">
                <h4 class="font-bold text-gray-800 mb-2">#طلب رقم: ${order.id}</h4>
                <ul class="text-sm text-gray-600 space-y-1 mb-2">
                    <li><strong>الحالة:</strong> <span class="${getStatusBadge(order.status)}">${order.status}</span></li>
                    <li><strong>الإجمالي:</strong> ${order.total_price} د.ل</li>
                    <li class="text-xs text-gray-400 mt-1">${new Date(order.created_at).toLocaleString()}</li>
                </ul>
                <a href="/orders/${order.id}" class="text-blue-600 text-sm hover:underline mt-auto self-end">عرض التفاصيل →</a>
            </div>
        `).join("");

        orderContainer.innerHTML = cards;

    } catch {
        orderContainer.innerHTML = `<p class="col-span-full text-red-500">فشل في تحميل الطلبات.</p>`;
    }
}

function getStatusBadge(status) {
    switch (status) {
        case "تم التوصيل":
            return "bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium";
        case "قيد التنفيذ":
            return "bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium";
        case "ملغي":
            return "bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium";
        default:
            return "bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium";
    }
}


    fetchCustomer();
    fetchOrders();
            lucide.createIcons();

</script>
@endsection
