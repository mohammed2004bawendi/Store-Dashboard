<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Support\Facades\DB;
use Pest\ArchPresets\Custom;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\OrderService;
use App\Traits\ApiResponseTrait;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    use AuthorizesRequests;
    use ApiResponseTrait;

    /**
     * Display all orders with filters and meta info.
     */

    


    public function indexall(Request $request) {
        Gate::authorize('view-orders');

        $key = 'orders.page.' . $request->get('page', 1) . '.' . md5(json_encode($request->all()));

        $query = Cache::remember($key, 60, function () use ($request) {
        return $this->applyFilters(Order::query()->with('customer')->whereHas('customer')->with('products'), $request);
});

        $orders = $query->paginate();

        return OrderResource::collection($orders)->additional([
            'meta' => [
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('total_price'),
            ]
        ]);
    }

        private function applyFilters($query, Request $request)
    {

        return $query->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->min_total_price, fn($q, $min) => $q->where('total_price', '>=', $min))
            ->when($request->max_total_price, fn($q, $max) => $q->where('total_price', '<=', $max))
            ->when($request->search, function ($q, $search) {
            $q->where('id', $search)
              ->orWhereHas('customer', fn($qc) => $qc->where('name', 'like', "%$search%"));
    });
}

    /**
     * Show single order with products and customer.
     */
    public function showOne(Order $order)
    {
        $this->authorize('view', $order);

        $order->with(['products', 'customer']);

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

        $query = $customer->orders();

        $query->when($request->status, fn($q, $status) => $q->where('status', $status));
        $query->when($request->min_total_price, fn($q, $min) => $q->where('total_price', '>=', $min));
        $query->when($request->max_total_price, fn($q, $max) => $q->where('total_price', '<=', $max));

        return OrderResource::collection($query->paginate());
    }

    /**
     * Not implemented: show form for creating a new order.
     */
    public function create()
    {
        //
    }

    /**
     * Store new order using OrderService.
     */
    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        $this->authorize('create', Order::class);

        $validated = $request->validated();

        $orderService->create($validated);

        return $this->success([], 'تم إنشاء الطلب بنجاح');
    }

    /**
     * Show products in an order if belongs to the customer.
     */
    public function show(Customer $customer, Order $order, Request $request)
    {
        if ($customer->id != $order->customer_id) {
            return response()->json("هذا الزبون لا يمتلك هذه الطلبية");
        }

        $this->authorize('view', $order);

        $query = $order->products()->when($request->search, fn($q, $search) => $q->where('name', 'like', '%' . $search . '%'));

        return ProductResource::collection($query->get());
    }

    /**
     * Not implemented: show form to edit order.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update existing order using OrderService.
     */
    public function update(UpdateOrderRequest $request, OrderService $orderService, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validated();

        $customer = $order->customer;

        $orderService->update($validated, $customer, $order);

        return $this->success([], 'تم تحديث الطلب بنجاح');
    }

    /**
     * Delete order and related data.
     */
    public function destroy(Customer $customer, Order $order)
    {
        $this->authorize('delete', $order);

        $this->deleteOrderData($order);

        return $this->success([], 'تم حذف الطلب بنجاح');
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

        $orders = Order::with('customer')->get();

        SimpleExcelWriter::create($path)
            ->addRows($orders->map(function ($order) {
                return [
                    'رقم الطلب' => $order->id,
                    'العميل' => $order->customer->name ?? '',
                    'الحالة' => $order->status,
                    'الإجمالي' => $order->total_price,
                    'تاريخ الإنشاء' => $order->created_at->format('Y-m-d H:i:s'),
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
    public function downloadInvoice(Order $order)
    {
        $order->load(['customer', 'products']);

        $html = view('invoices.order', compact('order'))->render();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 12,
            'default_font' => 'amiri',
            'directionality' => 'rtl',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'fontDir' => array_merge($fontDirs, [
                resource_path('fonts'),
            ]),
            'fontdata' => $fontData + [
                'amiri' => [
                    'R' => 'Amiri-Regular.ttf',
                ],
            ],
        ]);

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-order-' . $order->id . '.pdf"',
        ]);
    }
}