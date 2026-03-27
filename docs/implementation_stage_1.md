# Smart Library - Implementation Stage 1

## Scope Completed
- Laravel 11 project bootstrap (PHP 8.2+).
- Database schema core: `users.role`, `categories`, `racks`, `books`.
- Enforced rack position uniqueness in DB:
  - `unique(['rack_id', 'position_code'])`.
- QR path persisted as file path (`qr_code_path`) using format:
  - `/storage/qrcodes/book-{id}.png`.
- Service Layer + Repository Pattern + clean controllers.
- ISBN lookup service with fallback:
  - Google Books -> Open Library.
- Public QR result page:
  - `/book/{id}`.
- QR scanner page:
  - `/scan`.

## Architecture Applied
- `Controllers`: only request/response orchestration.
- `Services`: business logic.
- `Repositories`: data access abstraction.
- `FormRequest`: input validation.
- `Middleware`: role guard (`admin`, `staff`).

## API Endpoints (Protected by auth + role)
- `POST /api/isbn/lookup`
- `apiResource /api/categories`
- `apiResource /api/racks`
- `apiResource /api/books`

## Core Technical Notes
- Borrowing module intentionally postponed.
- Roles locked to `admin` and `staff`.
- OpenAPI MCP disabled.
- MCP filesystem path aligned to active workspace.

## Verification
- `php artisan migrate:fresh` passed.
- `php artisan test` passed (including DB-level position constraint behavior).

