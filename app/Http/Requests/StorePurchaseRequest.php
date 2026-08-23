<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_kobo' => ['required', 'integer', 'min:100'],
            'reference' => ['required', 'string', 'max:100', 'unique:purchases,reference'],
        ];
    }
}
