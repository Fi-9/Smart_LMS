<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($book->title); ?> - Smart Library</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
</head>
<body class="min-h-screen bg-slate-100 p-4 text-slate-900 sm:p-6">
    <main class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">Book Location</h1>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700"><?php echo e($book->status->value); ?></span>
        </div>

        <div class="flex gap-4">
            <img
                src="<?php echo e($book->cover_url ?: '/images/default-book-cover.svg'); ?>"
                alt="<?php echo e($book->title); ?>"
                class="h-28 w-20 rounded-md border border-slate-200 object-cover"
            >
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-slate-900"><?php echo e($book->title); ?></h2>
                <p class="text-sm text-slate-600"><?php echo e($book->author); ?></p>
                <p class="mt-2 text-xs text-slate-500">ISBN: <?php echo e($book->isbn ?? '-'); ?></p>
                <p class="text-xs text-slate-500">Category: <?php echo e($book->category->name); ?></p>
            </div>
        </div>

        <div class="mt-5 rounded-xl border border-emerald-100 bg-emerald-50 p-4">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Current Location</p>
            <p class="mt-1 text-xl font-bold text-emerald-800"><?php echo e($book->rack?->name ?? '-'); ?> - <?php echo e($book->position_code ?? 'Unassigned'); ?></p>
            <p class="mt-1 text-xs text-emerald-700">Gunakan kode ini untuk menuju rak.</p>
        </div>
    </main>
</body>
</html>
<?php /**PATH C:\Users\renre\Smart_LMS\resources\views\books\public_show.blade.php ENDPATH**/ ?>