<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\OrderService;
use App\Traits\ApiResponseTrait;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class OrderController extends Controller
{
    use AuthorizesRequests;
    use ApiResponseTrait;

    /**
     * Build orders cache key with versioning
     */
    private function ordersCacheKey(Request $request): string
    {
        // Create/keep the cache version with initial value = 1
        $version = Cache::rememberForever('orders_cache_version', fn () => 1);

        // Sort query parameters and exclude 'page' from hash
        $params = $request->except('page');
        ksort($params);
        $hash = md5(json_encode($params));
        $page = (int) $request->get('page', 1);

        return "orders.v{$version}.page.{$page}.{$hash}";
    }

    /**
     * Increment orders cache version (old keys become obsolete automatically)
     */
    private function bumpOrdersCacheVersion(): void
    {
        // If version key does not exist, create it and then increment
        Cache::add('orders_cache_version', 1);
        Cache::increment('orders_cache_version');
    }

    /**
     * Display all orders with filters and meta info.
     */
    public function indexall(Request $request)
    {
        Gate::authorize('view-orders');

        $key = $this->ordersCacheKey($request);

        $orders = Cache::remember($key, 60, function () use ($request) {
            return $this->applyFilters(
                Order::query()
                    // Load only necessary columns for better performance
                    ->with(['customer', 'products'])
                    ->whereHas('customer'),
                $request
            )->paginate();
        });

        return OrderResource::collection($orders)->additional([
            'meta' => [
                // Note: count/sum are only for the current page
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('total_price'),
                // If you need the total for all results use:
                // 'total_orders_all' => $orders->total(),
            ]
        ]);
    }

    private function applyFilters($query, Request $request)
    {
        return $query
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->min_total_price, fn ($q, $min) => $q->where('total_price', '>=', $min))
            ->when($request->max_total_price, fn ($q, $max) => $q->where('total_price', '<=', $max))
            ->when($request->search, function ($q, $search) {
                $q->where('id', $search)
                  ->orWhereHas('customer', fn ($qc) => $qc->where('name', 'like', "%{$search}%"));
            });
    }

    /**
     * Show single order with products and customer.
     */
    public function showOne(Order $order)
    {
        $this->authorize('view', $order);

        // with() does not work on instance; use load() instead
        $order->load(['products', 'customer']);

        return new OrderResource($order);
    }

    /**
     * Return order view.
     */
    public function showPage(Order $order)
    {
        return view('Orders.show', compact('order'));
    }

    /**
     * List orders for a specific customer with filters.
     */
    public function index(Customer $customer, Request $request)
    {
        Gate::authorize('view-orders');

        $query = $customer->orders()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->min_total_price, fn ($q, $min) => $q->where('total_price', '>=', $min))
            ->when($request->max_total_price, fn ($q, $max) => $q->where('total_price', '<=', $max));

        return OrderResource::collection($query->paginate());
    }

    public function create() { /* ... */ }

    /**
     * Store new order using OrderService.
     */
    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        $this->authorize('create', Order::class);

        $validated = $request->validated();
        $order = $orderService->create($validated);

        $users = User::where('role', 'logistics')->get();
        Notification::send(notifiables: $users, notification: new OrderCreatedNotification($order));

        // Instead of manually deleting cache keys:
        $this->bumpOrdersCacheVersion();

        return $this->success([], 'Order created successfully');
    }

    /**
     * Show products in an order if belongs to the customer.
     */
    public function show(Customer $customer, Order $order, Request $request)
    {
        if ($customer->id != $order->customer_id) {
            return response()->json("This customer does not own this order");
        }

        $this->authorize('view', $order);

        $query = $order->products()
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"));

        return ProductResource::collection($query->get());
    }

    public function edit(Order $order) { /* ... */ }

    /**
     * Update existing order using OrderService.
     */
    public function update(UpdateOrderRequest $request, OrderService $orderService, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validated();
        $customer  = $order->customer;

        $orderService->update($validated, $customer, $order);

        // Bump version instead of deleting cache table entries
        $this->bumpOrdersCacheVersion();

        return $this->success([], 'Order updated successfully');
    }

    /**
     * Delete order and related data.
     */
    public function destroy(Customer $customer, Order $order)
    {
        $this->authorize('delete', $order);

        $this->deleteOrderData($order);

        // Bump version after deletion
        $this->bumpOrdersCacheVersion();

        return $this->success([], 'Order deleted successfully');
    }

    /**
     * Restore product quantities and remove order.
     */
    public function deleteOrderData(Order $order)
    {
        foreach ($order->products as $product) {
            $product->increment('quantity', $product->pivot->quantity);
        }

        $order->products()->detach();
        $order->delete();
    }

    /**
     * Export orders to Excel file.
     */
    public function export(Request $request)
    {
        $filename = 'orders_' . now()->timestamp . '.xlsx';
        $path = storage_path("app/public/{$filename}");

        // Load only customer name
        $orders = Order::with(['customer:id,name'])->get();

        SimpleExcelWriter::create($path)
            ->addRows($orders->map(function ($order) {
                return [
                    'Order ID'      => $order->id,
                    'Customer'      => $order->customer->name ?? '',
                    'Status'        => $order->status,
                    'Total'         => $order->total_price,
                    'Created At'    => $order->created_at->format('Y-m-d H:i:s'),
                ];
            }));

        if ($request->expectsJson()) {
            return response()->json([
                'url' => asset('storage/' . $filename)
            ]);
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }

    /**
     * Generate and download PDF invoice using mPDF.
     */
    public function downloadInvoice(Order $order, User $user)
    {
        $order->load(['customer', 'products']);

        $html = view('invoices.order', compact('order', 'user'))->render();

        $defaultConfig   = (new ConfigVariables())->getDefaults();
        $fontDirs        = $defaultConfig['fontDir'];
        $defaultFontConf = (new FontVariables())->getDefaults();
        $fontData        = $defaultFontConf['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 12,
            'default_font' => 'amiri',
            'directionality' => 'rtl',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'fontDir' => array_merge($fontDirs, [resource_path('fonts')]),
            'fontdata' => $fontData + [
                'amiri' => ['R' => 'Amiri-Regular.ttf'],
            ],
        ]);

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-order-' . $order->id . '.pdf"',
        ]);
    }
}
