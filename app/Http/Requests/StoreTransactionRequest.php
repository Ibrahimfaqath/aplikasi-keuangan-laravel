<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Services\AmountFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50', Rule::in(Transaction::allCategories())],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'transaction_date' => ['required', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:20480'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => app(AmountFormatter::class)->normalize($this->input('amount')),
            ]);
        }
    }
}
