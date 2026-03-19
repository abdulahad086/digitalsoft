# Multi-Branch Smart Inventory & Order Management System

Production-ready **Laravel REST API + Vue 3 SPA** for multi-branch inventory, secure order processing, and reporting.

## Architecture

- **Backend**: Laravel 12, MySQL, Laravel Sanctum (API tokens), RESTful API
- **Frontend**: Vue 3 (Composition API), Vue Router, Pinia, Axios
- **Style**: Service Layer architecture
  - Controllers are thin: input → service call → resource/response
  - Business logic lives in `app/Services`
  - Validation via `FormRequest` in `app/Http/Requests`
  - Serialization via `JsonResource` in `app/Http/Resources`

## Folder structure (backend)

- `app/Http/Controllers/Api/V1/*` — REST controllers (Thin)
- `app/Services/*` — Business Logic & Transaction Handling
- `app/Models/*` — Eloquent Models
- `app/Http/Requests/Api/V1/*` — Strict Validation Rules
- `app/Http/Resources/*` — JSON API Serialization
- `app/Http/Middleware/RoleMiddleware.php` — Role-based Authorization Logic
- `routes/api.php` — API Routing with Middleware Groups

## Database schema (tables)

- `roles` — `id`, `name` (unique)
- `users` — `role_id`, `branch_id`, plus standard Laravel user fields
- `branches` — `name`, `address`, `manager_user_id` (unique)
- `products` — `sku` (unique), pricing, tax %, `is_active`
- `inventories` — `(branch_id, product_id)` unique, `quantity`
- `inventory_movements` — audit log for all stock changes
- `orders` — `branch_id`, `user_id`, totals, `ordered_at`
- `order_items` — line details
- `personal_access_tokens` — Sanctum tokens

Key indexes:
- `products.sku` unique
- `inventories(branch_id, product_id)` unique
- `inventory_movements(branch_id, product_id, created_at)` for history queries
- `orders(branch_id, ordered_at)` for reporting

## Concurrency handling (Overselling Prevention)

Order creation is a critical operation. The system prevents race conditions and overselling using the following strategy:

1.  **Database Transactions**: All operations (inventory check, deduction, order creation, movement logging) are wrapped in a single database transaction.
2.  **Row-Level Locking**: Before checking stock, the system acquires row-level locks on the relevant `inventories` and `products` records using `SELECT ... FOR UPDATE`.
3.  **Deterministic Locking Order**: To prevent deadlocks, involved records are always locked in a deterministic order (by primary key).
4.  **Final Verification**: Stock is re-checked *after* the locks are successfully acquired. Only then is the deduction performed and the transaction committed.

This ensures that even if two users attempt to purchase the last available unit of a product simultaneously, one will block until the other finishes, and the second user will receive an "Insufficient stock" error.

Implementation can be found in `backend/app/Services/OrderService.php`.

## Security

- **Sanctum Authentication**: Bearer tokens are used for API security.
- **Strict RBAC**: Role-based access control is enforced at both the route level (middleware) and the controller level (branch isolation).
- **Validation**: Every write endpoint uses dedicated `FormRequest` classes for strict input validation.
- **CSRF & Rate Limiting**: Standard Laravel protection including API rate limiting on login attempts.
- **HTTP Integrity**: Uses appropriate status codes (201 Created, 403 Forbidden, 422 Validation Error).

## Local installation (PHP + MySQL)

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8+
- Node 18+ (you have Node 22 already)

### Backend setup

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Configure MySQL in `backend/.env`:
- `DB_DATABASE=smart_inventory`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

API base URL:
- `http://localhost:8000/api/v1`

### Frontend setup

```bash
cd web
npm install
npm run dev
```

Set `web/.env` (optional):

```bash
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

## Sample login credentials (seeded)

Password for all: `password`

- **Super Admin**: `superadmin@example.com`
- **Branch Manager**: `manager@example.com`
- **Sales User**: `sales@example.com`

## API overview

Auth:
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`

Super Admin:
- Branch CRUD: `GET/POST/PUT/DELETE /api/v1/branches`
- Product CRUD: `GET/POST/PUT/DELETE /api/v1/products`

Branch Manager:
- `POST /api/v1/inventory/add-stock`
- `POST /api/v1/inventory/adjust-stock`
- `POST /api/v1/inventory/transfer-stock`

Branch Manager + Sales User:
- `GET /api/v1/inventory`
- `GET /api/v1/inventory/movements`
- `POST /api/v1/orders`
- `GET /api/v1/orders`
- `GET /api/v1/orders/{order}`
- `GET /api/v1/reports`

## Known limitations (current baseline)

- Vue UI focuses on core flows; advanced CRUD forms (create/edit in UI) can be expanded further.
- Inventory UI includes listing/filtering; stock adjustment forms are API-ready (endpoints exist) and can be added as additional UI components.

