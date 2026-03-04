# TOS — User Guide

> **Tri-E Enterprises — Internal Operations System**
> This guide is written for everyday users of the system: cashiers, admin staff, and supervisors.

---

## Table of Contents

1. [Logging In](#1-logging-in)
2. [Navigating the Admin Panel](#2-navigating-the-admin-panel)
3. [Point of Sale (POS)](#3-point-of-sale-pos)
   - 3.1 [Opening the Cash Register](#31-opening-the-cash-register)
   - 3.2 [Browsing and Searching Products](#32-browsing-and-searching-products)
   - 3.3 [Adding Products to the Cart](#33-adding-products-to-the-cart)
   - 3.4 [Managing the Cart](#34-managing-the-cart)
   - 3.5 [Selecting a Customer](#35-selecting-a-customer)
   - 3.6 [Processing a Payment](#36-processing-a-payment)
   - 3.7 [Payment Terms (Credit Sales)](#37-payment-terms-credit-sales)
   - 3.8 [Printing a Receipt](#38-printing-a-receipt)
   - 3.9 [Reprinting a Past Receipt](#39-reprinting-a-past-receipt)
   - 3.10 [Creating a Quotation](#310-creating-a-quotation)
   - 3.11 [Closing the Cash Register](#311-closing-the-cash-register)
4. [Sales Management (Admin Panel)](#4-sales-management-admin-panel)
   - 4.1 [Viewing All Sales](#41-viewing-all-sales)
   - 4.2 [Understanding Payment Status](#42-understanding-payment-status)
   - 4.3 [Marking a Credit Sale as Paid](#43-marking-a-credit-sale-as-paid)
   - 4.4 [Downloading a Sales Summary (CSV)](#44-downloading-a-sales-summary-csv)
   - 4.5 [Editing a Sale](#45-editing-a-sale)
5. [Customers](#5-customers)
6. [Quotations](#6-quotations)
7. [Deliveries](#7-deliveries)
8. [Reports](#8-reports)
   - 8.1 [Sales Report](#81-sales-report)
   - 8.2 [Inventory Report](#82-inventory-report)
   - 8.3 [Aging Report](#83-aging-report)
9. [Common Questions & Troubleshooting](#9-common-questions--troubleshooting)

---

## 1. Logging In

1. Open your browser and go to the system URL provided by your administrator.
2. Enter your **email address** and **password**.
3. Click **Log In**.

> If you forgot your password, contact your system administrator to reset it.

---

## 2. Navigating the Admin Panel

After logging in you will see the **admin panel**. The left sidebar contains the navigation menu grouped by module:

| Group | What's inside |
|---|---|
| (Home) | Dashboard with key metrics |
| Inventory & Sales | Categories, Products, Suppliers, Customers, Sales, Purchases, Quotations |
| Delivery Management | Deliveries, Drivers |
| Finance | Expenses, Expense Categories |
| Fleet Management | Vehicles, Service Records, Service Requests |
| Attendance Management | Attendance, Leave Types, Leave Requests |
| Payroll | Payrolls |
| Reports | Sales Report, Inventory Report, Aging Report, and more |
| Settings | Users, Roles & Permissions, Audit Logs |

To open the **POS terminal**, click **Point of Sale** in the sidebar or navigate to `/pos`.

---

## 3. Point of Sale (POS)

The POS terminal is used for all counter sales. It runs in your browser — you do not need to reload the page between transactions.

---

### 3.1 Opening the Cash Register

You **must** open the register before processing any sale.

1. When you first open the POS, a prompt appears asking for your **Opening Amount**.
2. Count the physical cash in the drawer and enter the total (e.g., `500.00`).
3. Click **Open Register**.

The register is now open and tied to your user account. You are ready to process sales.

> Only one open session per user is allowed at a time.

---

### 3.2 Browsing and Searching Products

- **Product cards** are displayed in the centre of the screen, sorted by stock level (highest first).
- Use the **search bar** at the top to find a product by name.
- Use the **category filter** buttons to narrow down products by category.
- Products with **zero stock** are shown with an out-of-stock indicator and cannot be added to the cart.

---

### 3.3 Adding Products to the Cart

**Standard products:**
1. Click the product card.
2. If the product is sold by weight, volume, or length (kg, g, L, mL, m, ft), a modal will appear — enter the measured quantity and click **Add to Cart**.
3. For all other units (pieces, bags, boxes, etc.), the item is added directly with a quantity of 1.

**Manual / custom items** (items not in the catalogue):
1. Click **Add Manual Item** (or the custom item button in the cart panel).
2. Enter: Item name, Unit, Unit Price, and Quantity.
3. Click **Add** — the item is added to the cart but does **not** affect inventory.

---

### 3.4 Managing the Cart

Once items are in the cart:

| Action | How |
|---|---|
| Increase quantity | Click the **+** button next to the item |
| Decrease quantity | Click the **−** button (minimum 0.01) |
| Type a quantity | Click directly on the quantity number and type a new value |
| Remove an item | Click the **trash / × icon** on that line |
| Clear the entire cart | Click **Clear Cart** — a confirmation prompt will appear |

The **cart total** updates automatically as you make changes.

---

### 3.5 Selecting a Customer

Before charging, you can attach a customer to the sale (optional for walk-in customers).

**Search for an existing customer:**
1. Click the **Customer** field or search box.
2. Type the customer's name — matching results appear as you type.
3. Click the customer name to select them.

**Add a new customer on the spot:**
1. Click **Add New Customer** (or the + icon next to the customer field).
2. Fill in: Name (required), Phone, Email, Address.
3. Click **Save** — the new customer is automatically selected.

**Walk-in sale:** Leave the customer field blank. The sale will be recorded as a walk-in.

---

### 3.6 Processing a Payment

1. Review the cart items and total.
2. Click the **Charge** button.
3. The payment modal opens showing the total amount due.

**Select a payment method:**

| Method | When to use |
|---|---|
| Cash | Customer pays with physical cash |
| GCash | Customer pays via GCash QR or transfer |
| Maya | Customer pays via Maya |
| Card | Credit or debit card payment |
| Bank Transfer | Direct bank transfer |
| Check | Payment by cheque |

**For cash payments:**
- Enter the **Amount Received** from the customer.
- The system automatically calculates the **change**.

4. Click **Confirm Payment**.
5. The sale is saved, inventory is updated, and a success screen appears.

---

### 3.7 Payment Terms (Credit Sales)

Payment terms allow a customer to pay later within an agreed number of days.

**When to use:** Only for trusted customers with an approved credit arrangement.

**How to apply payment terms:**
1. In the payment modal, toggle on **Add Payment Terms**.
2. Select the credit period: **5, 10, 15, 30, or 60 days**.
3. The system automatically calculates the **Due Date** based on today's date.
4. Select a payment method and click **Confirm Payment**.

**What happens:**
- The sale is saved with `payment_status = unpaid`.
- The sale is **not** counted in the register's cash totals (because no money has been collected yet).
- The sale appears in the **Sales list** with a **red "unpaid" badge**.
- When the customer pays later, a supervisor marks it as paid from the admin panel (see [Section 4.3](#43-marking-a-credit-sale-as-paid)).

> **Important:** Never apply payment terms to a cash or instant payment. Payment terms are only for genuine credit arrangements.

---

### 3.8 Printing a Receipt

After a successful payment, two receipt options are shown:

| Option | Use |
|---|---|
| **Delivery Receipt** | Customer's order will be delivered — includes driver/receiver signature lines |
| **Pick Up Receipt** | Customer picks up at the counter — includes released-by/picked-up-by signature lines |

1. Click the receipt type button.
2. A new tab opens with the formatted receipt.
3. Click **Print** in the top-right corner.
4. The receipt prints two copies side-by-side on A4 landscape paper: **Office Copy** and **Customer's Copy**, separated by a cut line.

---

### 3.9 Reprinting a Past Receipt

1. In the POS, click the **Reprint** section (usually a tab or button on the right panel).
2. The last **50 sales** are listed, most recent first.
3. Search by receipt number or customer name.
4. Click **Print Receipt** on the sale you need — choose Delivery or Pick Up format.

---

### 3.10 Creating a Quotation

A quotation saves the current cart as a price proposal without completing a sale. Inventory is **not affected**.

1. Build the cart as normal (add products, set quantities).
2. Optionally select a customer.
3. Click **Create Quotation**.
4. A modal opens — add any notes and set the validity period (default 30 days).
5. Click **Save Quotation**.
6. A print link appears immediately. Click it to open and print the quotation document.

**What happens next:**
- An admin receives an email notification to approve or reject the quotation.
- Once **approved**, the quotation can be converted to a sale by opening the POS with the quotation link.
- Once converted, the quotation status changes to **Converted to Sale**.

**Quotation statuses:**

| Status | Meaning |
|---|---|
| Pending | Waiting for admin approval |
| Approved | Ready to convert to a sale |
| Rejected | Declined by admin |
| Converted to Sale | Already completed as a sale |
| Expired | Validity period has passed |

---

### 3.11 Closing the Cash Register

At the end of your shift:

1. Click the **register/cash icon** or the **Close Register** button in the POS.
2. Physically count all the cash in your drawer.
3. Enter the **Closing Amount** (the physical cash count).
4. Add any **Notes** if needed (e.g., unusual transactions, issues).
5. Click **Close Register**.

**What happens:**
- The system calculates the **expected amount** (opening cash + all cash sales collected).
- The **discrepancy** is shown (positive = overage, negative = shortage).
- A **PDF report** automatically downloads in a new tab containing:
  - Session summary: opening amount, total sales, cash collected, expected cash, closing amount, and discrepancy
  - Full list of all sales processed in the session, including customer names, payment method, payment status, and amounts

> Save or print the PDF report for your records. You can re-download it anytime from the admin panel.

---

## 4. Sales Management (Admin Panel)

**Navigation:** Inventory & Sales → Sales

---

### 4.1 Viewing All Sales

The Sales list shows all recorded sales with the following columns:

| Column | Description |
|---|---|
| Customer | Customer name (or blank for walk-in) |
| Date | Date the sale was made |
| Items | Number of items in the sale |
| Status | Payment status badge (green = paid, yellow = partial, red = unpaid) |
| Total | Sale total in PHP |

At the bottom of the Total column, a **grand sum** of all visible sales is shown.

**Searching:** Use the search bar at the top to find sales by customer name.

---

### 4.2 Understanding Payment Status

| Badge | Colour | Meaning |
|---|---|---|
| paid | Green | Payment has been fully collected |
| partial | Yellow | Part of the amount has been collected |
| unpaid | Red | No payment collected yet (credit/terms sale) |

> Cash, GCash, Maya, Card, and Bank Transfer sales are always marked **paid** immediately at point of sale. Only credit/term sales start as **unpaid**.

---

### 4.3 Marking a Credit Sale as Paid

When a customer pays their outstanding credit balance:

1. Go to **Sales** in the admin panel.
2. Find the sale (it will have a red **unpaid** badge).
3. Click the green **Mark as Paid** button on that row.
4. A confirmation dialog appears showing the amount: *"Mark ₱X,XXX.XX sale as fully paid?"*
5. Click **Confirm**.

The sale is immediately updated:
- `payment_status` → **paid**
- `amount_paid` → full total
- `paid_date` → today's date

> The **Mark as Paid** button only appears on credit/term sales. It will never appear on cash or instant payment sales.

---

### 4.4 Downloading a Sales Summary (CSV)

The Sales Summary is a CSV file that shows total sales grouped by date — useful for daily or monthly reporting.

1. Go to **Sales** in the admin panel.
2. Click the **Download Summary** button (top right, with a download icon).
3. A dialog appears — select your period:

| Option | Covers |
|---|---|
| Today | Current date only |
| Yesterday | Previous day |
| This Week | Monday to Sunday of the current week |
| Last Week | Previous full week |
| This Month | Current calendar month |
| Last Month | Previous calendar month |
| This Year | January to December of the current year |
| Custom Date Range | Choose your own From and To dates |

4. Click **Download**.
5. The CSV file opens or saves to your computer.

**CSV format:**

| Date | Sales Count | Total |
|---|---|---|
| 2026-03-01 | 8 | 24,500.00 |
| 2026-03-02 | 5 | 15,200.00 |
| GRAND TOTAL | 13 | 39,700.00 |

---

### 4.5 Editing a Sale

1. Find the sale in the Sales list.
2. Click the **Edit** (pencil) icon on that row.
3. Make changes to the customer, date, items, or payment details.
4. Click **Save**.

> Editing a sale does not automatically adjust inventory. Contact your administrator if a product quantity needs correction.

---

## 5. Customers

**Navigation:** Inventory & Sales → Customers

The Customers list stores all customer profiles used across sales, quotations, and deliveries.

**Key fields:**

| Field | Description |
|---|---|
| Name | Customer's full name or business name |
| Company | Company name (optional) |
| Phone | Contact number |
| Email | Email address |
| Address | Delivery/billing address |
| Payment Term Days | Credit period for this customer (0 = cash only, e.g. 30 = Net 30) |

**Adding a new customer:**
1. Click **New Customer** (top right).
2. Fill in the required Name field and any other available details.
3. Click **Save**.

> Customers can also be added quickly from the POS without leaving the terminal (see [Section 3.5](#35-selecting-a-customer)).

**Payment Term Days:** If a customer has a default `payment_term_days` set (e.g., 30), the system will automatically calculate the due date when a sale is created for them. This can still be overridden at the POS during payment.

---

## 6. Quotations

**Navigation:** Inventory & Sales → Quotations

Quotations are price proposals sent to customers before a sale is confirmed.

**Quotation workflow:**

```
Created (Pending) → Approved → Converted to Sale
                 → Rejected
                 → Expired (if not actioned before valid_until date)
```

**Approving a quotation:**
1. Open the quotation from the list.
2. Review the items and total.
3. Click **Approve** or **Reject**.
4. Once approved, the quotation can be converted to a sale from the POS.

**Converting to a sale:**
1. Open the approved quotation.
2. Click **Convert to Sale** (or use the POS link provided).
3. The POS opens with the cart pre-filled with the quotation's items.
4. Complete the payment as normal.

**Printing a quotation:**
- Open any quotation and click **Print** to generate the formatted quotation document.

---

## 7. Deliveries

**Navigation:** Delivery Management → Deliveries

After a sale is completed, a delivery record can be created to track the shipment.

**Delivery statuses:**

| Status | Meaning |
|---|---|
| Pending | Delivery created, waiting for driver assignment |
| Assigned | Driver has been assigned |
| Picked Up | Driver has collected the goods |
| In Transit | Goods are on the way |
| Delivered | Successfully delivered to customer |
| Failed | Delivery was unsuccessful |
| Returned | Goods were returned |

**Creating a delivery:**
1. Go to **Deliveries** and click **New Delivery**.
2. Select the **Sale** this delivery is for.
3. Assign a **Driver**.
4. Enter the delivery address, distance (optional), and any notes.
5. Click **Save**.

**Printing a delivery receipt:**
- Open any delivery and click **Print Receipt** to generate the driver's delivery document with signature lines.

---

## 8. Reports

**Navigation:** Reports (in the left sidebar)

---

### 8.1 Sales Report

Provides a detailed table of all sales filtered by date range.

1. Go to **Reports → Sales Report**.
2. Use the **date filter** (From / Until) to set your reporting period.
3. The table shows each sale: date, customer, item count, and total.
4. A **sum** of all totals is shown at the bottom.

**Exporting to CSV:**
1. Click **Export to CSV** (top right).
2. Select a period or enter a custom date range.
3. Click **Export** — a CSV file downloads with each individual sale as a row.

---

### 8.2 Inventory Report

Shows current stock levels and inventory movements.

1. Go to **Reports → Inventory Report**.
2. Filter by product, category, or date range as needed.
3. Export to CSV if required.

---

### 8.3 Aging Report

Shows outstanding unpaid balances from customers, grouped by how long they have been overdue.

**Aging buckets:**

| Bucket | Overdue days |
|---|---|
| Current | Not yet due |
| 1–30 Days | 1 to 30 days overdue |
| 31–60 Days | 31 to 60 days overdue |
| 61–90 Days | 61 to 90 days overdue |
| Over 90 Days | More than 90 days overdue |

Use this report to identify customers who need to be followed up for payment.

---

## 9. Common Questions & Troubleshooting

**Q: The POS won't let me process a sale — it says the register is closed.**
> You need to open the cash register first. Click **Open Register**, enter your opening cash amount, and click confirm.

**Q: A product shows "Out of Stock" but I know we have stock.**
> Check the product's inventory in **Inventory & Sales → Products**. The quantity may need to be updated via a purchase receipt or manual adjustment by an admin.

**Q: I accidentally processed a sale with the wrong amount or wrong items.**
> Go to **Sales** in the admin panel, find the sale, and click **Edit**. Correct the details and save. For serious errors, contact your administrator.

**Q: The customer paid their credit balance but I can't find the Mark as Paid button.**
> The button only appears on sales that have payment terms set (credit/term sales with a red "unpaid" badge). Cash or instant payment sales do not have this button because they are already paid.

**Q: I closed the register but the PDF report didn't download.**
> Check if your browser blocked the pop-up. Allow pop-ups for this site and close the register again — or re-download the report by going to `/pos/register/{session-id}/sales-report` (ask your admin for the session ID).

**Q: I need a copy of an old register closure report.**
> Ask your administrator — they can re-download the report using the session ID from the system.

**Q: The sale total on the register doesn't match my sales receipts.**
> Credit/term sales are **not** counted in the register total until they are marked as paid. This is expected behaviour. Check the Sales list for unpaid entries.

**Q: I can't find a customer when searching in the POS.**
> Try searching with a different part of their name. If they are truly not in the system, use **Add New Customer** to create their profile on the spot.

**Q: A receipt printed incorrectly or I chose the wrong type.**
> Use the **Reprint** section in the POS to reprint the receipt in the correct format (Delivery or Pick Up).
