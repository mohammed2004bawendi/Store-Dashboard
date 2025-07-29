<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'لوحة التحكم')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Rf5L1K84bD5Y5fT6Vk+Ao2xZddzO5+3b5M+Po2Py+jXMaIj/HDZ4lxoyM2S9rk3c/k9pN9gqSzj9XJp3a5IlVg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- ✅ أيقونات Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md min-h-screen px-4 py-6">
        <h1 class="text-xl font-bold mb-8 text-center text-blue-600">Fiorella</h1>
        <nav class="space-y-6" id="sidebarNav">
            <!-- الروابط ستُضاف ديناميكيًا -->
        </nav>
    </aside>

    <!-- Content -->
    <main class="flex-1 p-6">
        <!-- Topbar -->
        <header class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <span class="font-medium text-gray-700">مرحباً، {{ auth()->user()->name ?? 'مستخدم' }}</span>
                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}" class="w-8 h-8 rounded-full" />
            </div>

            <div class="flex items-center gap-3">
                <button onclick="logout()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm">
                    تسجيل الخروج
                </button>
            </div>
        </header>

        <!-- Page Content -->
        @yield('content')
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const token = localStorage.getItem("token");

        const sidebarRoutes = [
            {
                name: 'لوحة التحكم',
                icon: 'layout-dashboard',
                path: '/dashboard',
                roles: ['admin', 'product_manager', 'support']
            },
            {
                name: 'الملف الشخصي',
                icon: 'user-circle',
                path: '/profile',
                roles: ['admin', 'support', 'product_manager']
            },
            {
                name: 'المنتجات',
                icon: 'shopping-bag',
                path: '/products',
                roles: ['product_manager', 'admin', 'support']
            },
            {
                name: 'الزبائن',
                icon: 'users',
                path: '/customers',
                roles: ['support', 'admin']
            },
            {
                name: 'الطلبات',
                icon: 'file-text',
                path: '/orders',
                roles: ['support', 'admin']
            }
        ];

        async function fetchProfile() {
            if (!token) return redirectToLogin();

            try {
                const userRes = await fetch("/api/user", {
                    headers: { Authorization: `Bearer ${token}` }
                });

                if (!userRes.ok) throw new Error("Unauthorized");

                const user = await userRes.json();
                return user;

            } catch (err) {
                console.error("Failed to fetch profile:", err);
                redirectToLogin();
            }
        }

        async function renderSidebar() {
            const user = await fetchProfile();
            if (!user) return;

            const nav = document.getElementById("sidebarNav");
            nav.innerHTML = "";

            sidebarRoutes.forEach(route => {
                if (route.roles.includes(user.role)) {
                    const link = document.createElement("a");
                    link.href = route.path;
                    link.className = "flex items-center gap-2 text-gray-700 hover:text-blue-600 transition";
                    link.innerHTML = `<i data-lucide="${route.icon}" class="w-5 h-5"></i><span>${route.name}</span>`;
                    nav.appendChild(link);
                }
            });

            lucide.createIcons();
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

        // بدء التطبيق
        renderSidebar();
    </script>

    @yield('scripts')
</body>
</html>
