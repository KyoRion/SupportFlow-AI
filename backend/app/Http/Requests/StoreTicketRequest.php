<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],

            'order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')
                    ->where(
                        'customer_id',
                        (int) $this->input('customer_id')
                    ),
            ],

            'subject' => [
                'required', 'string', 'max:200',
            ],

            'description' => [
                'required', 'string', 'max:10000',
            ],

            'priority' => [
                'sometimes',
                Rule::enum(TicketPriority::class),
            ],

            'assigned_to' => [
                'nullable', 'integer', 'exists:users,id',
            ],
        ];
    }
}
