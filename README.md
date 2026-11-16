# STOCKer ERP — Inventory & Stock Management System

BCA Semester V | ERP-Based CRUD Mini Project

---

> 🎓 submitted as part of BCA Semester V coursework.
>
> **has been presented by**
>
> | Name | Roll Number |
> |---|---|
> | **DINESH PRAJAPAT** | 243501046 |
> | **GAUTAM CHAHAR** | 243501057 |
> | **GAJESH SOLANKI** | 243501053 |
> | **DILIP KHATOR** | 243501044 |

---

## Features
- Session-based administrator login
- Dashboard with product, category, supplier, low-stock and inventory valuation summaries
- Product CRUD with unique SKU validation and search/filter
- Category CRUD
- Supplier CRUD with email validation
- Stock-in and stock-out transactions with transaction history
- Prevents stock-out when available quantity is insufficient
- Low-stock and category valuation reports
- Prepared statements, CSRF tokens, HTML escaping, foreign keys and responsive UI

## Requirements
- XAMPP (Apache, PHP 8+ recommended, MySQL/MariaDB)
- Browser and VS Code
- PHP PDO MySQL extension enabled

## Setup (Windows / XAMPP)
1. Copy the `STOCKer` folder to `C:\xampp\htdocs\STOCKer`.
2. Open XAMPP Control Panel and start **Apache** and **MySQL**.
3. Visit `http://localhost/phpmyadmin`.
4. Select **Import**, choose `database.sql`, and import it.
5. If your MySQL root account has a password, update `DB_PASS` in `config.php`.
6. Open `http://localhost/STOCKer/login.php`.
7. Demo login: `admin` / `Admin@123`.

## First demo workflow
1. Sign in.
2. Add a category and supplier.
3. Add a product with a unique SKU.
4. Search/filter the product, edit it, and demonstrate the update.
5. Record stock-in.
6. Record stock-out. Try an amount greater than available stock to demonstrate validation.
7. Open Reports and show the low-stock list.
8. Demonstrate category/supplier CRUD and the delete confirmation.

## Database tables
- `users`: login accounts
- `categories`: product categories
- `suppliers`: supplier details
- `products`: catalog, prices and current quantity
- `stock_movements`: stock-in/stock-out audit history

## Important academic notes
- This is a classroom demonstration starter, not a production-ready ERP.
- Change the demo password before any real deployment. The supplied demo password hash is intended for `Admin@123`; if login fails on a modified environment, generate a fresh hash with PHP `password_hash('Admin@123', PASSWORD_DEFAULT)` and update the row.
- Product quantity is changed through stock movements in the stock module. Product create accepts opening quantity and records an opening movement.
- Product deletion is restricted by foreign keys if stock history exists.
- For a real deployment, add role/permission management, pagination, database backups, audit logging, HTTPS and stronger operational controls.

## GitHub
Do not commit real credentials, customer data or production database dumps. For the class demo, use only synthetic records. Add your group names and roll numbers to the report before submission.
