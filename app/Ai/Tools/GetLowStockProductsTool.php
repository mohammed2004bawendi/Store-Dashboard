<?php

namespace App\Ai\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetLowStockProductsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Get products whose remaining inventory quantity is less than or equal to a threshold. Use this when the user asks about low-stock, nearly out-of-stock, missing, or almost-finished products.';
    }

    public function handle(Request $request): Stringable|string
    {
        $threshold = max(0, min((int) $request->integer('threshold', 5), 100));
        $quantityExpression = Product::query()->getConnection()->getDriverName() === 'mysql'
            ? 'CAST(quantity AS UNSIGNED)'
            : 'CAST(quantity AS INTEGER)';

        $products = Product::query()
            ->select(['id', 'name', 'quantity', 'price'])
            ->whereRaw("{$quantityExpression} <= ?", [$threshold])
            ->orderByRaw("{$quantityExpression} asc")
            ->orderBy('name')
            ->limit(25)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => (int) $product->quantity,
                'price' => is_numeric($product->price) ? (float) $product->price : $product->price,
            ]);

        return json_encode([
            'threshold' => $threshold,
            'products' => $products,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'threshold' => $schema->integer()
                ->description('Maximum product quantity that should be considered low stock. Default is 5.')
                ->min(0)
                ->max(100),
        ];
    }
}
