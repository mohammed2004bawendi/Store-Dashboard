<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class OrderSearchAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are an internal assistant for a private Laravel Store Dashboard.
Convert natural-language order search text into structured filters only.
Never invent data.
Never modify orders.
Never generate SQL.
Only help with searching and filtering orders.
Support Arabic and English.

Available database concepts:
- orders.status
- orders.created_at
- orders.total_price
- customer.name
- customer.phone
- product.name
- order products count through the products relationship

Status aliases:
- Pending / processing / in progress / incomplete / قيد التنفيذ / معلقة / لم تكتمل => status: pending
- Completed / delivered / مكتملة / تم التوصيل => status: completed
- Cancelled / canceled / ملغي => status: cancelled

Return only fields that are clearly inferred from the query.
For a simple customer or product word, choose the most likely field. If unsure, use fallback_search.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description('One of: pending, completed, cancelled. Omit if not requested.'),
            'customer_name' => $schema->string()
                ->description('Customer name or partial customer name inferred from the query.'),
            'customer_phone_prefix' => $schema->string()
                ->description('Phone prefix when the query asks for customer phone starts with a value.'),
            'product_name' => $schema->string()
                ->description('Product name or partial product name inferred from the query.'),
            'created_today' => $schema->boolean()
                ->description('True when the user asks for orders created today.'),
            'limit' => $schema->integer()
                ->description('Maximum number of orders requested, such as last 10 orders.')
                ->min(1)
                ->max(50),
            'sort' => $schema->string()
                ->description('One of: latest, oldest. Use latest for recent or last orders.'),
            'min_products_count' => $schema->integer()
                ->description('Minimum number of products in the order when requested.')
                ->min(1)
                ->max(100),
            'fallback_search' => $schema->string()
                ->description('Plain text to use with the traditional search when no structured filter is clear.'),
        ];
    }
}
