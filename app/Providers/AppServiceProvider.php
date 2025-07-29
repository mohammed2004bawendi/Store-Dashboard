<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Order;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
            protected $policies = [
    \App\Models\Product::class => \App\Policies\ProductPolicy::class,
];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('view-products', fn($user) => in_array($user->role,['admin', 'product_manager', 'support']));
        Gate::define('view-orders', fn($user) => in_array($user->role, ['admin', 'support', 'product_manager']));
        Gate::define('view-customers', fn($user) => in_array($user->role, ['admin', 'product_manager', 'support']));
        Gate::define('view-dashboard', fn($user) => in_array($user->role, ['admin', 'product_manager', 'support']));


    }
}

/* ✅ التحسينات التي تحدثنا عنها (أو لمّحتُ لها في الشات):
1️⃣ ✅ التحقق من هوية المستخدم أو صلاحياته (Authorization)
بدل ما تسمح لأي شخص يعدل أو يحذف أي Order،

استعمل Policies أو Middleware للتحقق أن هذا الطلب يخص العميل نفسه.

🔸 مثال:





php
Copy
Edit
public function store(StoreOrderRequest $request) {
    // الكود بدون validation هنا 👍
}
3️⃣ ✅ نقل منطق الطلبات إلى Service Class
حاليًا منطق إنشاء Order موجود داخل Controller مباشرة.
يمكنك نقله إلى كلاس مثل:

bash
Copy
Edit
app/Services/OrderService.php
ليصبح الكود أنظف وأسهل في الاختبار والصيانة.


أو أنشئ Trait باسم ApiResponse يحتوي على:

php
Copy
Edit
protected function success($message, $data = [])
{
    return response()->json([
        'status' => 'success',
        'message' => $message,
        'data' => $data,
    ]);
}
6️⃣ ✅ تحسين الأداء عبر Eager Loading دائمًا
في أي with(), حاول تحديد فقط الحقول التي تحتاجها:

php
Copy
Edit
$orders = Order::with(['product:id,name,price'])->get();
7️⃣ ✅ تنظيف قاعدة البيانات تلقائيًا في الاختبارات
إذا أضفت اختبارات، استعمل RefreshDatabase لضمان نظافة البيانات.

🧠 الخلاصة:
التحسين	الهدف
✅ Middleware أو Policies	أمان وصلاحيات أوضح
✅ FormRequest	كود أنظف وأقصر
✅ Services	فصل المنطق عن الكنترولر
✅ Pivot Tables	دعم منتجات متعددة لكل طلب
✅ Response موحد	سهولة التعامل مع الواجهة
✅ Eager loading محسن	أداء أفضل
✅ تنظيم الكود	سهولة التطوير مستقبلًا

 */
