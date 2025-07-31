<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'لوحة التحكم')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md min-h-screen px-4 py-6">
        <h1 class="text-xl font-bold mb-8 text-center text-blue-600">Fiorella</h1>
        <nav class="space-y-6" id="sidebarNav">
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

            <div class="flex items-center gap-5">
                <!-- Notification Bell -->
                <div class="relative">
                    <button onclick="toggleNotifications()" class="relative focus:outline-none">
                        <i id="notif-icon" class="fas fa-bell text-gray-700 text-xl"></i>
                        <span id="notif-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full hidden">0</span>
                    </button>

                    <div id="notif-dropdown" class="absolute left-0 mt-2 w-80 bg-white shadow-lg rounded-lg p-4 z-50 hidden max-h-96 overflow-y-auto text-right">
                        <h3 class="font-semibold mb-2">الإشعارات</h3>
                        <ul id="notif-list" class="space-y-2">
                            <li class="text-gray-500 text-sm">جارٍ التحميل...</li>
                        </ul>
                    </div>
                </div>

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
            { name: 'لوحة التحكم', icon: 'layout-dashboard', path: '/dashboard', roles: ['admin', 'product_manager', 'support'] },
            { name: 'الملف الشخصي', icon: 'user-circle', path: '/profile', roles: ['admin', 'support', 'product_manager'] },
            { name: 'المنتجات', icon: 'shopping-bag', path: '/products', roles: ['product_manager', 'admin', 'support'] },
            { name: 'الزبائن', icon: 'users', path: '/customers', roles: ['support', 'admin'] },
            { name: 'الطلبات', icon: 'file-text', path: '/orders', roles: ['support', 'admin'] }
        ];

        async function fetchProfile() {
            if (!token) return redirectToLogin();

            try {
                const userRes = await fetch("/api/user", {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (!userRes.ok) throw new Error("Unauthorized");
                return await userRes.json();

            } catch (err) {
                console.error("فشل تحميل المستخدم", err);
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

        renderSidebar();

        function toggleNotifications() {
            const dropdown = document.getElementById("notif-dropdown");
            dropdown.classList.toggle("hidden");
        }

        async function loadNotifications() {
            try {
                const res = await fetch("/api/notifications", {
                    headers: { Authorization: `Bearer ${token}` }
                });

                if (!res.ok) return;

                const data = await res.json();
                const notifs = data.notifications;
                const list = document.getElementById("notif-list");
                const count = document.getElementById("notif-count");

                const unread = notifs.filter(n => !n.read_at);
                count.textContent = unread.length;
                count.classList.toggle("hidden", unread.length === 0);

                list.innerHTML = "";

                if (notifs.length === 0) {
                    list.innerHTML = '<li class="text-gray-400 text-sm">لا توجد إشعارات</li>';
                } else {
                    notifs.forEach(n => {
                    const isRead = n.read_at !== null;

                    list.innerHTML += `
                        <li class="border-b pb-2">
                            <a href="${n.data.url || '#'}" onclick="markAsRead('${n.id}')" class="text-sm text-gray-700 hover:text-blue-600 flex items-start gap-1">
                                <i class="fas fa-bell mt-1 ${isRead ? 'text-gray-400' : 'text-yellow-400'}"></i>
                                <div>
                                    <div>${n.data.title || 'إشعار'}</div>
                                    <div class="text-xs text-gray-500">المنتج: ${n.data.product_name ?? ''}</div>
                                </div>
                            </a>
                        </li>
                    `;
                });


                }

            } catch (e) {
                console.error("فشل في تحميل الإشعارات", e);
            }
        }

        async function markAsRead(id) {
            await fetch(`/api/notifications/${id}/read`, {
                method: 'POST',
                headers: { Authorization: `Bearer ${token}` }
            });
            loadNotifications();
        }

        loadNotifications();
    </script>

    @yield('scripts')
</body>
</html>
