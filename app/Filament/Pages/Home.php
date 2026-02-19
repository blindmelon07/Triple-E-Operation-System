<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Filament\Resources\Drivers\DriverResource;
use App\Filament\Resources\EmployeeCompensations\EmployeeCompensationResource;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\GovernmentContributions\GovernmentContributionResource;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Filament\Resources\LeaveTypes\LeaveTypeResource;
use App\Filament\Resources\MaintenanceRecords\MaintenanceRecordResource;
use App\Filament\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Filament\Resources\MaintenanceTypes\MaintenanceTypeResource;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Vehicles\VehicleResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Home extends Page
{
    protected static ?string $title = 'Home';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.home';

    public static function getNavigationIcon(): BackedEnum|Htmlable|string|null
    {
        return Heroicon::OutlinedHome;
    }

    public function getGreeting(): string
    {
        $hour = now()->timezone('Asia/Manila')->hour;

        if ($hour < 12) {
            return 'Good morning';
        } elseif ($hour < 18) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }

    public function getCards(): array
    {
        $user      = auth()->user();
        $isAdmin   = $user->hasRole('super_admin');
        $cards     = [];

        // ── POS ──────────────────────────────────────────────────────────────
        if ($isAdmin || $user->hasRole('cashier')) {
            $cards[] = [
                'title'       => 'Point of Sale',
                'description' => 'Process sales and transactions at the counter',
                'icon'        => 'heroicon-o-shopping-cart',
                'url'         => '/pos',
                'color'       => '#3b82f6',
                'bg'          => '#eff6ff',
                'border'      => '#bfdbfe',
            ];
        }

        // ── ATTENDANCE (self) ─────────────────────────────────────────────────
        $cards[] = [
            'title'       => 'My Attendance',
            'description' => 'Clock in, clock out and view your attendance records',
            'icon'        => 'heroicon-o-finger-print',
            'url'         => MyAttendance::getUrl(),
            'color'       => '#10b981',
            'bg'          => '#ecfdf5',
            'border'      => '#a7f3d0',
        ];

        // ── SALES & CUSTOMERS ─────────────────────────────────────────────────
        if ($isAdmin || $user->can('ViewAny:Quotation')) {
            $cards[] = [
                'title'       => 'Quotations',
                'description' => 'Create and manage customer price quotations',
                'icon'        => 'heroicon-o-document-text',
                'url'         => QuotationResource::getUrl(),
                'color'       => '#8b5cf6',
                'bg'          => '#f5f3ff',
                'border'      => '#ddd6fe',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:Sale')) {
            $cards[] = [
                'title'       => 'Sales',
                'description' => 'View and manage completed sales transactions',
                'icon'        => 'heroicon-o-banknotes',
                'url'         => SaleResource::getUrl(),
                'color'       => '#f59e0b',
                'bg'          => '#fffbeb',
                'border'      => '#fde68a',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:Customer')) {
            $cards[] = [
                'title'       => 'Customers',
                'description' => 'Manage customer profiles and contact details',
                'icon'        => 'heroicon-o-users',
                'url'         => CustomerResource::getUrl(),
                'color'       => '#06b6d4',
                'bg'          => '#ecfeff',
                'border'      => '#a5f3fc',
            ];
        }

        // ── INVENTORY ─────────────────────────────────────────────────────────
        if ($isAdmin || $user->can('ViewAny:Product')) {
            $cards[] = [
                'title'       => 'Products',
                'description' => 'Manage product catalogue, pricing and stock',
                'icon'        => 'heroicon-o-archive-box',
                'url'         => ProductResource::getUrl(),
                'color'       => '#6366f1',
                'bg'          => '#eef2ff',
                'border'      => '#c7d2fe',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:Category')) {
            $cards[] = [
                'title'       => 'Categories',
                'description' => 'Manage product categories and classification',
                'icon'        => 'heroicon-o-tag',
                'url'         => CategoryResource::getUrl(),
                'color'       => '#a78bfa',
                'bg'          => '#f5f3ff',
                'border'      => '#ede9fe',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:Supplier')) {
            $cards[] = [
                'title'       => 'Suppliers',
                'description' => 'Manage supplier contacts and information',
                'icon'        => 'heroicon-o-building-office',
                'url'         => SupplierResource::getUrl(),
                'color'       => '#059669',
                'bg'          => '#ecfdf5',
                'border'      => '#a7f3d0',
            ];
        }

        // ── PURCHASING ────────────────────────────────────────────────────────
        if ($isAdmin || $user->can('ViewAny:Purchase')) {
            $cards[] = [
                'title'       => 'Purchases',
                'description' => 'Create and track purchase orders from suppliers',
                'icon'        => 'heroicon-o-shopping-bag',
                'url'         => PurchaseResource::getUrl(),
                'color'       => '#ef4444',
                'bg'          => '#fef2f2',
                'border'      => '#fecaca',
            ];
        }

        // ── DELIVERIES ────────────────────────────────────────────────────────
        if ($isAdmin || $user->can('ViewAny:Delivery')) {
            $cards[] = [
                'title'       => 'Deliveries',
                'description' => 'Track and manage delivery orders and status',
                'icon'        => 'heroicon-o-truck',
                'url'         => DeliveryResource::getUrl(),
                'color'       => '#eab308',
                'bg'          => '#fefce8',
                'border'      => '#fef08a',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:Driver')) {
            $cards[] = [
                'title'       => 'Drivers',
                'description' => 'Manage drivers and their assignments',
                'icon'        => 'heroicon-o-identification',
                'url'         => DriverResource::getUrl(),
                'color'       => '#d97706',
                'bg'          => '#fffbeb',
                'border'      => '#fde68a',
            ];
        }

        // ── FLEET & MAINTENANCE ───────────────────────────────────────────────
        if ($isAdmin || $user->can('ViewAny:Vehicle')) {
            $cards[] = [
                'title'       => 'Vehicles',
                'description' => 'Manage fleet vehicles and records',
                'icon'        => 'heroicon-o-wrench-screwdriver',
                'url'         => VehicleResource::getUrl(),
                'color'       => '#84cc16',
                'bg'          => '#f7fee7',
                'border'      => '#d9f99d',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:MaintenanceRecord')) {
            $cards[] = [
                'title'       => 'Maintenance Records',
                'description' => 'View completed vehicle maintenance history',
                'icon'        => 'heroicon-o-clipboard-document-list',
                'url'         => MaintenanceRecordResource::getUrl(),
                'color'       => '#65a30d',
                'bg'          => '#f7fee7',
                'border'      => '#bbf7d0',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:MaintenanceRequest')) {
            $cards[] = [
                'title'       => 'Maintenance Requests',
                'description' => 'Submit and track vehicle maintenance requests',
                'icon'        => 'heroicon-o-wrench',
                'url'         => MaintenanceRequestResource::getUrl(),
                'color'       => '#f97316',
                'bg'          => '#fff7ed',
                'border'      => '#fed7aa',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:MaintenanceType')) {
            $cards[] = [
                'title'       => 'Maintenance Types',
                'description' => 'Configure maintenance categories and types',
                'icon'        => 'heroicon-o-cog-6-tooth',
                'url'         => MaintenanceTypeResource::getUrl(),
                'color'       => '#78716c',
                'bg'          => '#fafaf9',
                'border'      => '#e7e5e4',
            ];
        }

        // ── HR & PAYROLL ──────────────────────────────────────────────────────
        if ($isAdmin || $user->can('ViewAny:Payroll')) {
            $cards[] = [
                'title'       => 'Payroll',
                'description' => 'Process and manage employee payroll runs',
                'icon'        => 'heroicon-o-currency-dollar',
                'url'         => PayrollResource::getUrl(),
                'color'       => '#ec4899',
                'bg'          => '#fdf2f8',
                'border'      => '#fbcfe8',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:Attendance')) {
            $cards[] = [
                'title'       => 'Attendance',
                'description' => 'View and manage all employee attendance logs',
                'icon'        => 'heroicon-o-clock',
                'url'         => AttendanceResource::getUrl(),
                'color'       => '#14b8a6',
                'bg'          => '#f0fdfa',
                'border'      => '#99f6e4',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:LeaveRequest')) {
            $cards[] = [
                'title'       => 'Leave Requests',
                'description' => 'Review and approve employee leave applications',
                'icon'        => 'heroicon-o-calendar-days',
                'url'         => LeaveRequestResource::getUrl(),
                'color'       => '#f97316',
                'bg'          => '#fff7ed',
                'border'      => '#fed7aa',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:LeaveType')) {
            $cards[] = [
                'title'       => 'Leave Types',
                'description' => 'Configure available leave types and entitlements',
                'icon'        => 'heroicon-o-calendar',
                'url'         => LeaveTypeResource::getUrl(),
                'color'       => '#c084fc',
                'bg'          => '#faf5ff',
                'border'      => '#e9d5ff',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:EmployeeCompensation')) {
            $cards[] = [
                'title'       => 'Compensations',
                'description' => 'Manage employee salary and compensation setup',
                'icon'        => 'heroicon-o-banknotes',
                'url'         => EmployeeCompensationResource::getUrl(),
                'color'       => '#db2777',
                'bg'          => '#fdf2f8',
                'border'      => '#f9a8d4',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:GovernmentContribution')) {
            $cards[] = [
                'title'       => 'Gov. Contributions',
                'description' => 'Manage SSS, PhilHealth and Pag-IBIG contributions',
                'icon'        => 'heroicon-o-building-library',
                'url'         => GovernmentContributionResource::getUrl(),
                'color'       => '#1d4ed8',
                'bg'          => '#eff6ff',
                'border'      => '#bfdbfe',
            ];
        }

        // ── EXPENSES & FINANCE ────────────────────────────────────────────────
        if ($isAdmin || $user->can('ViewAny:Expense')) {
            $cards[] = [
                'title'       => 'Expenses',
                'description' => 'Record and categorize business expenses',
                'icon'        => 'heroicon-o-receipt-percent',
                'url'         => ExpenseResource::getUrl(),
                'color'       => '#64748b',
                'bg'          => '#f8fafc',
                'border'      => '#e2e8f0',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:ExpenseCategory')) {
            $cards[] = [
                'title'       => 'Expense Categories',
                'description' => 'Configure expense types and categories',
                'icon'        => 'heroicon-o-folder-open',
                'url'         => ExpenseCategoryResource::getUrl(),
                'color'       => '#94a3b8',
                'bg'          => '#f8fafc',
                'border'      => '#e2e8f0',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:CashRegisterSession')) {
            $cards[] = [
                'title'       => 'Cash Register',
                'description' => 'View open and closed cash register sessions',
                'icon'        => 'heroicon-o-calculator',
                'url'         => CashRegisterSessionResource::getUrl(),
                'color'       => '#0ea5e9',
                'bg'          => '#f0f9ff',
                'border'      => '#bae6fd',
            ];
        }

        // ── REPORTS ───────────────────────────────────────────────────────────
        if ($isAdmin || $user->can('View:SalesReport')) {
            $cards[] = [
                'title'       => 'Sales Report',
                'description' => 'View detailed sales analytics and summaries',
                'icon'        => 'heroicon-o-chart-bar',
                'url'         => SalesReport::getUrl(),
                'color'       => '#0891b2',
                'bg'          => '#ecfeff',
                'border'      => '#a5f3fc',
            ];
        }

        if ($isAdmin || $user->can('View:FinancialDashboard')) {
            $cards[] = [
                'title'       => 'Financial Dashboard',
                'description' => 'Overview of revenue, expenses and profit',
                'icon'        => 'heroicon-o-presentation-chart-line',
                'url'         => FinancialDashboard::getUrl(),
                'color'       => '#16a34a',
                'bg'          => '#f0fdf4',
                'border'      => '#bbf7d0',
            ];
        }

        if ($isAdmin || $user->can('View:ProfitLossReport')) {
            $cards[] = [
                'title'       => 'Profit & Loss',
                'description' => 'Profit and loss statement for any period',
                'icon'        => 'heroicon-o-chart-pie',
                'url'         => ProfitLossReport::getUrl(),
                'color'       => '#15803d',
                'bg'          => '#f0fdf4',
                'border'      => '#86efac',
            ];
        }

        if ($isAdmin || $user->can('View:InventoryReport')) {
            $cards[] = [
                'title'       => 'Inventory Report',
                'description' => 'Stock levels, movements and valuation report',
                'icon'        => 'heroicon-o-cube',
                'url'         => InventoryReport::getUrl(),
                'color'       => '#7c3aed',
                'bg'          => '#f5f3ff',
                'border'      => '#ddd6fe',
            ];
        }

        if ($isAdmin || $user->can('View:ProductsReport')) {
            $cards[] = [
                'title'       => 'Products Report',
                'description' => 'Product performance and sales breakdown',
                'icon'        => 'heroicon-o-clipboard-document-check',
                'url'         => ProductsReport::getUrl(),
                'color'       => '#6d28d9',
                'bg'          => '#f5f3ff',
                'border'      => '#ede9fe',
            ];
        }

        if ($isAdmin || $user->can('View:AgingReport')) {
            $cards[] = [
                'title'       => 'Aging Report',
                'description' => 'Outstanding receivables by aging bucket',
                'icon'        => 'heroicon-o-exclamation-triangle',
                'url'         => AgingReport::getUrl(),
                'color'       => '#b45309',
                'bg'          => '#fffbeb',
                'border'      => '#fde68a',
            ];
        }

        if ($isAdmin || $user->can('View:DriverKpiDashboard')) {
            $cards[] = [
                'title'       => 'Driver KPI',
                'description' => 'Driver performance metrics and KPI dashboard',
                'icon'        => 'heroicon-o-trophy',
                'url'         => DriverKpiDashboard::getUrl(),
                'color'       => '#ea580c',
                'bg'          => '#fff7ed',
                'border'      => '#fdba74',
            ];
        }

        // ── ADMINISTRATION ────────────────────────────────────────────────────
        if ($isAdmin || $user->can('ViewAny:Role')) {
            $cards[] = [
                'title'       => 'Roles & Permissions',
                'description' => 'Manage roles and their permission assignments',
                'icon'        => 'heroicon-o-shield-check',
                'url'         => RoleResource::getUrl(),
                'color'       => '#334155',
                'bg'          => '#f8fafc',
                'border'      => '#cbd5e1',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:User')) {
            $cards[] = [
                'title'       => 'User Management',
                'description' => 'Manage system users, roles and access',
                'icon'        => 'heroicon-o-user-group',
                'url'         => UserResource::getUrl(),
                'color'       => '#475569',
                'bg'          => '#f1f5f9',
                'border'      => '#cbd5e1',
            ];
        }

        if ($isAdmin || $user->can('ViewAny:AuditLog')) {
            $cards[] = [
                'title'       => 'Audit Logs',
                'description' => 'Track all system changes and user activity',
                'icon'        => 'heroicon-o-magnifying-glass',
                'url'         => AuditLogResource::getUrl(),
                'color'       => '#6b7280',
                'bg'          => '#f9fafb',
                'border'      => '#e5e7eb',
            ];
        }

        return $cards;
    }
}
