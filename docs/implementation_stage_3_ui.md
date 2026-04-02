# Smart Library - UI Stage

## Delivered UI Modules
- Bulk Import UI:
  - Upload CSV
  - Preview valid/invalid rows
  - Error row highlight (red)
  - Confirm import
  - Import summary
  - Progress bar during preview submit
- Dashboard UI:
  - Cards: total books, total racks, total categories
  - Simple charts: books per rack, books per category
- Book List UI:
  - Clean table
  - Search + filter (category, rack, status)
  - Status badge (`available`, `borrowed`)

## UI Rules Applied
- Blade + Tailwind
- No inline CSS
- Reusable components
- Data supplied from controller/service
- No heavy logic in views

## Main Files
- Layout and components:
  - `resources/views/components/layouts/app.blade.php`
  - `resources/views/components/ui/*.blade.php`
- Pages:
  - `resources/views/dashboard/index.blade.php`
  - `resources/views/imports/books.blade.php`
  - `resources/views/books/index.blade.php`
- Web controllers:
  - `app/Http/Controllers/Web/*`
- Routes:
  - `routes/web.php`

