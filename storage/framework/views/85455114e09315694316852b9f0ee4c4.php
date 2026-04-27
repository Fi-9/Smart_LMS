<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Print</title>
    <style>
        body {
            margin: 0;
            padding: 16px;
            font-family: Arial, sans-serif;
            color: #0f172a;
        }

        .toolbar {
            margin-bottom: 16px;
        }

        .print-btn {
            border: 0;
            border-radius: 8px;
            background: #0f172a;
            color: #fff;
            padding: 10px 14px;
            font-size: 14px;
            cursor: pointer;
        }

        .sheet {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .sticker {
            border: 1px dashed #94a3b8;
            border-radius: 8px;
            padding: 10px;
            min-height: 180px;
            text-align: center;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .sticker img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .title {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 4px;
            max-height: 2.6em;
            overflow: hidden;
        }

        .meta {
            font-size: 10px;
            color: #475569;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .empty-state {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 18px;
            font-size: 14px;
            color: #64748b;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .sticker {
                border: 1px dashed #cbd5e1;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button class="print-btn" type="button" onclick="window.print()">Print</button>
    </div>

    <?php if($books->isEmpty()): ?>
        <div class="empty-state">No QR items found for current filter.</div>
    <?php else: ?>
        <?php
            $chunks = $books->chunk(16);
            $lastChunkIndex = $chunks->count() - 1;
        ?>

        <?php $__currentLoopData = $chunks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chunkIndex => $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="sheet">
                <?php $__currentLoopData = $page; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="sticker">
                        <img src="<?php echo e($book->qr_code ?: $book->qr_code_path); ?>" alt="QR <?php echo e($book->title); ?>">
                        <div class="title"><?php echo e(\Illuminate\Support\Str::limit($book->title, 36)); ?></div>
                        <div class="meta"><?php echo e($book->rack->name ?? '-'); ?> - <?php echo e($book->position_code ?? '-'); ?></div>
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
<?php /**PATH /mnt/data/Smart_LMS/resources/views/qr/print.blade.php ENDPATH**/ ?>