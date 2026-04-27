<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print QR - T&J No. 103</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sticker-width: 64mm;
            --sticker-height: 32mm;
        }

        body {
            margin: 0;
            padding: 16px;
            font-family: 'Inter', sans-serif;
            color: #000;
            background: #e2e8f0;
        }

        .toolbar {
            margin-bottom: 16px;
            text-align: center;
        }

        .print-btn {
            border: 0;
            border-radius: 8px;
            background: #0f172a;
            color: #fff;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 600;
        }

        .sheet {
            width: 210mm; /* A4 width */
            padding: 9mm 39mm; /* Adjust margins to center the 2x6 grid */
            margin: 0 auto 16px;
            background: #fff;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            display: grid;
            grid-template-columns: var(--sticker-width) var(--sticker-width);
            grid-auto-rows: var(--sticker-height);
            gap: 2mm 4mm; /* T&J 103 usually has a small gap */
            box-sizing: border-box;
            justify-content: center;
        }

        .sticker {
            width: var(--sticker-width);
            height: var(--sticker-height);
            border: 1px dashed #cbd5e1;
            padding: 2mm;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: #fff;
        }

        .qr-side {
            width: 24mm;
            height: 24mm;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-side img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .info-side {
            width: calc(100% - 24mm);
            padding-left: 2mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .school-name {
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 2px;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-title {
            font-size: 8pt;
            font-weight: 600;
            line-height: 1.1;
            margin-bottom: 3px;
            max-height: 2.2em; /* 2 lines max */
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .book-meta {
            font-size: 7pt;
            font-family: monospace;
            color: #333;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .empty-state {
            text-align: center;
            padding: 20px;
            font-size: 14px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0; /* Zero margin to let CSS handle it */
            }

            body {
                background: none;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                margin: 0;
                box-shadow: none;
                /* Adjust padding to match exact print margins for T&J 103 */
                padding: 13mm 39mm; 
                height: 297mm; /* A4 height */
                gap: 0 4mm;
                align-content: start;
            }

            .sticker {
                border: none; /* remove border for actual print */
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button class="print-btn" type="button" onclick="window.print()">🖨️ Print to T&J No.103</button>
        <p style="font-size: 12px; margin-top: 8px; color: #475569;">Pastikan margin pada pengaturan printer diatur ke <strong>None</strong> atau <strong>Minimum</strong>, dan scale di <strong>100%</strong>.</p>
    </div>

    <?php if($books->isEmpty()): ?>
        <div class="empty-state">No QR items found for current filter.</div>
    <?php else: ?>
        <?php
            // 12 stickers per page (2 columns x 6 rows)
            $chunks = $books->chunk(12);
            $lastChunkIndex = $chunks->count() - 1;
            $schoolName = config('app.name', 'SMK Mustaqbal');
        ?>

        <?php $__currentLoopData = $chunks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chunkIndex => $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="sheet">
                <?php $__currentLoopData = $page; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="sticker">
                        <div class="qr-side">
                            <img src="<?php echo e($book->qr_code ?: $book->qr_code_path); ?>" alt="QR">
                        </div>
                        <div class="info-side">
                            <div class="school-name"><?php echo e($schoolName); ?></div>
                            <div class="book-title"><?php echo e($book->title); ?></div>
                            <div class="book-meta"><?php echo e($book->rack->name ?? 'Rack -'); ?> | <?php echo e($book->position_code ?? 'Pos -'); ?></div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <?php if($chunkIndex !== $lastChunkIndex): ?>
                <div class="page-break"></div>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</body>
</html>
<?php /**PATH /mnt/data/Smart_LMS/resources/views/qr/print-tj103.blade.php ENDPATH**/ ?>