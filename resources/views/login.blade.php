<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md relative animate-fadeIn">
        <div class="flex justify-center mb-6">
            <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel Logo" class="w-14 h-14">
        </div>

        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6"> الدخول</h2>

        <form id="login-form" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm mb-1" for="email">البريد الإلكتروني</label>
                <div class="relative">
                    <input type="email" name="email" id="email" required
                        class="pl-10 pr-3 py-2 border rounded w-full text-sm text-gray-700 shadow-sm focus:ring focus:ring-blue-200 focus:outline-none"
                        placeholder="you@example.com">
                    <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">
                        <i class="fas fa-envelope"></i>
                    </span>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm mb-1" for="password">كلمة المرور</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                        class="pl-10 pr-3 py-2 border rounded w-full text-sm text-gray-700 shadow-sm focus:ring focus:ring-blue-200 focus:outline-none"
                        placeholder="********">
                    <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded transition-all duration-150">
                دخول
            </button>
        </form>

        <p id="error-message" class="text-red-600 text-sm mt-4 text-center hidden"></p>
    </div>

    <script>
        document.getElementById("login-form").addEventListener("submit", async function (e) {
            e.preventDefault();

            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            const errorMessage = document.getElementById("error-message");

            try {
                const response = await fetch("/api/login", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok && data.token) {
                    localStorage.setItem("token", data.token);
                    window.location.href = "/dashboard";
                } else {
                    errorMessage.textContent = data.message || "فشل تسجيل الدخول";
                    errorMessage.classList.remove("hidden");
                }
            } catch (error) {
                errorMessage.textContent = "خطأ في الاتصال بالخادم";
                errorMessage.classList.remove("hidden");
            }
        });
    </script>

</body>
</html>
