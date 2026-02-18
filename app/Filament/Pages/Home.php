<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Vehicles\VehicleResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Home extends Page
{
    protected static ?string $navigationLabel = 'Home';

    protected static ?string $title = 'Home';

    protected static ?int $navigationSort = -10;

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
        $user = auth()->user();
        $cards = [];

        // POS — cashier or super_admin
        if ($user->hasRole('cashier') || $user->hasRole('super_admin')) {
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

        // Clock In — all authenticated users
        $cards[] = [
            'title'       => 'My Attendance',
            'description' => 'Clock in, clock out and view your attendance records',
            'icon'        => 'heroicon-o-finger-print',
            'url'         => MyAttendance::getUrl(),
            'color'       => '#10b981',
            'bg'          => '#ecfdf5',
            'border'      => '#a7f3d0',
        ];

        // Quotations
        if ($user->can('view_any_quotation')) {
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

        // Sales
        if ($user->can('view_any_sale')) {
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

        // Customers
        if ($user->can('view_any_customer')) {
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

        // Products
        if ($user->can('view_any_product')) {
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

        // Deliveries
        if ($user->can('view_any_delivery')) {
            $cards[] = [
                'title'       => 'Deliveries',
                'description' => 'Track and manage delivery orders and drivers',
                'icon'        => 'heroicon-o-truck',
                'url'         => DeliveryResource::getUrl(),
                'color'       => '#eab308',
                'bg'          => '#fefce8',
                'border'      => '#fef08a',
            ];
        }

        // Purchases
        if ($user->can('view_any_purchase')) {
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

        // Payroll
        if ($user->can('view_any_payroll')) {
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

        // Attendance (admin / HR view)
        if ($user->can('view_any_attendance')) {
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

        // Leave Requests
        if ($user->can('view_any_leave_request')) {
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

        // Expenses
        if ($user->can('view_any_expense')) {
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

        // Cash Register
        if ($user->can('view_any_cash_register_session')) {
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

        // Vehicles
        if ($user->can('view_any_vehicle')) {
            $cards[] = [
                'title'       => 'Vehicles',
                'description' => 'Manage fleet vehicles and maintenance records',
                'icon'        => 'heroicon-o-wrench-screwdriver',
                'url'         => VehicleResource::getUrl(),
                'color'       => '#84cc16',
                'bg'          => '#f7fee7',
                'border'      => '#d9f99d',
            ];
        }

        // User Management (admin only)
        if ($user->can('view_any_user')) {
            $cards[] = [
                'title'       => 'User Management',
                'description' => 'Manage system users, roles and permissions',
                'icon'        => 'heroicon-o-user-group',
                'url'         => UserResource::getUrl(),
                'color'       => '#475569',
                'bg'          => '#f1f5f9',
                'border'      => '#cbd5e1',
            ];
        }

        return $cards;
    }
}
