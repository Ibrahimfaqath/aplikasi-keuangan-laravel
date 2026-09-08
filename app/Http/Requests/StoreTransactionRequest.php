<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Services\AmountFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    /**
     * Kategori harus sesuai jenisnya (mis. expense tidak boleh pakai "Gaji").
     * Pengecekan dasar (in-list) tetap di rules(); di sini hanya cek konsistensi
     * pasangan type + category agar pesan error-nya spesifik.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            $category = $this->input('category');

            if (! in_array($type, ['income', 'expense'], true)) {
                return;
            }

            if (! is_string($category) || ! in_array($category, Transaction::allCategories(), true)) {
                return;
            }

            if (! in_array($category, Transaction::categoriesFor($type), true)) {
                $validator->errors()->add(
                    'category',
                    'Kategori tidak sesuai dengan jenis transaksi. Periksa lagi ya!'
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.in' => 'Kategori tidak valid. Pilih dari daftar yang tersedia.',
        ];
    }
}
