<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Doctor;
use App\Models\InventoryMovement;
use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\SaleReturn;
use App\Models\Setting;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Maps audit_logs.subject_type + subject_id to an in-app URL where one exists.
 */
final class AuditSubjectLink
{
    /**
     * Absolute URL to manage/view the subject, or null when unknown or not linkable.
     */
    public static function url(?string $subjectType, ?int $subjectId, ?User $viewer = null): ?string
    {
        if ($subjectType === null || $subjectId === null || $subjectId < 1) {
            return null;
        }

        $viewer = $viewer ?? auth()->user();
        $pair = self::routeAndParameters($subjectType, $subjectId, $viewer);
        if ($pair === null) {
            return null;
        }

        [$routeName, $parameters] = $pair;

        if (! Route::has($routeName)) {
            return null;
        }

        try {
            return url(route($routeName, $parameters, false));
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function label(?string $subjectType, ?int $subjectId): string
    {
        if ($subjectType === null || $subjectId === null) {
            return '—';
        }

        return class_basename($subjectType).' #'.$subjectId;
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}|null
     */
    private static function routeAndParameters(string $subjectType, int $subjectId, ?User $viewer): ?array
    {
        return match ($subjectType) {
            Product::class => ['products.edit', ['product' => $subjectId]],
            Order::class => ['orders.show', ['order' => $subjectId]],
            User::class => ['users.edit', ['user' => $subjectId]],
            Site::class => ['sites.edit', ['site' => $subjectId]],
            Manufacturer::class => ['manufacturers.edit', ['manufacturer' => $subjectId]],
            Supplier::class => ['suppliers.edit', ['supplier' => $subjectId]],
            Doctor::class => ['pharmacy.doctors.edit', ['doctor' => $subjectId]],
            Prescription::class => ['pharmacy.prescriptions.show', ['prescription' => $subjectId]],
            Customer::class => ['customers.edit', ['customer' => $subjectId]],
            SupplierInvoice::class => ['supplier-invoices.edit', ['supplier_invoice' => $subjectId]],
            Order_detail::class => ['report.show', ['report' => $subjectId]],
            StockReceipt::class => ['inventory.receipts.show', ['stock_receipt' => $subjectId]],
            Transaction::class => ['transactions.show', ['transaction' => $subjectId]],
            SaleReturn::class => ['sales.returns.index', []],
            StockTransfer::class, InventoryMovement::class => ['inventory.logs', []],
            Setting::class => ['settings.localization', []],
            Company::class => $viewer && $viewer->isSuperAdmin()
                ? ['super-admin.companies.edit', ['company' => $subjectId]]
                : null,
            default => null,
        };
    }
}
