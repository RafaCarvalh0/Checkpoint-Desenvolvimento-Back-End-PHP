@extends('layouts.react')

@php
    $title = 'Novo Produto';
    $appProps = [
        'page' => 'products.form',
        'mode' => 'create',
        'errors' => $errors->all(),
        'product' => [
            'name' => old('name', ''),
            'description' => old('description', ''),
            'price' => old('price', ''),
            'sku' => old('sku', ''),
            'stock' => old('stock', 0),
            'status' => old('status', 'active'),
        ],
        'statuses' => array_map(static fn ($status): array => [
            'value' => $status->value,
            'label' => $status->value === 'active' ? 'Ativo' : 'Inativo',
        ], $statuses),
        'images' => [],
    ];
@endphp
