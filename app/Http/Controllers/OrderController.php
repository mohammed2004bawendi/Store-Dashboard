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





class OrderController extends Controller
{

    use AuthorizesRequests;
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */

     public function indexall(Request $request) {
        Gate::authorize('view-orders');


       $query = Order::query()->with('customer')->whereHas('customer')->with('products');

       $query->when($request->status, fn($q, $status) => $q->where('status', $status));
        $query->when($request->min_total_price, fn($q, $min) => $q->where('total_price', '>=', $min));
        $query->when($request->max_total_price, fn($q, $max) => $q->where('total_price', '<=', $max));
        $query->when($request->search, function ($q, $search) {
          $q->where('id', $search)
         ->orWhereHas('customer', fn($qc) => $qc->where('name', 'like', "%$search%"));
});


        return OrderResource::collection($query->paginate())->additional([
    'meta' => [
        'total_orders' => $query->count(),
        'total_amount' => $query->sum('total_price'),
    ]
]);




     }

     public function showOne(Order $order)
{
     Gate::authorize('view', $order);

    $order->with(['products', 'customer']);

    return new OrderResource($order);
}

public function showPage(Order $order)
{
    return view('Orders.show', compact('order'));
}



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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
 public function store(StoreOrderRequest $request, OrderService $orderService)
{
    $this->authorize('create', Order::class);

    $validated = $request->validated();

    $orderService->create($validated);

    return $this->success([], 'تم إنشاء الطلب بنجاح');

}

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer, Order $order, Request $request)
    {

        $query = $order->products()->when($request->search, fn($q, $search) => $q->where('name', 'like', '%'. $search . '%' ));

        Gate::authorize('view', $order);


        return ProductResource::collection($query->get());
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
public function update(UpdateOrderRequest $request,OrderService $orderService, Order $order)
{
    $this->authorize('update', $order);

    $validated = $request->validated();

    $customer = $order->customer;

    $orderService->update($validated, $customer, $order);

    return $this->success([], 'تم تحديث الطلب بنجاح');

}






    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer, Order $order)
    {

        $this->authorize('delete', $order, $customer);
        $this->deleteOrderData($order);

          return $this->success([], 'تم حذف الطلب بنجاح');

    }

    public function deleteOrderData(Order $order)
{
   foreach($order->products as $product) {
        $product->increment('quantity', $product->pivot->quantity);
   }

   $order->products()->detach();

   $order->delete();
}

public function export(Request $request)
{
    $filename = 'orders_' . now()->timestamp . '.xlsx';
    $path = storage_path("app/public/{$filename}");

     $orders = Order::with('customer')->get();

    SimpleExcelWriter::create($path)
        ->addRows($orders->map(function ($order) {

            return [
                'رقم الطلب' => $order->id,
                'العميل' => $order->customer->name??'',
                'الحالة' => $order->status,
                'الإجمالي' => $order->total_price,
                'تاريخ الإنشاء' => $order->created_at->format('Y-m-d H:i:s'),
            ];
        })
    );


    if ($request->expectsJson()) {

        return response()->json([
            'url' => asset('storage/' .$filename)
        ]);
    }


    return response()->download($path)->deleteFileAfterSend(true);
}




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


