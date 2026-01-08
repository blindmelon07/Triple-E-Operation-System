# Tri-E Trading Operating System (TOS)
## Complete User Guide

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [Dashboard](#3-dashboard)
4. [Point of Sale (POS)](#4-point-of-sale-pos)
5. [Inventory & Sales Management](#5-inventory--sales-management)
6. [Finance Management](#6-finance-management)
7. [Delivery Management](#7-delivery-management)
8. [Fleet Management](#8-fleet-management)
9. [Reports](#9-reports)
10. [User Management & Security](#10-user-management--security)
11. [Tips & Best Practices](#11-tips--best-practices)

---

## 1. Introduction

### About Tri-E TOS

Tri-E Trading Operating System (TOS) is a comprehensive business management system designed for trading and retail operations. It provides integrated solutions for:

- **Point of Sale (POS)** - Process customer transactions quickly
- **Inventory Management** - Track products and stock levels
- **Sales & Purchasing** - Manage customer sales and supplier purchases
- **Financial Tracking** - Monitor expenses, profits, and cash flow
- **Delivery Management** - Track deliveries and drivers
- **Fleet Management** - Maintain vehicles and schedule services
- **Reporting** - Generate comprehensive business reports

### System Requirements

- Modern web browser (Chrome, Firefox, Edge, Safari)
- Internet connection
- For mobile POS: iOS or Android device with touch support

---

## 2. Getting Started

### Logging In

1. Navigate to your TOS URL ( `https://tri-e.cloud`)
2. Enter your **email address** and **password**
3. Click **Sign In**

### Navigation Overview

The system is organized into the following main sections (visible in the left sidebar):

| Section | Description |
|---------|-------------|
| **Dashboard** | Overview of key business metrics |
| **Inventory & Sales** | Products, Customers, Sales, Purchases, Suppliers |
| **Finance** | Expenses, Expense Categories, Financial Dashboard, Profit & Loss |
| **Delivery Management** | Deliveries, Drivers |
| **Fleet Management** | Vehicles, Service Types, Service Requests, Service Records |
| **Reports** | Sales, Inventory, Products, Aging, Driver KPI |
| **Authentication** | Users, Roles & Permissions |

### Quick Access: POS System

Click **"Back to Admin"** from the POS or access `/pos` directly for the point-of-sale interface.

---

## 3. Dashboard

The main dashboard provides an at-a-glance view of your business performance.

### Dashboard Widgets

- **Stats Overview** - Key metrics including total sales, orders, and revenue
- **Aging Alerts** - Notifications for overdue customer payments
- **Collection Reminders** - Upcoming payment due dates
- **Financial Overview** - Revenue, expenses, and profit summary

---

## 4. Point of Sale (POS)

The POS system is designed for fast, touch-friendly transactions.

### Accessing POS

- Click the POS link or navigate to `/pos`
- The interface works on both desktop and mobile/tablet devices

### Processing a Sale

#### Step 1: Select Products
1. Browse products in the grid or use the **search bar**
2. Filter by **category** using the dropdown
3. **Click/tap** a product to add it to the cart
4. For weight-based products (kilo, liter, meter), enter the quantity when prompted

#### Step 2: Adjust Quantities
- Use **+** and **-** buttons to adjust quantities
- Click the **X** to remove an item
- For weight-based items, quantities adjust by 0.1 units

#### Step 3: Select Customer (Optional)
- Choose from the **Customer** dropdown
- Click **"+"** to add a new customer on-the-fly
- Leave as "Walk-in Customer" for anonymous sales

#### Step 4: Complete the Sale
1. Click **"Complete Sale"**
2. Select **payment method** (Cash, Card, GCash, PayMaya)
3. For cash payments, enter the amount received
4. The system calculates change automatically
5. Click **"Confirm Payment"**

### Creating a Quotation

Instead of completing a sale, you can create a quotation:

1. Add products to cart as usual
2. Click **"Create Quotation"** (amber button)
3. Set the **validity period** (7-90 days)
4. Add optional **notes**
5. Click **"Create & Print"**
6. A printable quotation will open in a new window

### POS Features

| Feature | Description |
|---------|-------------|
| **Real-time Stock** | Shows available stock for each product |
| **Stock Alerts** | Warns when stock is low or unavailable |
| **Unit Conversion** | Supports grams↔kilos, ml↔liters, feet↔meters |
| **Mobile Support** | Full functionality on phones and tablets |
| **Quick Customer Add** | Add new customers without leaving POS |

### Supported Product Units

| Unit Type | Units |
|-----------|-------|
| **Count** | Piece |
| **Weight** | Kilo, Gram |
| **Liquid** | Liter, Milliliter |
| **Length** | Meter, Foot |
| **Volume** | Cubic Meter |
| **Package** | Bag, Box, Bundle, Tube, Knot |

---

## 5. Inventory & Sales Management

### Products

Navigate to: **Inventory & Sales → Products**

#### Adding a Product
1. Click **"New Product"**
2. Fill in the required fields:
   - **Name** - Product name
   - **Category** - Select or create a category
   - **Price** - Selling price
   - **Cost Price** - Purchase cost (for profit calculation)
   - **Unit** - Measurement unit
   - **Supplier** - Optional supplier link
3. Click **"Create"**

#### Managing Products
- **Search** - Use the search bar to find products
- **Filter** - Filter by category
- **Edit** - Click the edit icon to modify
- **Stock** - Stock is managed through Purchases (see below)

### Categories

Navigate to: **Inventory & Sales → Categories**

Organize your products into logical categories for easier browsing in the POS.

### Customers

Navigate to: **Inventory & Sales → Customers**

#### Customer Information
- **Name** - Customer's full name
- **Phone** - Contact number
- **Email** - Email address
- **Address** - Delivery/billing address
- **Payment Terms** - Days allowed for payment (0 = COD, 30 = Net 30, etc.)

#### Payment Terms
- **COD (0 days)** - Cash on delivery, payment due immediately
- **Net 15/30/60** - Payment due within specified days

### Sales

Navigate to: **Inventory & Sales → Sales**

View all sales transactions including:
- Customer name
- Date
- Total amount
- Payment status (Paid/Unpaid)
- Due date (for credit customers)

#### Creating a Manual Sale
1. Click **"New Sale"**
2. Select customer (or leave blank for walk-in)
3. Add products and quantities
4. Set payment status
5. Click **"Create"**

> **Note:** Most sales should be processed through the POS system for a better experience.

### Purchases

Navigate to: **Inventory & Sales → Purchases**

Purchases track goods received from suppliers and automatically update inventory.

#### Creating a Purchase
1. Click **"New Purchase"**
2. Select **Supplier**
3. Add **Purchase Items**:
   - Select product
   - Enter quantity
   - Enter purchase price
4. Click **"Create"**

> **Important:** Creating a purchase automatically increases the product's stock level.

### Suppliers

Navigate to: **Inventory & Sales → Suppliers**

Manage your product suppliers:
- **Name** - Supplier/company name
- **Contact Person** - Primary contact
- **Phone/Email** - Contact details
- **Address** - Business address
- **Payment Terms** - Your payment terms with this supplier

---

## 6. Finance Management

### Expenses

Navigate to: **Finance → Expenses**

Track all business expenses including:
- Utilities
- Rent
- Supplies
- Vehicle maintenance
- Salaries
- And more...

#### Recording an Expense
1. Click **"New Expense"**
2. Select **Category**
3. Enter **Amount**
4. Enter **Description**
5. Set **Date**
6. Click **"Create"**

### Expense Categories

Navigate to: **Finance → Expense Categories**

Create and manage expense categories to organize your spending. Default categories might include:
- Utilities
- Rent
- Supplies
- Transportation
- Salaries
- Marketing
- Maintenance

### Financial Dashboard

Navigate to: **Finance → Financial Dashboard**

A comprehensive financial overview showing:

#### Key Metrics
- **Total Revenue** - Income from sales
- **Total Expenses** - All recorded expenses
- **Gross Profit** - Revenue minus cost of goods
- **Net Profit** - Profit after all expenses

#### Period Selection
Choose from:
- Today
- This Week
- This Month
- This Year
- Custom Date Range

#### Features
- **Export to PDF** - Generate PDF reports
- **Export to Excel** - Download spreadsheet data
- **Expense Breakdown** - Visual breakdown by category
- **Quick Actions** - Links to common tasks

### Profit & Loss Report

Navigate to: **Finance → Profit & Loss**

Detailed profit and loss statement showing:
- Revenue breakdown
- Cost of goods sold
- Gross margin
- Operating expenses (by category)
- Net income

---

## 7. Delivery Management

### Deliveries

Navigate to: **Delivery Management → Deliveries**

Track the delivery of orders to customers.

#### Delivery Statuses

| Status | Description |
|--------|-------------|
| **Pending** | Order created, awaiting assignment |
| **Assigned** | Driver assigned to delivery |
| **Picked Up** | Driver has collected the order |
| **In Transit** | Order is being delivered |
| **Delivered** | Successfully delivered |
| **Failed** | Delivery attempt failed |
| **Returned** | Order returned to warehouse |

#### Creating a Delivery
1. Click **"New Delivery"**
2. Select the **Sale/Order**
3. Assign a **Driver**
4. Enter delivery **Address**
5. Add any **Notes**
6. Click **"Create"**

#### Updating Delivery Status
1. Open the delivery record
2. Change the **Status**
3. Add any notes
4. Save changes

### Drivers

Navigate to: **Delivery Management → Drivers**

Manage your delivery drivers:
- **Name** - Driver's full name
- **Phone** - Contact number
- **License Number** - Driver's license
- **Active Status** - Enable/disable driver availability

---

## 8. Fleet Management

### Vehicles

Navigate to: **Fleet Management → Vehicles**

Track and manage your fleet of vehicles.

#### Vehicle Information
- **Registration/Plate Number** - Vehicle identification
- **Make/Model** - Vehicle brand and model
- **Year** - Manufacturing year
- **Current Mileage** - Odometer reading
- **Assigned Driver** - Primary driver
- **Status** - Active/Inactive/In Maintenance

### Service Types

Navigate to: **Fleet Management → Service Types**

Define types of maintenance/service for your vehicles:
- Oil Change
- Tire Rotation
- Brake Service
- General Inspection
- Major Service
- etc.

#### Service Type Settings
- **Name** - Service name
- **Description** - What the service includes
- **Recommended Interval (km)** - Mileage between services
- **Recommended Interval (months)** - Time between services
- **Estimated Duration** - How long the service takes
- **Estimated Cost** - Average service cost

### Service Requests

Navigate to: **Fleet Management → Service Requests**

Create and track maintenance requests:

1. Click **"New Request"**
2. Select **Vehicle**
3. Select **Service Type**
4. Enter **Reported Issues**
5. Set **Priority** (Low/Medium/High/Urgent)
6. Click **"Create"**

### Service Records

Navigate to: **Fleet Management → Service Records**

Complete history of all vehicle maintenance:
- Service date
- Vehicle
- Service type
- Cost
- Mileage at service
- Notes/findings

---

## 9. Reports

### Sales Report

Navigate to: **Reports → Sales Report**

Comprehensive sales analysis:
- Filter by period (Today, Week, Month, Year, Custom)
- View by customer
- Export to CSV

### Inventory In/Out Report

Navigate to: **Reports → Inventory In/Out**

Track inventory movements:
- Products added (from purchases)
- Products sold (from sales)
- Stock adjustments
- Export to CSV

### Products Report

Navigate to: **Reports → Products Report**

Product performance analysis:
- Filter by category
- Filter by supplier
- View stock levels
- Export to CSV

### Aging Report

Navigate to: **Reports → Aging Report**

Track overdue payments:

#### Accounts Receivable (Customers)
Money owed TO you by customers:
- Current (not yet due)
- 1-30 Days overdue
- 31-60 Days overdue
- 61-90 Days overdue
- Over 90 Days overdue

#### Accounts Payable (Suppliers)
Money YOU owe to suppliers with the same aging buckets.

### Driver KPI Dashboard

Navigate to: **Reports → Driver KPIs**

Track driver performance:
- Total deliveries
- Successful deliveries
- Failed deliveries
- On-time rate
- Average delivery time

---

## 10. User Management & Security

### Users

Navigate to: **Authentication → Users**

Manage system users:
- Add new users
- Edit user information
- Reset passwords
- Assign roles

### Roles & Permissions

Navigate to: **Authentication → Roles**

Control access to system features:

#### Default Roles
- **Super Admin** - Full system access
- **Admin** - Administrative access
- **Manager** - Management functions
- **Cashier** - POS and basic sales
- **Driver** - Limited delivery access

#### Permission Categories
- View, Create, Edit, Delete for each module
- Access to reports
- Access to financial data
- User management

---

## 11. Tips & Best Practices

### Daily Operations

1. **Start of Day**
   - Log in and check the Dashboard for alerts
   - Review any overdue payments (Aging Report)
   - Check low stock items

2. **During the Day**
   - Process sales through POS
   - Record any expenses immediately
   - Update delivery statuses

3. **End of Day**
   - Review daily sales (Sales Report)
   - Ensure all deliveries are updated
   - Back up critical data

### Inventory Management

- **Regular Stock Checks** - Verify physical stock matches system
- **Record All Purchases** - Every stock addition should be a purchase
- **Set Reorder Points** - Monitor low stock alerts
- **Categorize Products** - Use categories for easy navigation

### Financial Best Practices

- **Record Expenses Daily** - Don't let them pile up
- **Categorize Properly** - Use appropriate expense categories
- **Review P&L Weekly** - Monitor profitability trends
- **Follow Up on Receivables** - Use aging reports

### Customer Management

- **Collect Contact Info** - Enables follow-up and marketing
- **Set Payment Terms** - Be clear about payment expectations
- **Track Credit Sales** - Monitor accounts receivable
- **Use Quotations** - For large orders before commitment

### Fleet & Delivery

- **Regular Maintenance** - Follow service schedules
- **Track Mileage** - Update vehicle odometers
- **Driver Accountability** - Use KPI reports
- **Route Planning** - Optimize delivery routes

---

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl + K` | Open search |
| `Esc` | Close modals |
| `Enter` | Confirm actions |

---

## Getting Help

For technical support or questions:
1. Check this user guide first
2. Contact your system administrator
3. Report bugs through proper channels

---

## Version Information

- **System:** Tri-E Trading Operating System (TOS)
- **Built with:** Laravel 12, Filament v4, Livewire v3
- **Documentation Version:** 1.0.0
- **Last Updated:** January 2026

---

*This guide is subject to updates as new features are added to the system.*
