<?php

use App\Exceptions\ProductNotFoundException;
use App\Support\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ProductNotFoundException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Produto não encontrado.',
                ], 404);
            }

            return response()->view('errors.product-not-found', [
                'message' => 'Produto não encontrado.',
            ], 404);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::validation($exception->errors());
            }

            return null;
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error('Não autenticado.', 401);
            }

            return null;
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error('Acesso não autorizado.', 403);
            }

            return null;
        });

        $exceptions->render(function (InvalidArgumentException $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error($exception->getMessage(), 400);
            }

            return null;
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();

                return ApiResponse::error(
                    $status === 404 ? 'Recurso não encontrado.' : 'Erro HTTP.',
                    $status
                );
            }

            return ApiResponse::error('Erro interno do servidor.', 500);
        });
    })->create();
