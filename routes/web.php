<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockReceiptController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\CrossSiteDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\DomainPlaceholderController;
use App\Http\Controllers\SuperAdmin\SubscriptionPackageController;
use App\Http\Controllers\SuperAdmin\SubscriptionPaymentController;
use App\Http\Controllers\SuperAdmin\TenantCompanyController;
use App\Http\Controllers\SuperAdmin\TenantSubscriptionController;
use App\Http\Controllers\Tenant\RoleController as TenantRoleController;
use App\Http\Controllers\DirectMessageController;
use App\Http\Controllers\AnnouncementController;

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



Route::get('showuser', [UserController::class, 'manageUsers'])->name('users.manage');
Route::get('pharmacy/showuser', [UserController::class, 'manageUsers'])->name('pharmacy.showuser');
Route::get('pharmacy/employees/grid', [UserController::class, 'employeesGrid'])->name('users.employees.grid');
Route::get('addproduct', [PagesController::class, 'addproduct']);
Route::get('grid', [PagesController::class, 'grid']);


Auth::routes();

Route::get('dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
Route::get('dashboard/export', [DashboardController::class, 'exportCsv'])->middleware(['auth', 'can:reports.export'])->name('dashboard.export');
Route::get('dashboard/cross-site', [CrossSiteDashboardController::class, 'index'])->name('dashboard.cross-site');

Route::middleware(['auth', 'tenant.communications'])->group(function () {
    /*
     * Inbox must be registered as GET messages/ (index) before GET messages/{user}, otherwise
     * some stacks can treat the second segment incorrectly. Numeric-only {user} avoids clashes
     * with paths like messages/mark-all-read.
     */
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [DirectMessageController::class, 'index'])->name('index');
        Route::post('mark-all-read', [DirectMessageController::class, 'markAllRead'])->name('mark-all-read');
        Route::get('{user}', [DirectMessageController::class, 'show'])->name('show')->whereNumber('user');
        Route::post('/', [DirectMessageController::class, 'store'])->name('store');
    });

    Route::get('notifications', [AnnouncementController::class, 'index'])->name('notifications.index');
    Route::get('notifications/create', [AnnouncementController::class, 'create'])->name('notifications.create');
    Route::post('notifications', [AnnouncementController::class, 'store'])->name('notifications.store');
    Route::post('notifications/mark-all-read', [AnnouncementController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::get('notifications/{announcement}', [AnnouncementController::class, 'show'])->name('notifications.show');
});

Route::get('orders/customers/lookup', [OrderController::class, 'lookupCustomer'])->name('orders.customers.lookup');
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
Route::post('inventory/stock-transfer', [InventoryController::class, 'storeStockTransfer'])->name('inventory.stock-transfer.store');

Route::post('sites/switch', [SiteController::class, 'switch'])->name('sites.switch');
Route::resource('sites', SiteController::class)->except(['show']);
Route::get('inventory/catalog/categories', [InventoryController::class, 'catalogCategories'])->name('inventory.catalog.categories');
Route::get('inventory/catalog/brands', [InventoryController::class, 'catalogBrands'])->name('inventory.catalog.brands');
Route::get('inventory/catalog/units', [InventoryController::class, 'catalogUnits'])->name('inventory.catalog.units');
Route::get('inventory/expiry-tracking', [InventoryController::class, 'expiryTracking'])->name('inventory.expiry-tracking');
Route::middleware(['can:inventory.view'])->group(function () {
    Route::get('inventory/batches/export', [InventoryController::class, 'batchExport'])->name('inventory.batches.export');
    Route::get('inventory/batches', [InventoryController::class, 'batchManagement'])->name('inventory.batches');
});

Route::get('pharmacy/prescriptions', [PrescriptionController::class, 'index'])->name('pharmacy.prescriptions');
Route::post('pharmacy/prescriptions', [PrescriptionController::class, 'store'])->name('pharmacy.prescriptions.store');
Route::patch('pharmacy/prescriptions/{prescription}', [PrescriptionController::class, 'update'])->name('pharmacy.prescriptions.update');

Route::resource('products', ProductController::class);
Route::resource('manufacturers', ManufacturerController::class)->except(['show']);
Route::resource('suppliers', SupplierController::class)->except(['show']);
Route::resource('users', UserController::class);
Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);


Route::resource('transactions', TransactionController::class);

Route::middleware(['auth', 'superadmin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('domain', DomainPlaceholderController::class)->name('domain');
    Route::resource('companies', TenantCompanyController::class)->except(['show']);
    Route::resource('packages', SubscriptionPackageController::class)->except(['show']);
    Route::get('subscriptions', [TenantSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/create', [TenantSubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('subscriptions', [TenantSubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('purchase-transactions', [SubscriptionPaymentController::class, 'index'])->name('payments.index');
    Route::get('purchase-transactions/create', [SubscriptionPaymentController::class, 'create'])->name('payments.create');
    Route::post('purchase-transactions', [SubscriptionPaymentController::class, 'store'])->name('payments.store');
});
Route::middleware(['auth', 'can:reports.view'])->group(function () {
    Route::get('reports/periodic', [App\Http\Controllers\ReportController::class, 'periodic'])->name('reports.periodic');
    Route::get('reports/periodicprint', [App\Http\Controllers\ReportController::class, 'periodicprint'])->name('reports.periodicprint');
    Route::get('reports/sales/print', [App\Http\Controllers\ReportController::class, 'salesPrint'])->name('reports.sales.print');
    Route::get('reports/sales', [App\Http\Controllers\ReportController::class, 'sales'])->name('reports.sales');
});

Route::middleware(['auth', 'can:reports.export'])->group(function () {
    Route::get('reports/sales/export', [App\Http\Controllers\ReportController::class, 'salesExport'])->name('reports.sales.export');
});

Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::get('settings/localization', [SettingsController::class, 'localization'])->name('settings.localization');
Route::put('settings/localization', [SettingsController::class, 'saveLocalization'])->name('settings.localization.update');
Route::get('settings/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
Route::put('settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');

Route::middleware(['auth', 'tenant.roles'])->resource('roles', TenantRoleController::class)->except(['show']);