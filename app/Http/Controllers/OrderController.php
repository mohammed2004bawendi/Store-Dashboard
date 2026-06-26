<?php

namespace App\Http\Controllers;

use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Actions\DeleteOrderAction;
use App\Domain\Orders\Actions\ExportOrdersAction;
use App\Domain\Orders\Actions\GenerateOrderInvoiceAction;
use App\Domain\Orders\Actions\ListCustomerOrdersAction;
use App\Domain\Orders\Actions\ListOrderProductsAction;
use App\Domain\Orders\Actions\ListOrdersAction;
use App\Domain\Orders\Actions\ShowOrderAction;
use App\Domain\Orders\Actions\UpdateOrderAction;
use App\Domain\Orders\Data\CreateOrderData;
use App\Domain\Orders\Data\OrderFiltersData;
use App\Domain\Orders\Data\UpdateOrderData;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Models\Customer;
use App\Models\Order;
use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    use AuthorizesRequests, ApiResponseTrait;

    public function indexall(Request $request, ListOrdersAction $listOrders)
    {
        Gate::authorize('view-orders');

        $orders = $listOrders->execute(
            OrderFiltersData::fromArray($request->all())
        );

        return OrderResource::collection($orders)->additional([
            'meta' => $listOrders->meta($orders),
        ]);
    }

    public function showOne(Order $order, ShowOrderAction $showOrder)
    {
        $this->authorize('view', $order);

        return new OrderResource($showOrder->execute($order));
    }

    public function showPage(Order $order)
    {
        return view('Orders.show', compact('order'));
    }

    public function index(Customer $customer, Request $request, ListCustomerOrdersAction $listCustomerOrders)
    {
        Gate::authorize('view-orders');

        $orders = $listCustomerOrders->execute(
            $customer,
            OrderFiltersData::fromArray($request->all())
        );

        return OrderResource::collection($orders);
    }

    public function create()
    {
        //
    }

    public function store(StoreOrderRequest $request, CreateOrderAction $createOrder)
    {
        $this->authorize('create', Order::class);

        $createOrder->execute(
            CreateOrderData::fromArray($request->validated())
        );

        return $this->success([], "\u{062A}\u{0645} \u{0625}\u{0646}\u{0634}\u{0627}\u{0621} \u{0627}\u{0644}\u{0637}\u{0644}\u{0628} \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}");
    }

    public function show(Customer $customer, Order $order, Request $request, ListOrderProductsAction $listOrderProducts)
    {
        $this->authorize('view', $order);

        $products = $listOrderProducts->execute(
            $customer,
            $order,
            $request->string('search')->toString() ?: null
        );

        return ProductResource::collection($products);
    }

    public function edit(Order $order)
    {
        //
    }

    public function update(UpdateOrderRequest $request, Order $order, UpdateOrderAction $updateOrder)
    {
        $this->authorize('update', $order);

        $updateOrder->execute(
            $order,
            UpdateOrderData::fromArray($request->validated())
        );

        return $this->success([], "\u{062A}\u{0645} \u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0637}\u{0644}\u{0628} \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}");
    }

    public function destroy(Order $order, DeleteOrderAction $deleteOrder)
    {
        $this->authorize('delete', $order);

        $deleteOrder->execute($order);

        return $this->success([], "\u{062A}\u{0645} \u{062D}\u{0630}\u{0641} \u{0627}\u{0644}\u{0637}\u{0644}\u{0628} \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}");
    }

    public function export(Request $request, ExportOrdersAction $exportOrders)
    {
        $export = $exportOrders->execute();

        if ($request->expectsJson()) {
            return response()->json([
                'url' => asset('storage/' . $export['filename']),
            ]);
        }

        return response()->download($export['path'])->deleteFileAfterSend(true);
    }

    public function downloadInvoice(Order $order, GenerateOrderInvoiceAction $generateOrderInvoice)
    {
        return response($generateOrderInvoice->execute($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-order-' . $order->id . '.pdf"',
        ]);
    }
}
