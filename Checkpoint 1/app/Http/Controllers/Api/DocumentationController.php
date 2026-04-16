<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function index(): View
    {
        return view('api.docs');
    }

    public function specification(): Response
    {
        return response((string) file_get_contents(base_path('docs/openapi.yaml')), 200, [
            'Content-Type' => 'application/yaml; charset=UTF-8',
        ]);
    }
}
