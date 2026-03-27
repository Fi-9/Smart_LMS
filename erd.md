Gas 🔥 kita lanjut ke bagian paling penting: **ERD + Database Detail + Migration Laravel siap pakai**

Gua bikin ini **rapi + scalable + siap coding**.

---

# 🧱 1. ERD (Entity Relationship Diagram)

## 📊 Struktur Relasi

```text
users
 └── role

categories
 └── books

racks
 └── books

books
 └── qr_codes (optional)
```

---

## 🔗 Relasi Inti

* 1 Category → banyak Books
* 1 Rack → banyak Books
* 1 Book → 1 QR Code
* 1 User → punya role

---

# 🗄️ 2. Struktur Database Detail

---

## 📚 Table: `books`

```sql
id (bigint, PK)
title (string)
author (string)
isbn (string, nullable)
category_id (FK)
rack_id (FK)
position_code (string) -- contoh: A1, B2
cover_url (string, nullable)
qr_code (string)
status (enum: available, borrowed)
created_at
updated_at
```

---

## 🧱 Table: `racks`

```sql
id
name (string) -- Rak A
rows (int)
columns (int)
created_at
```

---

## 🏷️ Table: `categories`

```sql
id
name
created_at
```

---

## 👤 Table: `users`

(Pakai bawaan Laravel + tambah role)

```sql
role (enum: admin, staff)
```

---

## 📌 Optional (Kalau mau advanced)

### Table: `borrowings`

```sql
id
book_id
user_id
borrowed_at
returned_at
status
```

---

# 🧠 3. ERD (Versi Penjelasan Simple)

```text
[users]
   ↓
[books] ← [categories]
   ↓
[racks]
```

---

# ⚙️ 4. Migration Laravel (SIAP COPY 🔥)

---

## 📁 create_categories_table

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

---

## 📁 create_racks_table

```php
Schema::create('racks', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->integer('rows');
    $table->integer('columns');
    $table->timestamps();
});
```

---

## 📁 create_books_table

```php
Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('author');
    $table->string('isbn')->nullable();

    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('rack_id')->constrained()->cascadeOnDelete();

    $table->string('position_code'); // contoh: A1
    $table->string('cover_url')->nullable();
    $table->string('qr_code');

    $table->enum('status', ['available', 'borrowed'])->default('available');

    $table->timestamps();
});
```

---

## 📁 update_users_table (role)

```php
Schema::table('users', function (Blueprint $table) {
    $table->enum('role', ['admin', 'staff'])->default('staff');
});
```

---

## 📁 create_borrowings_table (optional)

```php
Schema::create('borrowings', function (Blueprint $table) {
    $table->id();

    $table->foreignId('book_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();

    $table->timestamp('borrowed_at');
    $table->timestamp('returned_at')->nullable();

    $table->enum('status', ['borrowed', 'returned']);

    $table->timestamps();
});
```

---

# 🧩 5. Model Laravel (Relasi)

---

## 📚 Book.php

```php
class Book extends Model
{
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'category_id',
        'rack_id',
        'position_code',
        'cover_url',
        'qr_code',
        'status'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function rack()
    {
        return $this->belongsTo(Rack::class);
    }
}
```

---

## 🧱 Rack.php

```php
class Rack extends Model
{
    protected $fillable = ['name', 'rows', 'columns'];

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
```

---

## 🏷️ Category.php

```php
class Category extends Model
{
    protected $fillable = ['name'];

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
```

---

# 🔳 6. Logic Position (RAK PINTAR)

Contoh:

```text
Rak A
Rows: 3
Columns: 3
```

Auto generate:

```text
A1 A2 A3
B1 B2 B3
C1 C2 C3
```

👉 Lu bisa bikin helper:

```php
function generatePositions($rows, $cols) {
    $positions = [];
    for ($i = 0; $i < $rows; $i++) {
        for ($j = 1; $j <= $cols; $j++) {
            $positions[] = chr(65 + $i) . $j;
        }
    }
    return $positions;
}
```

---

# 🔳 7. QR Code Generation (Laravel)

```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$qr = QrCode::format('png')
    ->size(300)
    ->generate(url('/book/' . $book->id));
```

---

# 🚀 8. Next Step (INI PENTING)

Sekarang lu udah punya:
✅ Struktur database
✅ Relasi
✅ Migration
✅ Model

---

## 🔥 Saran gua:

Langsung lanjut:

1. CRUD Books
2. CRUD Racks
3. QR generator
4. Scan QR → route detail

---

Kalau lu mau, next gua bisa bikinin:

👉 🎨 **UI Dashboard Tailwind (admin panel modern)**
👉 🔌 **API ISBN auto-fill (Google Books ready)**
👉 📱 **Halaman scan QR langsung jalan**

Tinggal bilang:
**"lanjut bang ke UI dashboard"** 🚀
