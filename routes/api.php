<?php

use App\Http\Controllers\Admin\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstagramProfileController;
use App\Http\Controllers\Admin\RunController;
use App\Http\Controllers\Admin\StructureOutputController;
use App\Http\Controllers\Admin\StructureOutputGroupController;
use App\Http\Controllers\Api\V1\AttributeController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\InstagramController;
use App\Http\Controllers\ProductProcessorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health Check
Route::get('health', function () {
    return response()->json(['status' => 'ok']);
});

// Instagram Routes
Route::prefix('instagram')->group(function () {
    Route::get('{username}/profile', [InstagramController::class, 'upsertProfile']);
    Route::get('{igId}/posts', [InstagramController::class, 'upsertPosts']);
});

// Product Processing
Route::get('process-products/{username}', [ProductProcessorController::class, 'process']);
Route::get('label-posts/{username}', [ProductProcessorController::class, 'labelUsedFor']);

// API v1
Route::prefix('v1')->group(function () {
    // Products
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/search', [ProductController::class, 'search']);
    Route::get('products/advanced-search', [ProductController::class, 'advancedSearch']);
    Route::get('products/image-search', [ProductController::class, 'imageSearch']);
    Route::post('products/image-upload-search', [ProductController::class, 'imageUploadSearch']);
    Route::get('products/{id}', [ProductController::class, 'show']);

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);

    // Attributes
    Route::get('attributes', [AttributeController::class, 'index']);
    Route::get('attributes/{id}', [AttributeController::class, 'show']);
});

// Admin Authentication
Route::prefix('admin')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);

        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);

        // Instagram Profiles
        Route::apiResource('instagram-profiles', InstagramProfileController::class);

        // Structure Outputs
        Route::apiResource('structure-outputs', StructureOutputController::class);
        Route::get('product-attributes', [StructureOutputController::class, 'productAttributes']);

        // Structure Output Groups
        Route::apiResource('structure-output-groups', StructureOutputGroupController::class);

        // Product Attributes
        Route::get('attributes/with-outputs', [AdminAttributeController::class, 'withOutputs']);
        Route::apiResource('attributes', AdminAttributeController::class);

        // Product Attribute Values
        Route::get('attributes/{attribute}/values', [AttributeValueController::class, 'index']);
        Route::post('attributes/{attribute}/values', [AttributeValueController::class, 'store']);
        Route::put('attributes/{attribute}/values/{value}', [AttributeValueController::class, 'update']);
        Route::delete('attributes/{attribute}/values/{value}', [AttributeValueController::class, 'destroy']);

        // Scrape & Processing Runs
        Route::get('scrape-runs', [RunController::class, 'scrapeRuns']);
        Route::get('processing-runs', [RunController::class, 'processingRuns']);
        Route::get('profiles/{profile}/runs', [RunController::class, 'profileRuns']);
        Route::post('profiles/{profile}/scrape', [RunController::class, 'triggerScrape']);
        Route::post('profiles/{profile}/process', [RunController::class, 'triggerProcessing']);
        Route::post('profiles/{profile}/label', [RunController::class, 'triggerLabeling']);
        Route::post('profiles/{profile}/full-pipeline', [RunController::class, 'triggerFullPipeline']);
        Route::get('profiles/{profile}/labeling-status', [RunController::class, 'labelingStatus']);
        Route::patch('profiles/{profile}/settings', [RunController::class, 'updateProfileSettings']);
    });
});
