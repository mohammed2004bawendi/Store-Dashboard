<?php

namespace App\Admin\Ai\Controllers;

use App\Admin\Ai\Requests\AiAssistantRequest;
use App\Ai\StoreAssistant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AiAssistantController extends Controller
{
    public function __invoke(AiAssistantRequest $request, StoreAssistant $assistant): JsonResponse
    {
        Gate::authorize('view-products');

        $result = $assistant->reply(
            message: $request->string('message')->toString(),
            conversationId: $request->string('conversation_id')->toString() ?: null,
            user: $request->user(),
        );

        return response()->json([
            'reply' => $result['reply'],
            'conversation_id' => $result['conversation_id'],
        ], $result['status']);
    }
}
