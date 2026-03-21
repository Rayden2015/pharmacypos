<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockReceiptController;
use App\Http\Controllers\InventoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});


Route::get('/home', [HomeController::class, 'index'])->name('home');



Route::get('showuser', [PagesController::class, 'showusers']);
Route::get('addproduct', [PagesController::class, 'addproduct']);
Route::get('grid', [PagesController::class, 'grid']);


Auth::routes();

Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

Route::resource('orders', OrderController::class);
Route::resource('report', OrderDetailController::class);
Route::get('products/{product}/inventory-history', [ProductController::class, 'inventoryHistory'])
    ->name('products.inventory-history');
Route::get('inventory/receive', [StockReceiptController::class, 'create'])->name('inventory.receive.create');
Route::post('inventory/receive', [StockReceiptController::class, 'store'])->name('inventory.receive.store');
Route::get('inventory/receipts', [StockReceiptController::class, 'index'])->name('inventory.receipts.index');
Route::get('inventory/receipts/{stock_receipt}', [StockReceiptController::class, 'show'])->name('inventory.receipts.show');

Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
Route::get('inventory/manage-stock', [InventoryController::class, 'manageStock'])->name('inventory.manage-stock');
Route::get('inventory/stock-adjustment', [InventoryController::class, 'createStockAdjustment'])->name('inventory.stock-adjustment.create');
Route::post('inventory/stock-adjustment', [InventoryController::class, 'storeStockAdjustment'])->name('inventory.stock-adjustment.store');
Route::get('inventory/stock-transfer', [InventoryController::class, 'stockTransfer'])->name('inventory.stock-transfer');
Route::get('inventory/catalog/categories', [InventoryController::class, 'catalogCategories'])->name('inventory.catalog.categories');
Route::get('inventory/catalog/brands', [InventoryController::class, 'catalogBrands'])->name('inventory.catalog.brands');
Route::get('inventory/catalog/units', [InventoryController::class, 'catalogUnits'])->name('inventory.catalog.units');

Route::resource('products', ProductController::class);
Route::resource('suppliers', SupplierController::class);
Route::resource('users', UserController::class);


Route::resource('companies', CompanyController::class);
Route::resource('transactions', TransactionController::class);
Route::get('reports/periodic',[App\Http\Controllers\ReportController::class, 'periodic'])->name('reports.periodic');
Route::get('reports/periodicprint',[App\Http\Controllers\ReportController::class, 'periodicprint'])->name('reports.periodicprint');

Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');