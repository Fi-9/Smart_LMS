<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - Smart Library</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
</head>
<body class="min-h-screen bg-slate-100 p-6 text-slate-900">
    <main class="mx-auto max-w-xl rounded-xl bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold">Scan Book QR</h1>
        <p class="mt-1 text-sm text-slate-600">Arahkan kamera ke QR code buku untuk membuka detail.</p>
        <div id="reader" class="mt-4"></div>
        <p id="scan-error" class="mt-3 text-sm text-red-600"></p>
    </main>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        const errorContainer = document.getElementById('scan-error');
        const scanner = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 }, false);

        scanner.render(
            (decodedText) => {
                window.location.href = decodedText;
            },
            () => {}
        );

        window.addEventListener('error', () => {
            errorContainer.textContent = 'Unable to initialize camera scanner.';
        });
    </script>
</body>
</html>

<?php /**PATH /mnt/data/Smart_LMS/resources/views/scanner/index.blade.php ENDPATH**/ ?>