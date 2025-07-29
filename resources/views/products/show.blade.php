@extends('layouts.app')

@section('title', 'عرض المنتج')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-8 border-b pb-4 text-center">تفاصيل المنتج</h2>

    <!-- تفاصيل المنتج -->
    <div id="product-details">
        <p class="text-gray-500 text-center">جارٍ تحميل بيانات المنتج...</p>
    </div>

    <!-- أزرار التحكم -->
    <div class="mt-10 flex flex-col sm:flex-row justify-between items-center gap-4">
        <a href="/products"
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
        <p class="text-gray-600 mb-6">سيتم حذف المنتج نهائيًا.</p>
        <div class="flex justify-between gap-4">
            <button onclick="deleteProduct()"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 w-full">نعم، احذفه</button>
            <button onclick="closeModal()"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 w-full">إلغاء</button>
        </div>
    </div>
</div>

<script>
    const lastSegment = window.location.pathname.split('/').pop();
const productId = isNaN(lastSegment) ? null : lastSegment;
const container = document.getElementById("product-details");
    const editBtn = document.getElementById("edit-btn");
    const saveBtn = document.getElementById("save-btn");
if (!productId) {
    document.getElementById("product-details").innerHTML =
        `<p class="text-red-500 text-center">هذا الرابط غير مخصص لعرض منتج.</p>`;
}


    let productData = {};

        window.token = localStorage.getItem("token");

    async function fetchProduct() {
        try {
            const response = await fetch(`/api/products/${productId}`, {
                headers: {
                    Authorization: `Bearer ${window.token}`,
                    Accept: "application/json",
                }
            });

            const data = await response.json();

            if (!response.ok || !data.data) throw new Error("Not authorized");

            productData = data.data;
            renderProductView();

            const buyersResponse = await fetch(`/api/products/${productId}/buyers-count`, {
                headers: {
                   Authorization: `Bearer ${window.token}`,
                    Accept: "application/json",
                }
            });

            const buyersData = await buyersResponse.json();
            document.getElementById("buyers-count").innerText =
                buyersResponse.ok && buyersData.count !== undefined ? buyersData.count : '؟';

        } catch (e) {
            container.innerHTML = `<p class="text-red-500 text-center">فشل في تحميل تفاصيل المنتج.</p>`;
        }
    }

    function renderProductView() {
        const p = productData;
        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-right text-gray-700 text-sm leading-8">
                <div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">${p.name}</h3>
                    <p class="text-gray-600">${p.description ?? 'لا يوجد وصف'}</p>
                </div>

                <div class="space-y-2">
                    <p><span class="font-semibold">رقم المنتج:</span> #${p.id}</p>
                    <p><span class="font-semibold">السعر:</span> <span class="text-blue-600 font-bold">${p.price} $</span></p>
                    <p><span class="font-semibold">الكمية:</span> ${p.quantity}</p>
                    <p><span class="font-semibold">الحالة:</span>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium ${p.status === 'متوفر' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">${p.status}</span>
                    </p>
                    <p><span class="font-semibold">تم شراؤه من قبل:</span> <span id="buyers-count">...</span> زبون</p>
                </div>
            </div>
        `;
        saveBtn.classList.add("hidden");
        editBtn.classList.remove("hidden");
    }

    function enableEditing() {
        const p = productData;
        container.innerHTML = `
            <form class="grid grid-cols-1 md:grid-cols-2 gap-6 text-right text-gray-700 text-sm leading-8">
                <div>
                    <label class="font-semibold block mb-1">اسم المنتج</label>
                    <input id="name" type="text" class="w-full rounded-lg border px-4 py-2" value="${p.name}">

                    <label class="font-semibold block mt-4 mb-1">الوصف</label>
                    <textarea id="description" class="w-full rounded-lg border px-4 py-2">${p.description ?? ''}</textarea>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="font-semibold block mb-1">السعر</label>
                        <input id="price" type="number" class="w-full rounded-lg border px-4 py-2" value="${p.price}">
                    </div>

                    <div>
                        <label class="font-semibold block mb-1">الكمية</label>
                        <input id="quantity" type="number" class="w-full rounded-lg border px-4 py-2" value="${p.quantity}">
                    </div>

                    <div>
                        <label class="font-semibold block mb-1">الحالة</label>
                        <select id="status" class="w-full rounded-lg border px-4 py-2">
                            <option value="متوفر" ${p.status === 'متوفر' ? 'selected' : ''}>متوفر</option>
                            <option value="غير متوفر" ${p.status === 'غير متوفر' ? 'selected' : ''}>غير متوفر</option>
                        </select>
                    </div>
                </div>
            </form>
        `;
        saveBtn.classList.remove("hidden");
        editBtn.classList.add("hidden");
    }

    async function saveChanges() {
        const updated = {
            name: document.getElementById('name').value,
            description: document.getElementById('description').value,
            price: parseInt(document.getElementById('price').value),
            quantity: parseInt(document.getElementById('quantity').value),
            status: document.getElementById('status').value
        };

        try {
            const response = await fetch(`/api/products/${productId}`, {
                method: 'PUT',
                headers: {
                    Authorization: `Bearer ${window.token}`
,
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(updated)
            });

            const result = await response.json();
            if (response.ok) {
                productData = {...productData, ...updated};
                renderProductView();
                Swal.fire({
    icon: 'success',
    title: 'نجاح!',
    text: result.message || 'تم حفظ التعديلات بنجاح',
    confirmButtonText: 'موافق'
});

            } else {
                Swal.fire({
    icon: 'error',
    title: 'فشل!',
    text: result.message || 'فشل في التحديث',
    confirmButtonText: 'موافق'
});

            }
        } catch (error) {
            Swal.fire({
    icon: 'error',
    title: 'فشل!',
    text: result.message || 'حدذ خطأ أثناء الحفظ',
    confirmButtonText: 'موافق'
});

        }
    }

    function confirmDelete() {
        document.getElementById("confirmModal").classList.remove("hidden");
    }

    function closeModal() {
        document.getElementById("confirmModal").classList.add("hidden");
    }
async function deleteProduct() {
    try {
        const response = await fetch(`/api/products/${productId}`, {
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
                title: 'نجاح!',
                text: result.message || 'تم حذف المنتج بنجاح.',
                confirmButtonText: 'موافق'
            }).then(() => {
                // الانتقال بعد الضغط على "موافق"
                window.location.href = "/products";
            });

        } else {
            Swal.fire({
                icon: 'error',
                title: 'فشل!',
                text: result.message || 'فشل في حذف المنتج',
                confirmButtonText: 'موافق'
            });
        }

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'فشل!',
            text: 'فشل في حذف المنتج',
            confirmButtonText: 'موافق'
        });
    }
}


    fetchProduct();
</script>
@endsection
