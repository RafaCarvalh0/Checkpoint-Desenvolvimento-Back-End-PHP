@extends('layouts.react')

@php
    $title = $product->getName();
    $appProps = [
        'page' => 'products.show',
        'product' => [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'sku' => $product->getSku(),
            'stock' => $product->getStock(),
            'status' => $product->getStatus()->value,
            'slug' => $product->getSlug(),
        ],
        'images' => $images->map(static fn ($image): array => [
            'id' => $image->id,
            'url' => $image->url,
            'thumbnail_url' => $image->thumbnail_url,
        ])->values()->all(),
    ];
@endphp
