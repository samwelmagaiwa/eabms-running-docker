<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SwaggerController;

// Load additional web routes (Swagger docs)
require_once __DIR__ . '/swagger.php';

Route::get('/', function () {
    return response()->json(['message' => 'Laravel API Server is running', 'timestamp' => now()]);
});
