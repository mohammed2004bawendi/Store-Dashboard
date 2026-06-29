<?php

namespace App\Ai;

use App\Ai\Agents\StoreAssistantAgent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\AiException;
use Throwable;

class StoreAssistant
{
    /**
     * @return array{reply: string, conversation_id: string|null, status: int}
     */
    public function reply(string $message, ?string $conversationId, User $user): array
    {
        if ($this->isWriteIntent($message)) {
            return [
                'reply' => 'لا أستطيع تنفيذ إنشاء أو تعديل أو حذف البيانات في هذه النسخة. أي إجراء يغيّر بيانات المتجر يحتاج تأكيدًا صريحًا وميزة منفصلة.',
                'conversation_id' => $conversationId,
                'status' => 200,
            ];
        }

        if (! $this->hasConfiguredProviderKey()) {
            return [
                'reply' => 'ميزة المساعد الذكي غير مفعّلة بعد. أضف مفتاح مزوّد الذكاء الاصطناعي في ملف .env ثم امسح كاش الإعدادات.',
                'conversation_id' => $conversationId,
                'status' => 503,
            ];
        }

        try {
            $agent = $conversationId
                ? StoreAssistantAgent::make()->continue($conversationId, as: $user)
                : StoreAssistantAgent::make()->forUser($user);

            $response = $agent->prompt($message);

            return [
                'reply' => $response->text,
                'conversation_id' => $response->conversationId,
                'status' => 200,
            ];
        } catch (AiException|Throwable $exception) {
            Log::warning('AI assistant request failed.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [
                'reply' => 'تعذر تشغيل المساعد الذكي الآن. حاول مرة أخرى لاحقًا أو راجع إعدادات مزوّد الذكاء الاصطناعي.',
                'conversation_id' => $conversationId,
                'status' => 502,
            ];
        }
    }

    protected function hasConfiguredProviderKey(): bool
    {
        $provider = config('ai.default', 'openai');

        if (! is_string($provider)) {
            return false;
        }

        return filled(config("ai.providers.{$provider}.key"));
    }

    protected function isWriteIntent(string $message): bool
    {
        $normalized = mb_strtolower($message);

        foreach (['احذف', 'حذف', 'امسح', 'عدل', 'تعديل', 'غير', 'غيّر', 'أنشئ', 'انشئ', 'اضف', 'أضف', 'create', 'update', 'delete', 'remove'] as $keyword) {
            if (str_contains($normalized, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
