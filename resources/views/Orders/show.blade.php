@extends('layouts.app')

@section('title', 'تفاصيل الطلب')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-8 border-b pb-4 text-center">تفاصيل الطلب</h2>

    <!-- تفاصيل الطلب -->
    <div id="order-details">
        <p class="text-gray-500 text-center">جارٍ تحميل بيانات الطلب...</p>
    </div>

    <!-- أزرار التحكم -->




<div class="mt-10 flex flex-col sm:flex-row justify-between items-center gap-3">
    <!-- زر الرجوع -->
    <a href="/orders"
       class="bg-blue-600 text-white text-sm px-6 py-2 rounded-lg hover:bg-blue-700 transition w-40 text-center flex items-center justify-center gap-2">
        <i data-lucide="arrow-right-circle" class="w-4 h-4"></i> الرجوع
    </a>

    <!-- زر حفظ -->
    <button id="save-btn" onclick="saveChanges()"
        class="hidden bg-green-600 text-white text-sm px-6 py-2 rounded-lg hover:bg-green-700 transition w-40 flex items-center justify-center gap-2">
        <i data-lucide="folder-check" class="w-4 h-4"></i> حفظ التعديلات
    </button>

    <!-- زر تعديل -->
    <button id="edit-btn" onclick="enableEditing()"
        class="bg-yellow-500 text-white text-sm px-6 py-2 rounded-lg hover:bg-yellow-600 transition w-40 flex items-center justify-center gap-2">
        <i data-lucide="pencil-line" class="w-4 h-4"></i> تعديل
    </button>

    <!-- زر حذف -->
    <button onclick="confirmDelete()"
        class="bg-red-600 text-white text-sm px-6 py-2 rounded-lg hover:bg-red-700 transition w-40 flex items-center justify-center gap-2">
        <i data-lucide="trash-2" class="w-4 h-4"></i> حذف
    </button>

    <!-- زر تحميل الفاتورة -->
    <button id="download-btn" data-id="{{ $order->id }}"
        class="bg-indigo-600 text-white text-sm px-6 py-2 rounded-lg hover:bg-indigo-700 transition w-40 flex items-center justify-center gap-2">
        <i data-lucide="file-down" class="w-4 h-4"></i> تحميل الفاتورة
    </button>
</div>

</div>

<!-- مودال تأكيد الحذف -->
<div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-80 text-center">
        <h3 class="text-lg font-bold mb-4 text-gray-800">هل أنت متأكد؟</h3>
        <p class="text-gray-600 mb-6">سيتم حذف الطلب نهائيًا.</p>
        <div class="flex justify-between gap-4">
            <button onclick="deleteOrder()"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 w-full">نعم، احذفه</button>
            <button onclick="closeModal()"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 w-full">إلغاء</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>

document.getElementById('download-btn').addEventListener('click', function () {
    const orderId = this.getAttribute('data-id');
    const token = localStorage.getItem('token');

    fetch(`/api/orders/${orderId}/invoice`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/pdf'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('فشل في تحميل الملف');
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `invoice-order-${orderId}.pdf`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        alert('حدث خطأ أثناء تحميل الفاتورة');
        console.error(error);
    });
});

const orderId = window.location.pathname.split('/').pop();
const container = document.getElementById("order-details");
const editBtn = document.getElementById("edit-btn");
const saveBtn = document.getElementById("save-btn");
let orderData = {};

async function fetchOrder() {
    try {
        const res = await fetch(`/api/orders/${orderId}`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        const response = await res.json();
        if (!res.ok) throw new Error("Fetch error");

        orderData = response.data;
        renderOrderView();
    } catch (err) {
        container.innerHTML = `<p class="text-red-500 text-center">فشل في تحميل تفاصيل الطلب.</p>`;
    }
}

function renderOrderView() {
    const o = orderData;
    const products = o.products?.map(p => `<li class="border rounded p-2 flex justify-between"><span>${p.name}</span><span>${p.price} د.ل × ${p.quantity}</span></li>`).join('') || '';
    container.innerHTML = `
        <div class="space-y-4 text-sm text-gray-700">
            <p><strong>الزبون:</strong> ${o.customer?.name ?? 'غير معروف'}</p>
            <p><strong>العنوان:</strong> ${o.customer?.address ?? 'غير معروف'}</p>
            <p><strong>السعر الكلي:</strong> ${o.total_price ?? '؟'} د.ل</p>
            <p><strong>الحالة:</strong> ${o.status ?? '؟'}</p>
            <p><strong>تاريخ الإنشاء:</strong> ${new Date(o.created_at).toLocaleString() ?? '؟'}</p>
            <div>
                <h4 class="font-bold">المنتجات:</h4>
                <ul class="space-y-1">${products}</ul>
            </div>
        </div>
    `;
    saveBtn.classList.add("hidden");
    editBtn.classList.remove("hidden");
}

function enableEditing() {
    const o = orderData;
    container.innerHTML = `
        <form class="space-y-4 text-sm text-gray-700">
            <label class="block">
                <span class="font-semibold">الاسم</span>
                <input id="name" type="text" class="w-full rounded border px-3 py-2" value="${o.customer?.name || ''}">
            </label>
            <label class="block">
                <span class="font-semibold">العنوان</span>
                <input id="address" type="text" class="w-full rounded border px-3 py-2" value="${o.customer?.address || ''}">
            </label>
            <label class="block">
                <span class="font-semibold">الحالة</span>
                <select id="status" class="w-full rounded border px-3 py-2">
                    <option value="قيد التنفيذ" ${o.status === 'قيد التنفيذ' ? 'selected' : ''}>قيد التنفيذ</option>
                    <option value="تم التوصيل" ${o.status === 'تم التوصيل' ? 'selected' : ''}>تم التوصيل</option>
                    <option value="ملغي" ${o.status === 'ملغي' ? 'selected' : ''}>ملغي</option>
                </select>
            </label>
            <label class="block">
                <span class="font-semibold">السعر الكلي</span>
                <input id="total_price" type="number" class="w-full rounded border px-3 py-2" value="${o.total_price}">
            </label>
        </form>
    `;
    saveBtn.classList.remove("hidden");
    editBtn.classList.add("hidden");
}

async function saveChanges() {
    const updated = {
        status: document.getElementById("status").value,
        total_price: parseFloat(document.getElementById("total_price").value),
        customer: {
            name: document.getElementById("name").value,
            address: document.getElementById("address").value
        }
    };

    try {
        const res = await fetch(`/api/orders/${orderId}`, {
            method: "PUT",
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: "application/json",
                "Content-Type": "application/json"
            },
            body: JSON.stringify(updated)
        });

        const result = await res.json();
        if (res.ok) {
            orderData = { ...orderData, ...updated, customer: updated.customer };
            renderOrderView();
            Swal.fire('تم التحديث', result.message || 'تم تحديث الطلب بنجاح', 'success');
        } else {
            Swal.fire('خطأ', result.message || 'فشل في التحديث', 'error');
        }
    } catch (err) {
        Swal.fire('خطأ', 'حدث خطأ أثناء التحديث', 'error');
    }
}

function confirmDelete() {
    document.getElementById("confirmModal").classList.remove("hidden");
}

function closeModal() {
    document.getElementById("confirmModal").classList.add("hidden");
}

async function deleteOrder() {
    try {
        const res = await fetch(`/api/orders/${orderId}`, {
            method: "DELETE",
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: "application/json"
            }
        });

        const result = await res.json();
        if (res.ok) {
            Swal.fire('تم الحذف', result.message || 'تم حذف الطلب بنجاح', 'success').then(() => {
                window.location.href = "/orders";
            });
        } else {
            Swal.fire('خطأ', result.message || 'فشل في حذف الطلب', 'error');
        }
    } catch (err) {
        Swal.fire('خطأ', 'فشل في حذف الطلب', 'error');
    }
}

fetchOrder();
</script>
@endsection
