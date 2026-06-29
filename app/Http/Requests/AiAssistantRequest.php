<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AiAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'اكتب رسالة للمساعد أولًا.',
            'message.string' => 'يجب أن تكون الرسالة نصًا.',
            'message.max' => 'الرسالة طويلة جدًا. الحد الأقصى 2000 حرف.',
            'conversation_id.string' => 'معرّف المحادثة غير صالح.',
        ];
    }
}
