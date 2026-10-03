# StockWise — Multi-Branch Retail Inventory Management

StockWise is a full-stack web application for **Harbourside Hardware**, a fictional three-store retailer (Sydney CBD, Parramatta, Newcastle). Before StockWise, the business tracked stock in a separate spreadsheet at each store, so stock was often missing or double-counted. StockWise gives all branches one live view of stock: per-branch quantities, low-stock alerts, transfers between stores, and a full audit trail of every stock change.

| Layer | Technology |
|---|---|
| Frontend | Vue 3 (Composition API), Vue Router 4, Pinia, Axios, Vite |
| Backend | Laravel 12 (PHP 8.2), Laravel Sanctum (token auth) |
| Database | MySQL / MariaDB (tested on MariaDB 10.4 via XAMPP) |
| Tests | PHPUnit feature tests (in-memory SQLite) |

---

## Quick start

### Prerequisites
- PHP 8.2+ with `pdo_mysql`, plus Composer 2
- Node.js 20+ and npm
- MySQL or MariaDB running locally (XAMPP works)

### 1. Backend API
```bash
cd backend
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# create the database (adjust user/password to suit)
mysql -u root -e "CREATE DATABASE stockwise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed    # tables + demo data
php artisan serve --port=8000 # http://localhost:8000
```

### 2. Frontend SPA
```bash
cd frontend
npm install
cp .env.example .env          # VITE_API_URL=http://localhost:8000/api
npm run dev                   # http://localhost:5173
```

### Demo accounts (password `Password123`)
| Email | Role | Branch |
|---|---|---|
| admin@stockwise.test | Administrator | all branches |
| staff@stockwise.test | Staff | Sydney CBD |
| priya@stockwise.test | Staff | Parramatta |

### Run the tests
```bash
cd backend
php artisan test
```
The tests run against an in-memory SQLite database, so they never touch your MySQL data.

---

## Features

- **Authentication:** register, log in and log out with Sanctum bearer tokens (12-hour expiry). Passwords are bcrypt-hashed, and login and registration are rate limited.
- **Role-based access:**
  - *Admins* manage the catalogue, categories, suppliers, branches and users.
  - *Staff* can view everything, but can only record stock for their own branch.
- **Dashboard:** total products, units on hand, stock value at cost, number of low-stock lines, units per branch, and recent activity.
- **Products:** paginated, searchable, filterable and sortable list; create and edit form with live margin hint; detail page with stock per branch and movement history.
- **Stock levels:** per-branch quantities with "low stock only" filter. Filters are kept in the URL so views can be bookmarked.
- **Stock movements:** stock in (deliveries), stock out (sales), signed adjustments (stocktake/damage), and atomic branch-to-branch transfers.
  - Stock can never go negative.
  - Every change writes an immutable movement record with the balance after the change.
- **Admin CRUD:** categories, suppliers, branches and users, edited in accessible modal dialogs.
- **Responsive and accessible:**
  - Layout collapses to a mobile menu below 800px; light and dark themes follow the OS setting.
  - Skip link, visible focus rings, labelled form controls, errors tied to fields with `aria-describedby`, toasts announced via `aria-live`, and `aria-sort` on sortable tables.

---

## Architecture

```
┌──────────────────────────┐   HTTPS / JSON (REST)   ┌──────────────────────────┐        ┌──────────────┐
│  Vue 3 SPA (Vite)        │  Authorization: Bearer  │  Laravel 12 API          │  PDO   │  MySQL       │
│  views/  → pages         │ ──────────────────────▶ │  routes/api.php          │ ─────▶ │  branches    │
│  components/ → UI parts  │                         │  Middleware: auth, admin │        │  categories  │
│  stores/ (Pinia) → state │ ◀────────────────────── │  FormRequests: validation│ ◀───── │  suppliers   │
│  api/http.js → Axios     │   JSON / errors (4xx)   │  Controllers → Services  │        │  products    │
│  router/ → guards        │                         │  Eloquent models         │        │  stock_levels│
└──────────────────────────┘                         └──────────────────────────┘        │  stock_movem.│
        presentation                                     application logic               │  users       │
                                                                                          └──────────────┘
                                                                                             data layer
```

- **Presentation** (`frontend/src`):
  - Views render pages.
  - Pinia stores hold the auth session, cached lookups and toasts.
  - `api/http.js` adds the auth token to every request and turns a 401 response into a redirect to the login page.
- **Application logic** (`backend/app`):
  - Controllers stay thin.
  - `StockService` holds the core business rule: each stock change runs in a DB transaction, locks the stock row with `SELECT … FOR UPDATE`, rejects negative balances, and logs the movement.
- **Data** (`backend/database`):
  - Migrations define the schema with foreign keys and unique constraints.
  - The seeder loads realistic demo data.

### Data model
```
branches 1─* stock_levels *─1 products *─1 categories
    │                            │    *─1 suppliers
    └──1─* stock_movements *─────┘
users *─1 branches         stock_movements *─1 users
```
- `stock_levels` is unique on (`product_id`, `branch_id`) and holds the current on-hand quantity.
- `stock_movements` is append-only. Each row stores the signed `quantity` change and the `balance_after`.

---

## REST API

All endpoints are under `/api` and return JSON. Validation errors return `422` with an `errors` object. Other error codes:
- `401` for a missing or expired token
- `403` for a role or branch violation
- `404` for an unknown record
- `409` for a conflicting delete

| Method | Endpoint | Access | Purpose |
|---|---|---|---|
| POST | `/auth/register` | public | Create a staff account → token |
| POST | `/auth/login` | public | Log in → token |
| GET | `/auth/me` | user | Current user |
| POST | `/auth/logout` | user | Revoke current token |
| GET | `/dashboard` | user | Totals, low stock, branch totals, recent activity |
| GET | `/products` | user | List (search, category_id, supplier_id, sort, direction, page) |
| GET | `/products/{id}` | user | Detail with stock per branch and recent movements |
| POST/PUT/DELETE | `/products[/{id}]` | admin | Create, update, delete |
| GET | `/categories`, `/suppliers`, `/branches` | user | Lists |
| POST/PUT/DELETE | `/categories`, `/suppliers`, `/branches` | admin | CRUD |
| GET | `/branches/options` | public | Branch names for the registration form |
| GET | `/stock-levels` | user | Per-branch quantities (branch_id, search, low_only) |
| GET | `/stock-movements` | user | Audit log (branch_id, product_id, type) |
| POST | `/stock-movements` | user* | Record in / out / adjustment |
| POST | `/stock-transfers` | user* | Move stock between branches atomically |
| GET/POST/PUT/DELETE | `/users[/{id}]` | admin | User management |

\* Staff are limited to their assigned branch.

---

## Project structure
```
backend/
  app/Http/Controllers/Api/   Auth, Product, Category, Supplier, Branch, User, Stock, Dashboard
  app/Http/Requests/          ProductRequest, StockMovementRequest (validation rules)
  app/Http/Middleware/        EnsureUserIsAdmin
  app/Models/                 Eloquent models + relationships
  app/Services/StockService   transactional stock changes
  database/migrations/        schema
  database/seeders/           demo data
  routes/api.php              REST routes
  tests/Feature/              Auth, Product, Stock API tests
frontend/
  src/api/http.js             Axios instance + error parsing
  src/stores/                 auth, lookups, toast (Pinia)
  src/router/index.js         routes + auth/admin guards
  src/components/             layout, form field, modal, pagination, CRUD page
  src/views/                  pages (dashboard, products, stock, movements, admin)
```
