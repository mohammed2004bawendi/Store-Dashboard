@extends('layouts.app')

@section('title', 'الملف الشخصي')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-lg mt-10 border border-gray-100">
    <div id="profile-section" class="flex flex-col items-center text-center gap-4">
        <p class="text-gray-500">جارٍ تحميل بيانات المستخدم...</p>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", async function () {
    const token = localStorage.getItem("token");
    const section = document.getElementById("profile-section");

    if (!token) return window.location.href = "/login";

    try {
        const res = await fetch("/api/user", {
            headers: {
                "Authorization": `Bearer ${token}`,
                "Accept": "application/json"
            }
        });

        if (!res.ok) throw new Error("Unauthorized");
        const user = await res.json();

        section.innerHTML = `
            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=3b82f6&color=fff&size=128"
                 class="w-28 h-28 rounded-full shadow-md border-4 border-blue-100" alt="الصورة الشخصية">

            <h2 class="text-2xl font-bold text-gray-800">${user.name}</h2>
            <p class="text-gray-600">${user.email}</p>

            <div class="text-sm text-gray-500 mt-2">
                <i class="fas fa-user-tag ml-1 text-purple-500"></i>
                الدور: <span class="font-semibold">${user.role ?? 'غير محدد'}</span>
            </div>
        `;
    } catch (error) {
        localStorage.removeItem("token");
        window.location.href = "/login";
    }
});
</script>
@endsection
