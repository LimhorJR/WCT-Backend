<?php

use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/admin/login', [AuthController::class, 'loginAdmin']);

// get all user
Route::get('/users' , [AuthController::class, 'showUser']);
// user login
Route::post('/login', [AuthController::class, 'loginUser']);
// register new user
Route::post('/register', [AuthController::class, 'register']);
// google auth login
Route::get('auth' , [AuthController::class , 'redirectToAuth']);
Route::get('auth/callback' , [AuthController::class , 'handleAuthCallback']);

//Approve Order
Route::get('/order', [AdminOrderController::class, 'viewOrder']);
Route::get('/total-pending-approve', [AdminOrderController::class, 'getOrderStatistics']);
// Product Crud
Route::get('/products', [ProductController::class , 'index']);
Route::get('/products/{id}/find' , [ProductController::class , 'show']);

// Category Crud
Route::get('/categories', [CategoryController::class , 'index']);
Route::get('/categories/{id}/find', [CategoryController::class , 'show']);
Route::get('/query-categories' , [CategoryController::class , 'queryCategories']);
Route::get('/query-multiple-categories' , [CategoryController::class , 'queryMultipleCategories']);

// Brand Crud
Route::get('/brand', [BrandController::class , 'index']);
Route::get('/brand/{id}/find', [BrandController::class, 'show']);
Route::get('/query-multiple-brands', [BrandController::class, 'queryMultipleBrand']);



// View Cart Order

// Add To Cart
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/add-to-cart', [CartController::class, 'addToCart']);
// });
// Route::post('/add-to-cart', [CartController::class, 'addToCart']);



// Route::group(['middleware' => 'auth:api'], function(){
//     Route::group(['middleware' => 'role:admin'], function(){
//         Route::post('/add-to-cart', [CartController::class, 'addToCart']);
//     });
// });


// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/profile', [AuthController::class, 'profile']);

    Route::middleware('role:customer')->group(function () {
        Route::post('/add-to-cart', [CartController::class, 'addToCart']);
        Route::get('/cart', [CartController::class, 'viewCart']);
        Route::post('/cart/update-quantity', [CartController::class, 'updateQuantityByProductId']);
        Route::delete('/cart/{id}/remove' , [CartController::class , 'removeCart']);
        Route::get('/get-order-history', [OrderController::class , 'getAllOrders']);
        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::get('/user-notifications', [NotificationController::class, 'getUserNotifications']);
    });
    
    Route::middleware('role:admin')->group(function () {

        // Approve Order
        Route::post('/order/{id}/approve', [AdminOrderController::class, 'approveOrder']);
        Route::delete('/order/{id}/delete', [AdminOrderController::class, 'deleteOrder']);
        Route::get('/admin-notifications', [NotificationController::class, 'getAdminNotifications']);

        //Product Crud
        Route::post('/products',[ProductController::class , 'store']);
        Route::put('/products/{id}/update',[ProductController::class , 'update']);
        Route::delete('/products/{id}/destory',[ProductController::class , 'destroy']);

        //Category Crud
        Route::post('/categories', [CategoryController::class , 'store']);
        Route::put('/categories/{id}/update', [CategoryController::class , 'update']);
        Route::delete('/categories/{id}/destory', [CategoryController::class , 'destroy']);

        //Brand Crud
        Route::post('/brand', [BrandController::class, 'store']);
        Route::put('/brand/{id}/update', [BrandController::class, 'update']);
        Route::delete('/brand/{id}/destroy', [BrandController::class, 'destroy']);


    });
});