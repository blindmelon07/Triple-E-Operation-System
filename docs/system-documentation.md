# TOS — System Documentation

> **Stack:** Laravel 12 · PHP 8.3 · Filament v4 · Alpine.js · MySQL
> **Architecture:** Filament admin panel + standalone POS terminal
> **Access control:** Spatie Permissions + FilamentShield (role-based)

---

## Table of Contents

1. [Point of Sale (POS)](#1-point-of-sale-pos)
2. [Inventory & Sales](#2-inventory--sales)
   - 2.1 [Categories](#21-categories)
   - 2.2 [Products](#22-products)
   - 2.3 [Suppliers](#23-suppliers)
   - 2.4 [Customers](#24-customers)
   - 2.5 [Sales](#25-sales)
   - 2.6 [Purchases](#26-purchases)
   - 2.7 [Quotations](#27-quotations)
3. [Delivery Management](#3-delivery-management)
   - 3.1 [Deliveries](#31-deliveries)
   - 3.2 [Drivers](#32-drivers)
4. [Finance](#4-finance)
   - 4.1 [Expenses](#41-expenses)
   - 4.2 [Expense Categories](#42-expense-categories)
   - 4.3 [Financial Overview (Accounting Service)](#43-financial-overview-accounting-service)
5. [Fleet Management](#5-fleet-management)
   - 5.1 [Vehicles](#51-vehicles)
   - 5.2 [Service Types](#52-service-types)
   - 5.3 [Service Records](#53-service-records)
   - 5.4 [Service Requests](#54-service-requests)
6. [Attendance Management](#6-attendance-management)
   - 6.1 [Attendance](#61-attendance)
   - 6.2 [Leave Types](#62-leave-types)
   - 6.3 [Leave Requests](#63-leave-requests)
7. [Payroll](#7-payroll)
8. [Authentication & Security](#8-authentication--security)
   - 8.1 [Users](#81-users)
   - 8.2 [Roles & Permissions](#82-roles--permissions)
   - 8.3 [Audit Logs](#83-audit-logs)

---

## 1. Point of Sale (POS)

**URL:** `/pos`
**Auth required:** Yes
**View:** `resources/views/pos/index.blade.php`
**Controller:** `app/Http/Controllers/POSController.php`

The POS is a full-featured, single-page terminal built with Alpine.js. It runs in the browser without full page reloads. Sessions can remain open all day — CSRF tokens are automatically refreshed every 90 minutes to prevent expiry errors.

---

### 1.1 Cash Register

Before processing any sale, a cashier must open a register session.

| Action | Description |
|---|---|
| **Open Register** | Enter an opening cash amount. A session is created tied to the current user. |
| **Close Register** | Enter the physical cash count. The system calculates the expected amount and shows any discrepancy. |

The register tracks:
- Total sales (paid immediately — excludes credit/term sales)
- Total cash sales only
- Total number of transactions
- Expected cash vs actual closing amount (discrepancy)

> Only one open session per user at a time is allowed.

**Register Closure PDF Report**

When closing the register, a PDF report is automatically generated and downloaded in the browser. It contains:

- **Session summary cards** — Opening amount, Total sales, Cash sales, Transaction count, Expected cash, Closing amount, Discrepancy (green if over, red if short)
- **Full sales transaction table** — Time, Customer name, Items count, Payment method, Payment status, and Amount for every sale in the session
- Session notes (if entered)

The PDF filename format: `register-report-YYYY-MM-DD-session-{id}.pdf`

The report can also be re-downloaded anytime via:
```
GET /pos/register/{session}/sales-report
```

---

### 1.2 Adding Products to Cart

**Standard products** — click a product card. If the product uses a weight/volume/length unit (kg, g, L, mL, m, ft), a modal appears to enter the measured quantity before adding.

**Manual / custom items** — for items not in the product catalogue. Enter a name, unit, unit price, and quantity. These are flagged `is_manual = true` and do **not** affect inventory.

**Stock checking:**
- Products with zero stock show an out-of-stock alert (including a voice announcement via Web Speech API).
- Cart quantity is validated against available inventory in real time (current stock minus what is already in cart).

---

### 1.3 Cart Operations

| Action | Behaviour |
|---|---|
| **+/−** buttons | Increment or decrement quantity (minimum 0.01) |
| **Direct input** | Type quantity directly; validated against available stock |
| **Remove item** | Removes line from cart |
| **Clear cart** | Requires confirmation before emptying |

The cart total updates live as quantities change.

---

### 1.4 Customer Selection

- Search existing customers by name.
- **Add new customer** inline — name, phone, email, address. The new customer is auto-selected after saving.
- Leaving customer blank records the sale as a walk-in.

---

### 1.5 Payment Processing

Click **Charge** to open the payment modal.

**Payment methods:**

| Method | Code |
|---|---|
| Cash | `cash` |
| Bank Transfer | `bank_transfer` |
| Check | `check` |
| Credit Card | `credit_card` |
| GCash | `gcash` |
| Maya | `maya` |

**Payment Terms (all methods):**
Enable the *Add Payment Terms* toggle to attach a credit period to the sale. Options: 5, 10, 15, 30, or 60 days. This sets a `due_date` on the sale record.

**Cash change calculation:**
When payment method is cash, enter the amount received — the change is calculated automatically.

When **Confirm Payment** is clicked:
1. A `Sale` record is created.
2. `SaleItem` records are created for each cart line — inventory is decremented automatically for non-manual items.
3. Cash register totals are updated **only for immediately-paid sales** (no payment terms). Credit/term sales are excluded until collected.
4. If converting from an approved quotation, the quotation status changes to `converted_to_sale`.
5. A success modal appears with receipt print options (thermal or delivery receipt format).

**Payment status rules at sale creation:**

| Condition | `payment_status` | `amount_paid` | Added to register totals? |
|---|---|---|---|
| No payment terms (cash, card, GCash, etc.) | `paid` | = total | Yes |
| Payment terms set (`payment_term_days > 0`) | `unpaid` | `0` | No |

---

### 1.6 Quotations

From the POS, a staff member can save the cart as a **quotation** instead of completing a sale.

1. Open the Quotation modal.
2. Add optional notes and set validity period (default 30 days).
3. Click **Save Quotation** — a `Quotation` record is created with the cart items. Inventory is **not** affected.
4. An email notification is sent to admins with the `approve_quotation` permission.
5. A print link is shown immediately after creation.

**Converting a quotation to a sale:**
1. Admin approves the quotation in the admin panel (status → `approved`).
2. Open POS with `?quotation_id=X` in the URL — cart is pre-filled with the quotation's items.
3. Complete the sale normally — quotation status changes to `converted_to_sale`.

**Quotation statuses:**

| Status | Meaning |
|---|---|
| Pending | Awaiting admin approval |
| Approved | Ready to convert to sale |
| Rejected | Declined by admin |
| Converted to Sale | Sale was completed from this quotation |
| Expired | Past the valid-until date |

---

### 1.7 Receipt Reprinting

The **Reprint** section shows the last 50 sales. Search by receipt number or customer name, then reprint as either a thermal receipt or delivery receipt format.

---

### 1.8 POS API Endpoints

| Method | URL | Purpose |
|---|---|---|
| GET | `/pos` | Load POS terminal |
| POST | `/pos/register/open` | Open cash register session |
| POST | `/pos/register/close` | Close cash register session |
| GET | `/pos/register/status` | Check if session is open |
| POST | `/pos/customer` | Create new customer inline |
| POST | `/pos/quotation` | Create a quotation |
| POST | `/pos/complete-sale` | Complete a sale |
| GET | `/pos/recent-sales` | Fetch last 50 sales for reprint |
| GET | `/pos/print-receipt/{sale}` | Print receipt view |
| GET | `/pos/quotation/{quotation}/print` | Print quotation view |
| GET | `/pos/csrf-token` | Refresh CSRF token (long sessions) |
| GET | `/pos/register/{session}/sales-report` | Download register closure PDF report |

---

## 2. Inventory & Sales

### 2.1 Categories

**Navigation group:** Inventory & Sales
**Model:** `app/Models/Category.php`

Simple lookup table for organising products.

| Field | Type | Notes |
|---|---|---|
| name | string | Required, shown in product cards on POS |

---

### 2.2 Products

**Navigation group:** Inventory & Sales
**Model:** `app/Models/Product.php`

| Field | Type | Notes |
|---|---|---|
| name | string | Required |
| category_id | FK → Category | Required |
| supplier_id | FK → Supplier | Required |
| price | decimal(10,2) | Selling price (PHP) |
| cost_price | decimal(10,2) | Purchase cost; used for profit calculations |
| quantity | numeric | Tracked via linked Inventory record |
| unit | enum (ProductUnit) | See unit list below |

**Product Units:**

| Unit | Type |
|---|---|
| Piece | Unit |
| Bag, Box, Bundle, Knot, Tube | Package |
| Kilo, Gram | Weight |
| Liter, Milliliter | Liquid |
| Meter, Foot | Length |
| Cubic Meter | Volume |

Weight/liquid/length units trigger the measurement modal on the POS.

**Calculated fields (read-only):**
- `profit_margin` — `(price − cost_price) / price × 100`
- `profit_per_unit` — `price − cost_price`

If `cost_price` is not set, the system defaults to 70% of the selling price for margin estimates.

**Inventory:**
Each product has one linked `Inventory` record. The quantity there is decremented automatically when a `SaleItem` is created and incremented when a `PurchaseItem` is received.

---

### 2.3 Suppliers

**Navigation group:** Inventory & Sales
**Model:** `app/Models/Supplier.php`

| Field | Type | Notes |
|---|---|---|
| name | string | Required |
| contact_person | string | |
| phone | string | |
| email | string | |
| address | text | |
| payment_term_days | integer | 0 = COD; otherwise Net X days |

The `payment_term_label` accessor returns `"COD"` or `"Net 30"` etc.

When creating a purchase for this supplier, the `due_date` is automatically calculated as `purchase_date + payment_term_days`.

---

### 2.4 Customers

**Navigation group:** Inventory & Sales
**Model:** `app/Models/Customer.php`

| Field | Type | Notes |
|---|---|---|
| name | string | Required |
| contact_person | string | |
| company | string | |
| phone | string | |
| email | string | |
| address | text | |
| payment_term_days | integer | 0 = COD; otherwise Net X days |

When a sale is created for a customer with payment terms, the `due_date` is set automatically from their terms (unless overridden at sale time via the POS payment terms toggle).

---

### 2.5 Sales

**Navigation group:** Inventory & Sales
**Model:** `app/Models/Sale.php`

Sales are created through the POS. They can also be created manually through the admin panel.

| Field | Type | Notes |
|---|---|---|
| customer_id | FK → Customer | Nullable (walk-in) |
| cash_register_session_id | FK | Nullable |
| date | date | Auto-set to current date |
| total | decimal(10,2) | Sum of all sale items |
| payment_method | string | cash / bank_transfer / check / credit_card / gcash / maya |
| payment_term_days | integer | Nullable; set if terms applied |
| due_date | date | Auto-calculated from customer terms or POS terms override |
| payment_status | string | unpaid / partial / paid |
| amount_paid | decimal(10,2) | Amount collected so far |
| paid_date | date | Date fully paid |

**Calculated accessors:**
- `balance` — `total − amount_paid`
- `days_overdue` — days past due date (null if current or paid)
- `aging_bucket` — Current / 1–30 Days / 31–60 Days / 61–90 Days / Over 90 Days

**Inventory impact:**
When a `SaleItem` is created, inventory is decremented automatically (via model hook). Manual items are excluded.

**Admin Panel — Sales List**

The Sales list table shows a colour-coded **Status** badge per record:

| Badge colour | Status |
|---|---|
| Green | `paid` |
| Yellow | `partial` |
| Red | `unpaid` / other |

**Mark as Paid action**

A **Mark as Paid** button appears on rows where `payment_term_days` is set **and** `payment_status ≠ paid`. Clicking it shows a confirmation modal and, on confirm, sets:
- `payment_status = paid`
- `amount_paid = total`
- `paid_date = today`

> This button never appears on cash/card/GCash/PayMaya sales since those have no payment terms and are paid immediately at point of sale.

**Sales Summary CSV Download**

A **Download Summary** button in the page header lets you export an aggregated CSV of sales grouped by date.

- Select a period (Today, This Week, This Month, etc.) or a custom date range.
- The CSV contains: `Date | Sales Count | Total` per day, with a **GRAND TOTAL** row at the bottom.
- File: `app/Exports/SalesSummaryExport.php`
- Filename format: `sales-summary-{period}-YYYY-MM-DD-HHiiss.csv`

---

### 2.6 Purchases

**Navigation group:** Inventory & Sales
**Model:** `app/Models/Purchase.php`

Purchases record stock received from suppliers.

**Purchase header fields:**

| Field | Type | Notes |
|---|---|---|
| supplier_id | FK → Supplier | Required |
| date | date | Purchase date |
| total | decimal(10,2) | Auto-calculated from received items |
| due_date | date | Auto-calculated from supplier payment terms |
| payment_status | string | unpaid / partial / paid |
| amount_paid | decimal(10,2) | |
| paid_date | date | |

**Purchase item fields (per line):**

| Field | Type | Notes |
|---|---|---|
| product_id | FK → Product | Required |
| unit | enum (ProductUnit) | Auto-filled from product |
| quantity | integer | Ordered quantity |
| quantity_received | integer | Actually received (default 0) |
| price | decimal(10,2) | Unit cost price |

**Partial Receipt Workflow:**
When a supplier can only fulfil part of an order:
- Set `quantity` = total ordered
- Set `quantity_received` = what physically arrived

Only `quantity_received` drives inventory and the purchase total. The receipt status is calculated automatically:

| Status | Condition |
|---|---|
| Pending | No items received (`quantity_received = 0` for all) |
| Partial | At least one item received, but not all |
| Received | All items fully received (`quantity_received ≥ quantity` for all) |

**Inventory impact:**
- On create: inventory += `quantity_received`
- On update: inventory adjusted by the delta in `quantity_received`
- On delete: inventory -= `quantity_received`

**Calculated accessors:**
- `balance` — `total − amount_paid`
- `days_overdue` — days past due date
- `aging_bucket` — same buckets as Sales

---

### 2.7 Quotations

**Navigation group:** Inventory & Sales
**Model:** `app/Models/Quotation.php`
**Observer:** `app/Observers/QuotationObserver.php`

| Field | Type | Notes |
|---|---|---|
| quotation_number | string | Auto-generated: `QT-YYYYMMDD-XXXX` |
| customer_id | FK → Customer | Required |
| date | date | Issue date |
| valid_until | date | Expiry date (default +30 days from POS, +15 days from admin) |
| total | decimal(10,2) | Sum of quotation items |
| notes | text | Terms, conditions, remarks |
| status | enum (QuotationStatus) | See statuses below |
| created_by | FK → User | Auto-set to the authenticated user |

**Observer behaviour:**
- `creating` — auto-sets `created_by`
- `created` — sends email notification to admins with `approve_quotation` permission
- `saved` — recalculates `total` from items on updates (skipped on initial creation to avoid a timing issue)

**Quotation statuses:** See [Section 1.6](#16-quotations).

---

## 3. Delivery Management

### 3.1 Deliveries

**Navigation group:** Delivery Management
**Model:** `app/Models/Delivery.php`

Deliveries are linked to a completed Sale.

| Field | Type | Notes |
|---|---|---|
| sale_id | FK → Sale | Required |
| driver_id | FK → Driver | Assigned driver |
| status | enum (DeliveryStatus) | See below |
| delivery_address | string | |
| distance_km | decimal | |
| notes | text | |
| assigned_at | datetime | |
| picked_up_at | datetime | |
| delivered_at | datetime | |
| rating | integer (1–5) | Customer rating |
| customer_feedback | text | |

**Delivery statuses:**

| Status | Colour |
|---|---|
| Pending | Gray |
| Assigned | Blue |
| Picked Up | Yellow |
| In Transit | Primary |
| Delivered | Green |
| Failed | Red |
| Returned | Yellow |

**Calculated accessor:**
- `delivery_time_minutes` — time from `picked_up_at` to `delivered_at`

A **Print** action is available to generate a delivery receipt for the driver.

---

### 3.2 Drivers

**Navigation group:** Delivery Management
**Model:** `app/Models/Driver.php`

| Field | Type | Notes |
|---|---|---|
| name | string | Required |
| phone | string | |
| license_number | string | |
| vehicle_type | string | |
| vehicle_plate | string | |
| is_active | boolean | Default true; inactive drivers hidden from delivery assignment |

**Calculated accessors:**
- `delivery_count` — total deliveries
- `average_rating` — mean rating across completed deliveries
- `success_rate` — % of deliveries with status `Delivered`

---

## 4. Finance

### 4.1 Expenses

**Navigation group:** Finance
**Model:** `app/Models/Expense.php`

| Field | Type | Notes |
|---|---|---|
| reference_number | string | Auto-generated: `EXP-YYYYMMDD-XXXX` |
| expense_category_id | FK → ExpenseCategory | |
| expense_date | date | Cannot be a future date |
| amount | decimal(10,2) | Required, min ₱0.01 |
| payment_method | string | cash / bank_transfer / check / credit_card / gcash / maya |
| payee | string | Who was paid |
| description | text | |
| receipt_path | file | Image or PDF, max 5 MB |
| status | string | pending / approved / rejected (default approved) |
| user_id | FK → User | Who recorded the expense |

**Filters available:** Category, status, payment method, date range.

Expense amounts are included in the `AccountingService.getTotalExpenses()` calculation (approved only).

---

### 4.2 Expense Categories

**Navigation group:** Finance
**Model:** `app/Models/ExpenseCategory.php`

| Field | Type | Notes |
|---|---|---|
| name | string | Required |
| description | text | |
| is_active | boolean | Inactive categories hidden from expense form |

The `total_expenses` accessor returns the sum of all approved expenses in this category.

---

### 4.3 Financial Overview (Accounting Service)

**Service:** `app/Services/AccountingService.php`
**Used by:** `FinancialOverviewWidget` on the dashboard

The accounting service calculates all financial metrics for any date range.

**Revenue & Collections:**

| Metric | Description |
|---|---|
| Total Revenue | Sum of all `sales.total` in period |
| Total Collections | Sum of `sales.amount_paid` in period |
| Accounts Receivable | Revenue − Collections (unpaid balances) |

**Cost & Purchasing:**

| Metric | Description |
|---|---|
| COGS | Sum of `cost_price × quantity` for items sold; defaults to 70% of selling price if `cost_price` not set |
| Total Purchases | Sum of all `purchases.total` in period |
| Accounts Payable | Purchase total − amount paid (outstanding payables) |

**Profitability:**

| Metric | Formula |
|---|---|
| Gross Profit | Revenue − COGS |
| Gross Profit Margin | Gross Profit ÷ Revenue × 100 |
| Operating Costs | Expenses + Maintenance costs |
| Operating Profit | Gross Profit − Operating Costs |
| Net Profit | Same as Operating Profit |
| Net Profit Margin | Net Profit ÷ Revenue × 100 |

**Dashboard widget** shows current month vs last month with trend sparklines for Revenue, Profit, and Expenses.

**Predefined periods:** today, yesterday, this week, last week, this month, last month, this quarter, last quarter, this year, last year.

---

## 5. Fleet Management

### 5.1 Vehicles

**Navigation group:** Fleet Management
**Model:** `app/Models/Vehicle.php`

| Field | Type | Notes |
|---|---|---|
| plate_number | string | Unique, required |
| make | string | Manufacturer (datalist autocomplete) |
| model | string | |
| year | integer | 1990 to current year |
| color | string | |
| vin | string | Unique chassis number |
| engine_number | string | |
| fuel_type | enum | gasoline / diesel / electric / hybrid |
| transmission | enum | automatic / manual |
| current_mileage | integer | Updated after each service |
| acquisition_date | date | |
| acquisition_cost | decimal | |
| status | enum | active / maintenance / inactive / sold |
| assigned_driver_id | FK → User | Optional driver assignment |
| notes | text | |

**Calculated accessors:**
- `full_name` — `{year} {make} {model}`
- `total_maintenance_cost` — sum of all service record costs
- `last_maintenance_date` — date of most recent service
- `maintenance_due` — true if mileage or date exceeds the next service threshold

---

### 5.2 Service Types

**Navigation group:** Fleet Management
**Label:** Service Types
**Model:** `app/Models/MaintenanceType.php`

| Field | Type | Notes |
|---|---|---|
| name | string | Required (e.g. "Oil Change", "Tire Rotation") |
| description | text | |
| recommended_interval_km | integer | Trigger after this many km |
| recommended_interval_months | integer | Or after this many months |
| is_active | boolean | |

The `interval_display` accessor formats as `"5,000 km or 3 months"` or `"As needed"` if no interval is set.

---

### 5.3 Service Records

**Navigation group:** Fleet Management
**Label:** Service Records
**Model:** `app/Models/MaintenanceRecord.php`

| Field | Type | Notes |
|---|---|---|
| reference_number | string | Auto-generated: `MNT-YYYYMMDD-XXXX` |
| vehicle_id | FK → Vehicle | Required |
| maintenance_type_id | FK → MaintenanceType | Required |
| user_id | FK → User | Technician/person in charge |
| maintenance_date | date | Required |
| mileage_at_service | integer | Odometer at time of service |
| parts_cost | decimal | |
| labor_cost | decimal | |
| service_provider | string | External shop name if applicable |
| description | text | Work performed |
| parts_replaced | text | List of replaced parts |
| next_service_date | date | |
| next_service_mileage | integer | |
| status | enum | completed / pending / in_progress |
| invoice_path | file | Upload of service invoice |

**Calculated accessors:**
- `total_cost` — `parts_cost + labor_cost`
- `is_overdue` — true if past `next_service_date` or past `next_service_mileage`

---

### 5.4 Service Requests

**Navigation group:** Fleet Management
**Label:** Service Requests
**Model:** `app/Models/MaintenanceRequest.php`

Staff submit service requests which go through an approval workflow before becoming a Service Record.

| Field | Type | Notes |
|---|---|---|
| request_number | string | Auto-generated: `REQ-YYYYMMDD-XXXX` |
| vehicle_id | FK → Vehicle | Required |
| maintenance_type_id | FK → MaintenanceType | |
| priority | enum | high / normal / low |
| current_mileage | integer | Current odometer reading |
| description | text | Description of the issue |
| preferred_date | date | Requested service date |
| status | enum | pending / approved / rejected / completed |
| estimated_cost | decimal | Filled in during approval |
| rejection_reason | text | Required if rejected |
| approved_by | FK → User | |
| maintenance_record_id | FK → MaintenanceRecord | Linked after completion |

A **badge** on the navigation item shows the count of pending requests.

---

## 6. Attendance Management

### 6.1 Attendance

**Navigation group:** Attendance Management
**Model:** `app/Models/Attendance.php`

| Field | Type | Notes |
|---|---|---|
| date | date | Cannot be a future date |
| user_id | FK → User | Employee (excludes super_admin) |
| time_in | time | |
| time_out | time | |
| total_hours | decimal | Auto-calculated from time_in/time_out |
| status | enum (AttendanceStatus) | See below |
| remarks | text | |
| recorded_by | FK → User | Who logged this attendance record |

**Attendance statuses:**

| Status | Colour |
|---|---|
| Present | Green |
| Absent | Red |
| Late | Yellow |
| Half Day | Blue |
| On Leave | Gray |

**Filters:** Status, employee, date range.

---

### 6.2 Leave Types

**Navigation group:** Attendance Management
**Model:** `app/Models/LeaveType.php`

| Field | Type | Notes |
|---|---|---|
| name | string | Required (e.g. "Sick Leave", "Vacation Leave") |
| description | text | |
| max_days_per_year | integer | Annual entitlement |
| is_paid | boolean | Whether this leave is compensated |
| is_active | boolean | |

---

### 6.3 Leave Requests

**Navigation group:** Attendance Management
**Model:** `app/Models/LeaveRequest.php`

| Field | Type | Notes |
|---|---|---|
| request_number | string | Auto-generated: `LR-YYYYMMDD-XXXX` |
| user_id | FK → User | Employee making the request |
| leave_type_id | FK → LeaveType | |
| start_date | date | |
| end_date | date | |
| total_days | integer | Auto-calculated |
| reason | text | |
| status | enum | pending / approved / rejected / cancelled |
| rejection_reason | text | Required if rejected |
| approved_by | FK → User | |

A **badge** on the navigation item shows the count of pending requests.

---

## 7. Payroll

**Navigation group:** Payroll
**Model:** `app/Models/Payroll.php`

Payrolls follow a **Draft → Approved → Paid** workflow.

**Payroll header:**

| Field | Type | Notes |
|---|---|---|
| payroll_number | string | Auto-generated: `PAY-YYYYMMDD-XXXX` |
| pay_period_start | date | |
| pay_period_end | date | |
| pay_period_type | enum | Daily / Weekly / Semi-Monthly |
| total_gross | decimal | Auto-calculated from items |
| total_deductions | decimal | Auto-calculated from items |
| total_net | decimal | `gross − deductions` |
| status | enum (PayrollStatus) | Draft / Approved / Paid / Cancelled |
| approved_by | FK → User | |
| paid_at | datetime | |
| notes | text | |

**Payroll items** (per employee line):
Managed via a Relation Manager inside the Payroll view. Each item covers one employee's gross pay, deductions, and net pay for the period.

**Workflow methods:**
- `canBeApproved()` — only when status is Draft
- `canBePaid()` — only when status is Approved
- `canBeCancelled()` — cannot cancel after Paid
- `recalculateTotals()` — re-sums all payroll items

A **badge** on the navigation item shows the count of Draft payrolls awaiting approval.

---

## 8. Authentication & Security

### 8.1 Users

**Model:** `app/Models/User.php`

| Field | Type | Notes |
|---|---|---|
| name | string | Required |
| email | string | Required, unique |
| password | string | Hashed |
| email_verified_at | datetime | |

Users are assigned one or more **roles** (via Spatie Permissions). Roles control which resources and actions a user can access.

Users also serve as employees — they are linked to `Attendance`, `LeaveRequest`, `EmployeeCompensation`, and `PayrollItem` records.

The audit trail excludes sensitive fields: `password`, `remember_token`, `email_verified_at`.

---

### 8.2 Roles & Permissions

**Plugin:** FilamentShield (Spatie Permissions)

Roles are managed in the admin panel. Each role can be granted or denied access to individual Filament resources (view, create, update, delete) and specific custom permissions (e.g. `approve_quotation`).

**Notable custom permission:**
`approve_quotation` — allows the user to approve or reject quotations and receive email notifications for new quotations.

---

### 8.3 Audit Logs

**Model:** `app/Models/AuditLog.php`
**Access:** View only — no create, edit, or delete

All significant actions are recorded automatically via the `Auditable` trait used across most models.

| Field | Description |
|---|---|
| user_name | Name of the user who performed the action |
| action | e.g. `completed_sale`, `approved`, `opened_register` |
| auditable_label | Human-readable name of the affected record |
| auditable_type | Model class (e.g. `App\Models\Sale`) |
| old_values | JSON of fields before the change |
| new_values | JSON of fields after the change |
| ip_address | Client IP |
| user_agent | Browser/device string |
| created_at | Timestamp |

Audit logs are read-only and cannot be deleted from the interface.

---

## Appendix — Reference Numbers

All auto-generated reference numbers follow this format:

| Record | Format | Example |
|---|---|---|
| Quotation | `QT-YYYYMMDD-XXXX` | `QT-20260220-0001` |
| Expense | `EXP-YYYYMMDD-XXXX` | `EXP-20260220-0001` |
| Service Record | `MNT-YYYYMMDD-XXXX` | `MNT-20260220-0001` |
| Service Request | `REQ-YYYYMMDD-XXXX` | `REQ-20260220-0001` |
| Leave Request | `LR-YYYYMMDD-XXXX` | `LR-20260220-0001` |
| Payroll | `PAY-YYYYMMDD-XXXX` | `PAY-20260220-0001` |

The sequence resets daily (`XXXX` starts at `0001` each day).

---

## Appendix — Inventory Flow Summary

```
Purchase Created
    └─ PurchaseItem saved
           └─ quantity_received > 0 → Inventory.quantity += quantity_received

Sale Completed (POS or Admin)
    └─ SaleItem saved
           └─ is_manual = false → Inventory.quantity -= quantity

Quotation Created
    └─ QuotationItem saved
           └─ No inventory change (quotation is not a commitment)

Quotation → Sale (Convert)
    └─ SaleItem saved (same as Sale Completed above)
```

---

*Generated: 2026-02-20*
