<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->whereNull('sku')
            ->orWhere('sku', '')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $product): void {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'sku' => "PROD-{$product->id}",
                        'status' => DB::raw("COALESCE(status, 'active')"),
                    ]);
            });
    }

    public function down(): void
    {
        //
    }
};
