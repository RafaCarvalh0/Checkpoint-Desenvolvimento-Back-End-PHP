<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'sku' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('products', 'sku')->ignore($this->route('product')),],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do produto.',
            'name.max' => 'O nome deve ter no máximo 120 caracteres.',
            'price.required' => 'Informe o preço do produto.',
            'price.numeric' => 'O preço deve ser numérico.',
            'price.min' => 'O preço não pode ser negativo.',
            'sku.required' => 'Informe o SKU do produto.',
            'sku.regex' => 'O SKU deve conter apenas letras, números, hífen ou sublinhado.',
            'sku.unique' => 'Já existe um produto com este SKU.',
            'sku.max' => 'O SKU deve ter no máximo 60 caracteres.',
            'stock.required' => 'Informe o estoque do produto.',
            'stock.integer' => 'O estoque deve ser um número inteiro.',
            'stock.min' => 'O estoque não pode ser negativo.',
            'status.required' => 'Informe o status do produto.',
            'status.in' => 'O status informado é inválido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'description' => 'descrição',
            'price' => 'preço',
            'sku' => 'SKU',
            'stock' => 'estoque',
            'status' => 'status',
        ];
    }

    protected function prepareForValidation(): void
    {
        $description = $this->input('description');

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => is_string($description) && trim($description) !== ''
                ? trim($description)
                : null,
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'status' => trim((string) $this->input('status')),
        ]);
    }
}
