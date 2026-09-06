<?php

namespace App\Support\ReportBuilder;

use App\Models\Attendance;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\SaleItem;

/**
 * Whitelist of what the Custom Report Builder (app/Filament/Pages/CustomReportBuilder.php)
 * is allowed to query and show. This is deliberately a curated set of the
 * business-data modules — not literally every table in the app — so a report
 * can never surface something like user credentials, roles, or raw audit-log
 * payloads. Adding a module a user actually needs is just a new array entry
 * here; nothing elsewhere needs to change.
 *
 * Each module entry:
 *   - label:        shown in the module picker.
 *   - model:        the Eloquent model class to query.
 *   - eager:        relations to eager-load so dotted column keys resolve.
 *   - date_column:  column (dot notation for a relation) used for the date-range
 *                    filter; null if the module has nothing sensible to range on.
 *   - columns:      column key (dot notation supported) => display label. Order
 *                    here is the order columns render in, in every column list
 *                    the picker and every export produces.
 *   - filters:      key => ['label' => ..., 'column' => dotted column, 'options' => array|Closure]
 *                    simple equality filters, rendered as a <select>.
 */
class ReportModules
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'sales' => [
                'label' => 'Sales',
                'model' => Sale::class,
                'eager' => ['customer'],
                'date_column' => 'date',
                'columns' => [
                    'id' => 'Sale ID',
                    'date' => 'Date',
                    'customer.name' => 'Customer',
                    'reference_number' => 'Reference #',
                    'payment_method' => 'Payment Method',
                    'payment_status' => 'Payment Status',
                    'total' => 'Total',
                    'delivery_fee' => 'Delivery Fee',
                    'amount_paid' => 'Amount Paid',
                    'due_date' => 'Due Date',
                    'is_voided' => 'Voided',
                ],
                'filters' => [
                    'payment_status' => [
                        'label' => 'Payment Status',
                        'column' => 'payment_status',
                        'options' => ['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid'],
                    ],
                    'payment_method' => [
                        'label' => 'Payment Method',
                        'column' => 'payment_method',
                        'options' => [
                            'cash' => 'Cash', 'gcash' => 'GCash', 'maya' => 'Maya',
                            'bank_transfer' => 'Bank Transfer', 'check' => 'Check',
                            'credit_card' => 'Credit Card', 'charge' => 'Charge',
                        ],
                    ],
                    'is_voided' => [
                        'label' => 'Voided?',
                        'column' => 'is_voided',
                        'options' => ['0' => 'No', '1' => 'Yes'],
                    ],
                ],
            ],

            'sale_items' => [
                'label' => 'Sale Items',
                'model' => SaleItem::class,
                'eager' => ['sale.customer', 'product'],
                'date_column' => 'sale.date',
                'columns' => [
                    'id' => 'Item ID',
                    'sale_id' => 'Sale ID',
                    'sale.date' => 'Sale Date',
                    'sale.customer.name' => 'Customer',
                    'product.name' => 'Product',
                    'product_description' => 'Description',
                    'unit' => 'Unit',
                    'quantity' => 'Quantity',
                    'unit_price' => 'Unit Price',
                    'discount_amount' => 'Discount',
                    'price' => 'Line Total',
                    'is_voided' => 'Voided',
                ],
                'filters' => [
                    'is_voided' => [
                        'label' => 'Voided?',
                        'column' => 'is_voided',
                        'options' => ['0' => 'No', '1' => 'Yes'],
                    ],
                ],
            ],

            'expenses' => [
                'label' => 'Expenses',
                'model' => Expense::class,
                'eager' => ['category', 'user'],
                'date_column' => 'expense_date',
                'columns' => [
                    'id' => 'Expense ID',
                    'expense_date' => 'Date',
                    'reference_number' => 'Reference #',
                    'category.name' => 'Category',
                    'amount' => 'Amount',
                    'payment_method' => 'Payment Method',
                    'payee' => 'Payee',
                    'description' => 'Description',
                    'status' => 'Status',
                    'user.name' => 'Recorded By',
                ],
                'filters' => [
                    'status' => [
                        'label' => 'Status',
                        'column' => 'status',
                        'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'],
                    ],
                    'expense_category_id' => [
                        'label' => 'Category',
                        'column' => 'expense_category_id',
                        'options' => fn () => ExpenseCategory::orderBy('name')->pluck('name', 'id')->all(),
                    ],
                ],
            ],

            'products' => [
                'label' => 'Products & Inventory',
                'model' => Product::class,
                'eager' => ['category', 'supplier', 'inventory'],
                'date_column' => 'created_at',
                'columns' => [
                    'id' => 'Product ID',
                    'name' => 'Name',
                    'category.name' => 'Category',
                    'supplier.name' => 'Supplier',
                    'price' => 'Price',
                    'cost_price' => 'Cost Price',
                    'unit' => 'Unit',
                    'inventory.quantity' => 'Stock on Hand',
                ],
                'filters' => [
                    'category_id' => [
                        'label' => 'Category',
                        'column' => 'category_id',
                        'options' => fn () => \App\Models\Category::orderBy('name')->pluck('name', 'id')->all(),
                    ],
                ],
            ],

            'customers' => [
                'label' => 'Customers',
                'model' => Customer::class,
                'eager' => [],
                'date_column' => 'created_at',
                'columns' => [
                    'id' => 'Customer ID',
                    'name' => 'Name',
                    'company' => 'Company',
                    'contact_person' => 'Contact Person',
                    'email' => 'Email',
                    'phone' => 'Phone',
                    'address' => 'Address',
                    'payment_term_days' => 'Payment Terms (days)',
                    'created_at' => 'Date Added',
                ],
                'filters' => [],
            ],

            'purchases' => [
                'label' => 'Purchases',
                'model' => Purchase::class,
                'eager' => ['supplier'],
                'date_column' => 'date',
                'columns' => [
                    'id' => 'Purchase ID',
                    'date' => 'Date',
                    'supplier.name' => 'Supplier',
                    'total' => 'Total',
                    'payment_status' => 'Payment Status',
                    'amount_paid' => 'Amount Paid',
                    'due_date' => 'Due Date',
                    'paid_date' => 'Paid Date',
                ],
                'filters' => [
                    'payment_status' => [
                        'label' => 'Payment Status',
                        'column' => 'payment_status',
                        'options' => ['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid'],
                    ],
                ],
            ],

            'deliveries' => [
                'label' => 'Deliveries',
                'model' => Delivery::class,
                'eager' => ['sale.customer', 'driver'],
                'date_column' => 'created_at',
                'columns' => [
                    'id' => 'Delivery ID',
                    'sale_id' => 'Sale ID',
                    'sale.customer.name' => 'Customer',
                    'driver.name' => 'Driver',
                    'status' => 'Status',
                    'delivery_address' => 'Address',
                    'assigned_at' => 'Assigned At',
                    'picked_up_at' => 'Picked Up At',
                    'delivered_at' => 'Delivered At',
                    'distance_km' => 'Distance (km)',
                    'rating' => 'Rating',
                ],
                'filters' => [
                    'status' => [
                        'label' => 'Status',
                        'column' => 'status',
                        'options' => [
                            'pending' => 'Pending', 'assigned' => 'Assigned', 'picked_up' => 'Picked Up',
                            'in_transit' => 'In Transit', 'delivered' => 'Delivered',
                            'failed' => 'Failed', 'returned' => 'Returned',
                        ],
                    ],
                ],
            ],

            'quotations' => [
                'label' => 'Quotations',
                'model' => Quotation::class,
                'eager' => ['customer'],
                'date_column' => 'date',
                'columns' => [
                    'id' => 'Quotation ID',
                    'quotation_number' => 'Quotation #',
                    'date' => 'Date',
                    'customer.name' => 'Customer',
                    'valid_until' => 'Valid Until',
                    'total' => 'Total',
                    'down_payment' => 'Down Payment',
                    'status' => 'Status',
                ],
                'filters' => [
                    'status' => [
                        'label' => 'Status',
                        'column' => 'status',
                        'options' => [
                            'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                            'converted_to_sale' => 'Converted to Sale', 'expired' => 'Expired',
                        ],
                    ],
                ],
            ],

            'employees' => [
                'label' => 'Employees',
                'model' => Employee::class,
                'eager' => [],
                'date_column' => 'created_at',
                'columns' => [
                    'id' => 'Employee ID',
                    'name' => 'Name',
                    'email' => 'Email',
                    'phone' => 'Phone',
                    'is_active' => 'Active',
                    'created_at' => 'Date Added',
                ],
                'filters' => [
                    'is_active' => [
                        'label' => 'Active?',
                        'column' => 'is_active',
                        'options' => ['1' => 'Active', '0' => 'Inactive'],
                    ],
                ],
            ],

            'payrolls' => [
                'label' => 'Payrolls',
                'model' => Payroll::class,
                'eager' => [],
                'date_column' => 'pay_period_start',
                'columns' => [
                    'id' => 'Payroll ID',
                    'payroll_number' => 'Payroll #',
                    'pay_period_start' => 'Period Start',
                    'pay_period_end' => 'Period End',
                    'pay_period_type' => 'Period Type',
                    'status' => 'Status',
                    'total_gross' => 'Gross',
                    'total_deductions' => 'Deductions',
                    'total_net' => 'Net',
                    'paid_at' => 'Paid At',
                ],
                'filters' => [
                    'status' => [
                        'label' => 'Status',
                        'column' => 'status',
                        'options' => ['draft' => 'Draft', 'approved' => 'Approved', 'paid' => 'Paid', 'cancelled' => 'Cancelled'],
                    ],
                ],
            ],

            'attendances' => [
                'label' => 'Attendance',
                'model' => Attendance::class,
                'eager' => ['employee'],
                'date_column' => 'date',
                'columns' => [
                    'id' => 'Attendance ID',
                    'employee.name' => 'Employee',
                    'date' => 'Date',
                    'time_in' => 'Time In',
                    'time_out' => 'Time Out',
                    'total_hours' => 'Total Hours',
                    'status' => 'Status',
                    'remarks' => 'Remarks',
                ],
                'filters' => [
                    'status' => [
                        'label' => 'Status',
                        'column' => 'status',
                        'options' => [
                            'present' => 'Present', 'absent' => 'Absent', 'late' => 'Late',
                            'half_day' => 'Half Day', 'on_leave' => 'On Leave',
                        ],
                    ],
                ],
            ],

            'cash_register_sessions' => [
                'label' => 'Cash Register Sessions',
                'model' => CashRegisterSession::class,
                'eager' => ['user'],
                'date_column' => 'opened_at',
                'columns' => [
                    'id' => 'Session ID',
                    'user.name' => 'Cashier',
                    'opening_amount' => 'Opening Amount',
                    'closing_amount' => 'Closing Amount',
                    'expected_amount' => 'Expected Amount',
                    'discrepancy' => 'Discrepancy',
                    'total_sales' => 'Total Sales',
                    'total_cash_sales' => 'Total Cash Sales',
                    'total_transactions' => 'Transactions',
                    'opened_at' => 'Opened At',
                    'closed_at' => 'Closed At',
                    'status' => 'Status',
                ],
                'filters' => [
                    'status' => [
                        'label' => 'Status',
                        'column' => 'status',
                        'options' => ['open' => 'Open', 'closed' => 'Closed'],
                    ],
                ],
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function moduleOptions(): array
    {
        return collect(static::all())->map(fn (array $def) => $def['label'])->all();
    }

    /**
     * Resolves a filter's option list, whether it's a plain array or a
     * Closure (used for options that come from another table, e.g. categories).
     *
     * @return array<int|string, string>
     */
    public static function resolveFilterOptions(array $filterDef): array
    {
        $options = $filterDef['options'] ?? [];

        return $options instanceof \Closure ? $options() : $options;
    }
}
