# Smart Library - Implementation Stage 2

## Scope Delivered
- Bulk import CSV with strict flow:
  - `preview` first (validation only, no insert)
  - `commit` using `preview_token`
- Queue-based QR generation (asynchronous only).
- Dashboard statistics endpoint.

## New Endpoints
- `POST /api/books/import/preview`
- `POST /api/books/import/commit`
- `GET /api/dashboard/stats`

## Bulk Import Rules Enforced
- No insert before preview validation.
- Preview token required for commit.
- Duplicate ISBN handled:
  - duplicate in CSV rejected at preview
  - duplicate existing DB rejected at preview
- Default rack assignment if `rack_id` empty.
- Validation is mandatory for every row before import.

## QR Queue
- QR generation moved to job:
  - `GenerateBookQrCodeJob`
- Dispatch is `afterCommit()`.
- No synchronous QR generation in book creation flow.

## Dashboard Stats
- Total books, categories, racks
- Available vs borrowed books
- Books grouped by category
- Books grouped by rack

