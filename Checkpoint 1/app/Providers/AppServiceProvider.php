<?php

namespace App\Providers;

use App\Domain\Products\ProductRepositoryInterface;
use App\Infrastructure\Persistence\EloquentProductRepository;
use App\Models\Product;
use App\Policies\ProductPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            EloquentProductRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);

        RateLimiter::for('auth-actions', function (Request $request): Limit {
            $email = strtolower((string) $request->input('email'));
            $key = $email !== '' ? $email.'|'.$request->ip() : $request->ip();

            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('product-writes', function (Request $request): Limit {
            $userId = $request->user()?->id;

            return Limit::perMinute(30)->by($userId ? "user:{$userId}" : $request->ip());
        });
    }
}
