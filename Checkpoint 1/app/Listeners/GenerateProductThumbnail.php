<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Models\ProductImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GenerateProductThumbnail implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;

    public function handle(ProductCreated $event): void
    {
        ProductImage::query()
            ->where('product_id', $event->productId)
            ->whereNull('thumbnail_url')
            ->each(function (ProductImage $image): void {
                $image->update([
                    'thumbnail_url' => $image->url,
                ]);
            });
    }
}
