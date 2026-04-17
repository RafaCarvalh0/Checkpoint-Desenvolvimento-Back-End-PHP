@extends('layouts.react')

@php
    $title = 'Editar Produto';
    $appProps = [
        'page' => 'products.form',
        'mode' => 'edit',
        'errors' => $errors->all(),
        'product' => [
            'id' => $product->getId(),
            'name' => old('name', $product->getName()),
            'description' => old('description', $product->getDescription()),
            'price' => old('price', $product->getPrice()),
            'sku' => old('sku', $product->getSku()),
            'stock' => old('stock', $product->getStock()),
            'status' => old('status', $product->getStatus()->value),
        ],
        'statuses' => array_map(static fn ($status): array => [
            'value' => $status->value,
            'label' => $status->value === 'active' ? 'Ativo' : 'Inativo',
        ], $statuses),
        'images' => $images->map(static fn ($image): array => [
            'id' => $image->id,
            'url' => $image->url,
            'thumbnail_url' => $image->thumbnail_url,
        ])->values()->all(),
    ];
@endphp
