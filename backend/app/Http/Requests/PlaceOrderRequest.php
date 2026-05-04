<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Side;
use App\Enums\Symbol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PlaceOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'symbol' => ['required', Rule::enum(Symbol::class)],
            'side' => ['required', Rule::enum(Side::class)],
            'price' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,8})?$/'],
        ];
    }
}
