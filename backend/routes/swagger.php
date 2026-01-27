<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SwaggerController;

/*
|--------------------------------------------------------------------------
| Swagger Routes
|--------------------------------------------------------------------------
|
| Routes for modern Swagger API documentation
|
*/

Route::group(['middleware' => ['web']], function () {
    // Modern API Documentation Route (points to our robust self-contained UI)
    Route::get('/api-docs-modern', [SwaggerController::class, 'documentation'])->name('swagger.modern');

    // Legacy redirects
    Route::get('api/documentation', function () {
        return redirect('/api-docs-modern');
    })->name('swagger.modern.api');
    
    Route::get('/documentation', function () {
        return redirect('/api-docs-modern');
    })->name('swagger.redirect');

    // OpenAPI JSON routes (pointing to our robust dynamic generator)
    Route::get('/api/api-docs', [SwaggerController::class, 'apiDocs'])->name('swagger.modern.docs.base');
    
    // Custom assets route
    Route::get('swagger-assets/{asset}', [SwaggerController::class, 'asset'])
        ->where('asset', '.*')
        ->name('swagger.modern.asset');
});

// Test documentation JSON - removed; use unified /api-docs-modern which reads generated OpenAPI JSON
