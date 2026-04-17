@extends('layouts.react')

@php
    $title = 'Catálogo de Produtos';
    $appProps = [
        'page' => 'products.index',
        'filters' => [
            'name' => $filters['name'] ?? '',
            'sku' => $filters['sku'] ?? '',
            'status' => $filters['status'] ?? '',
        ],
        'products' => array_map(static fn ($product): array => [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'sku' => $product->getSku(),
            'stock' => $product->getStock(),
            'status' => $product->getStatus()->value,
            'slug' => $product->getSlug(),
        ], $products),
    ];
@endphp
