# Charge Transaction – How It Works

## What is a Charge Transaction?

A **Charge** transaction is a sale where the customer does not pay immediately. Instead, payment is collected later based on an agreed payment term (5, 10, 15, 30, or 60 days). It is essentially a credit sale.

> Charge transactions are recorded in the system but **do not appear in any report until the customer has fully paid**.

---

## How to Add a Charge Transaction

### Step 1 – Add Items to the Cart

Add the products to the cart as you normally would for any sale.

### Step 2 – Select the Customer

**A customer must be selected** for a charge transaction. Charge sales should always be tied to a named account for collection tracking.

Click the customer selector and choose the customer from the list. If the customer is new, use the **+ Add Customer** button.

### Step 3 – Click "Charge / Pay"

Click the **Charge / Pay** button at the bottom of the cart panel to open the payment modal.

### Step 4 – Select "Charge" as the Payment Method

In the **Payment Method** dropdown, select **Charge**.

Once selected, the **Payment Terms** selector will appear below.

### Step 5 – Select the Payment Term

Choose the number of days the customer has to pay:

| Term    | Meaning                          |
|---------|----------------------------------|
| 5 Days  | Payment due in 5 days            |
| 10 Days | Payment due in 10 days           |
| 15 Days | Payment due in 15 days           |
| 30 Days | Payment due in 30 days           |
| 60 Days | Payment due in 60 days           |

The exact **due date** is automatically calculated and displayed below the term buttons (e.g., *Due date: April 15, 2026*).

### Step 6 – Confirm the Transaction

Click **Confirm Payment**. The sale is saved with:

- `payment_method` = `charge`
- `payment_status` = `unpaid`
- `payment_term_days` = the selected term
- `amount_paid` = `0`
- `due_date` = today + selected term days

---

## What Happens After Confirming

- The sale is recorded in the system and visible in **Sales Management** under the admin panel.
- The transaction is tagged as **Unpaid**.
- It **will not appear** in the Daily Transaction Report, Register Closure Report, or Period Report until the payment status is updated to **Paid**.
- Inventory is deducted immediately upon confirming the sale.

---

## How to Mark a Charge Transaction as Paid

1. Go to the **Admin Panel → Sales**.
2. Find the charge transaction (filter by payment method: *Charge* or payment status: *Unpaid*).
3. Open the sale record and update the **Payment Status** to `Paid` and set the **Amount Paid** and **Paid Date**.
4. Save the record.

Once marked as paid, the transaction will automatically be included in all future and historical reports.

---

## Key Rules

- Charge transactions are **excluded from all reports** while they remain unpaid.
- Charge sales **do not count toward the cash register session totals** (total sales, cash sales, transaction count) since no money changes hands at the time of sale.
- Inventory is still reduced at the time of the transaction, regardless of payment status.
- Always assign a customer to a charge transaction for proper accounts receivable tracking.
