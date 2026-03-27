<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Library</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center justify-center p-6">
        <section class="w-full rounded-xl bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold">Smart Library</h1>
            <p class="mt-2 text-slate-600">Sistem siap digunakan.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white" href="<?php echo e(route('scanner')); ?>">Scan QR</a>
                <a class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" href="/up">Health Check</a>
            </div>
        </section>
    </main>
</body>
</html>

<?php /**PATH C:\Users\renre\Smart_LMS\resources\views\welcome.blade.php ENDPATH**/ ?>