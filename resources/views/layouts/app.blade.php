<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة التحكم')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div id="app-shell" class="flex min-h-screen">
        <aside id="app-sidebar" class="group/sidebar sticky top-0 z-30 flex h-screen w-72 shrink-0 flex-col border-l border-slate-200/80 bg-white/90 px-3 py-4 shadow-sm backdrop-blur transition-all duration-300">
            <div class="mb-5 flex items-center justify-between gap-3 px-2">
                <a href="/dashboard" class="flex min-w-0 items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-sm">
                        <i data-lucide="store" class="h-5 w-5"></i>
                    </span>
                    <span class="nav-label min-w-0">
                        <span class="block truncate text-base font-bold tracking-tight text-slate-950">Fiorella</span>
                        <span class="block truncate text-xs font-medium text-slate-500">Store Dashboard</span>
                    </span>
                </a>

                <button id="sidebar-toggle" type="button" class="app-icon-button shrink-0" aria-label="طي القائمة">
                    <i data-lucide="panel-right-close" class="h-4 w-4"></i>
                </button>
            </div>

            <nav id="sidebarNav" class="flex-1 space-y-1"></nav>

            <div class="nav-label rounded-2xl border border-slate-200 bg-slate-50 p-3 text-xs leading-6 text-slate-500">
                <div class="mb-1 flex items-center gap-2 font-semibold text-slate-700">
                    <i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i>
                    مساحة داخلية
                </div>
                النظام مخصص لإدارة الطلبات والمنتجات والعملاء.
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-slate-100/85 px-4 py-3 backdrop-blur lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">لوحة العمل</p>
                        <h1 class="truncate text-lg font-semibold text-slate-950">@yield('title', 'لوحة التحكم')</h1>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <button onclick="toggleNotifications()" type="button" class="app-icon-button relative" aria-label="الإشعارات">
                                <i id="notif-icon" data-lucide="bell" class="h-5 w-5"></i>
                                <span id="notif-count" class="absolute -right-1 -top-1 hidden min-w-[1.1rem] rounded-full bg-rose-500 px-1.5 py-0.5 text-center text-[10px] font-bold leading-none text-white">0</span>
                            </button>

                            <div id="notif-dropdown" class="absolute left-0 mt-3 hidden w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10">
                                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                    <h3 class="text-sm font-semibold text-slate-900">الإشعارات</h3>
                                    <span class="text-xs text-slate-400">آخر التحديثات</span>
                                </div>
                                <ul id="notif-list" class="max-h-96 space-y-1 overflow-y-auto p-2 text-right">
                                    <li class="rounded-xl px-3 py-2 text-sm text-slate-500">جار التحميل...</li>
                                </ul>
                            </div>
                        </div>

                        <div class="hidden items-center gap-3 rounded-full border border-slate-200 bg-white px-3 py-1.5 shadow-sm md:flex">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=0f172a&color=fff" class="h-8 w-8 rounded-full" alt="">
                            <div class="min-w-0">
                                <p class="max-w-32 truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name ?? 'مستخدم' }}</p>
                                <p class="text-xs text-slate-500">نشط الآن</p>
                            </div>
                        </div>

                        <button onclick="logout()" type="button" class="app-icon-button text-rose-600 hover:border-rose-200 hover:bg-rose-50" aria-label="تسجيل الخروج">
                            <i data-lucide="log-out" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const token = localStorage.getItem("token");

        const sidebarRoutes = [
            { name: 'لوحة التحكم', icon: 'layout-dashboard', path: '/dashboard', roles: ['admin', 'product_manager', 'support'] },
            { name: 'المساعد الذكي', icon: 'bot', path: '/ai-assistant', roles: ['admin', 'product_manager', 'support'] },
            { name: 'الملف الشخصي', icon: 'user-circle', path: '/profile', roles: ['admin', 'support', 'product_manager'] },
            { name: 'المنتجات', icon: 'shopping-bag', path: '/products', roles: ['product_manager', 'admin', 'support'] },
            { name: 'الزبائن', icon: 'users', path: '/customers', roles: ['support', 'admin'] },
            { name: 'الطلبات', icon: 'file-text', path: '/orders', roles: ['support', 'admin'] }
        ];

        const appShell = document.getElementById('app-shell');
        const sidebarToggle = document.getElementById('sidebar-toggle');

        function applySidebarState() {
            const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
            appShell.classList.toggle('sidebar-collapsed', isCollapsed);
            sidebarToggle.innerHTML = isCollapsed
                ? '<i data-lucide="panel-right-open" class="h-4 w-4"></i>'
                : '<i data-lucide="panel-right-close" class="h-4 w-4"></i>';
            lucide.createIcons();
        }

        sidebarToggle.addEventListener('click', () => {
            const nextState = localStorage.getItem('sidebar_collapsed') !== 'true';
            localStorage.setItem('sidebar_collapsed', nextState);
            applySidebarState();
        });

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
                if (!route.roles.includes(user.role)) return;

                const isActive = window.location.pathname === route.path || window.location.pathname.startsWith(route.path + '/');
                const link = document.createElement("a");
                link.href = route.path;
                link.className = [
                    "nav-item group flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition",
                    isActive
                        ? "bg-slate-950 text-white shadow-sm"
                        : "text-slate-600 hover:bg-slate-100 hover:text-slate-950"
                ].join(" ");
                link.innerHTML = `
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${isActive ? 'bg-white/10 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200 group-hover:text-slate-900'}">
                        <i data-lucide="${route.icon}" class="h-4 w-4"></i>
                    </span>
                    <span class="nav-label truncate">${route.name}</span>
                `;
                nav.appendChild(link);
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

        function toggleNotifications() {
            document.getElementById("notif-dropdown").classList.toggle("hidden");
        }

        async function loadNotifications() {
            try {
                const res = await fetch("/api/notifications", {
                    headers: { Authorization: `Bearer ${token}` }
                });

                if (!res.ok) return;

                const data = await res.json();
                const notifs = data.notifications || [];
                const list = document.getElementById("notif-list");
                const count = document.getElementById("notif-count");
                const unread = notifs.filter(n => !n.read_at);

                count.textContent = unread.length;
                count.classList.toggle("hidden", unread.length === 0);
                list.innerHTML = "";

                if (notifs.length === 0) {
                    list.innerHTML = '<li class="rounded-xl px-3 py-3 text-sm text-slate-500">لا توجد إشعارات</li>';
                    return;
                }

                notifs.forEach(n => {
                    const isRead = n.read_at !== null;
                    list.innerHTML += `
                        <li>
                            <a href="${n.data.url || '#'}" onclick="markAsRead('${n.id}')" class="flex items-start gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${isRead ? 'bg-slate-100 text-slate-400' : 'bg-amber-50 text-amber-500'}">
                                    <i class="fas fa-bell text-xs"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold">${n.data.title || 'إشعار'}</span>
                                    <span class="block truncate text-xs text-slate-500">المنتج: ${n.data.product_name ?? ''}</span>
                                </span>
                            </a>
                        </li>
                    `;
                });
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

        applySidebarState();
        renderSidebar();
        loadNotifications();
    </script>

    @yield('scripts')
</body>
</html>
