<?php $__env->startSection('content'); ?>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Borrowings</h1>
            <p class="page-subtitle">Kelola peminjaman dan pengembalian buku perpustakaan.</p>
        </div>
    </div>

    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mb-6 shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-6 shadow-md']); ?>
        <form method="GET" action="<?php echo e(route('borrowings.index')); ?>" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="form-label">Search</label>
                <input name="search" value="<?php echo e($filters['search']); ?>" type="text" placeholder="🔍 Nama peminjam atau judul..." class="form-input">
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="">All</option>
                    <option value="borrowed" <?php if($filters['status'] === 'borrowed'): echo 'selected'; endif; ?>>Borrowed</option>
                    <option value="late" <?php if($filters['status'] === 'late'): echo 'selected'; endif; ?>>Late</option>
                    <option value="returned" <?php if($filters['status'] === 'returned'): echo 'selected'; endif; ?>>Returned</option>
                </select>
            </div>
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>Apply <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            <a href="<?php echo e(route('borrowings.index')); ?>" class="inline-flex items-center rounded-lg border border-border bg-white px-4 py-2 text-sm text-gray-600 transition hover:bg-gray-50">Reset</a>
        </form>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>

    <?php if($borrowings->isEmpty()): ?>
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'shadow-md']); ?>
            <div class="py-10 text-center">
                <p class="text-4xl">📋</p>
                <p class="mt-2 text-sm font-medium text-gray-700">Belum ada peminjaman</p>
                <p class="mt-1 text-xs text-gray-500">Pinjamkan buku melalui halaman Books → pilih buku → "Borrow Book".</p>
                <a href="<?php echo e(route('books.index')); ?>" class="mt-3 inline-flex items-center gap-1 rounded-lg bg-primary-800 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">📚 Ke Halaman Books</a>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'shadow-md']); ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="p-3 text-left text-xs font-semibold text-gray-500">Book</th>
                            <th class="p-3 text-left text-xs font-semibold text-gray-500">Borrower</th>
                            <th class="p-3 text-left text-xs font-semibold text-gray-500">Borrowed</th>
                            <th class="p-3 text-left text-xs font-semibold text-gray-500">Due</th>
                            <th class="p-3 text-left text-xs font-semibold text-gray-500">Status</th>
                            <th class="p-3 text-right text-xs font-semibold text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $borrowings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $borrowing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $isLate = $borrowing->isLate();
                                $statusClass = match($borrowing->status->value) {
                                    'borrowed' => $isLate ? 'bg-red-100 text-red-700 ring-1 ring-red-200' : 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
                                    'late' => 'bg-red-100 text-red-700 ring-1 ring-red-200',
                                    'returned' => 'bg-primary-100 text-primary-700 ring-1 ring-primary-200',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                                $statusLabel = $isLate && $borrowing->status->value === 'borrowed' ? 'Late' : ucfirst($borrowing->status->value);
                            ?>
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50" id="borrowing-row-<?php echo e($borrowing->id); ?>">
                                <td class="p-3">
                                    <p class="font-semibold text-gray-900"><?php echo e($borrowing->book->title); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e($borrowing->book->author); ?></p>
                                </td>
                                <td class="p-3">
                                    <?php if($borrowing->member): ?>
                                        <a href="<?php echo e(route('members.show', $borrowing->member)); ?>" class="font-medium text-primary-700 hover:underline"><?php echo e($borrowing->borrower_display); ?></a>
                                        <p class="text-xs text-gray-500"><?php echo e($borrowing->member->nis); ?></p>
                                    <?php else: ?>
                                        <span class="font-medium text-gray-800"><?php echo e($borrowing->borrower_display); ?></span>
                                        <p class="text-xs text-amber-600">Unlinked</p>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-gray-600"><?php echo e($borrowing->borrowed_at->format('d M Y')); ?></td>
                                <td class="p-3">
                                    <span class="<?php echo e($isLate ? 'font-semibold text-red-600' : 'text-gray-600'); ?>">
                                        <?php echo e($borrowing->due_date->format('d M Y')); ?>

                                    </span>
                                    <?php if($isLate && $borrowing->isActive()): ?>
                                        <p class="text-[10px] font-bold text-red-500"><?php echo e($borrowing->due_date->diffForHumans()); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                                </td>
                                <td class="p-3 text-right">
                                    <?php if($borrowing->isActive()): ?>
                                        <button
                                            type="button"
                                            data-return-btn
                                            data-return-url="<?php echo e(route('borrowings.return', $borrowing)); ?>"
                                            data-borrowing-id="<?php echo e($borrowing->id); ?>"
                                            class="inline-flex items-center gap-1 rounded-lg bg-primary-800 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary-700"
                                        >
                                            ↩️ Return
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Returned <?php echo e($borrowing->returned_at?->format('d M')); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>

        <div class="mt-4">
            <?php echo e($borrowings->links()); ?>

        </div>
    <?php endif; ?>

    <div id="return-toast" class="fixed bottom-6 right-6 z-50 hidden rounded-xl border border-primary-200 bg-primary-50 px-5 py-3.5 text-sm font-medium text-primary-800 shadow-lg">
        Returned.
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const toast = document.getElementById('return-toast');
            const returnButtons = document.querySelectorAll('[data-return-btn]');

            const showToast = (message, isError) => {
                toast.textContent = message;
                toast.className = isError
                    ? 'fixed bottom-6 right-6 z-50 rounded-xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm font-medium text-red-800 shadow-lg'
                    : 'fixed bottom-6 right-6 z-50 rounded-xl border border-primary-200 bg-primary-50 px-5 py-3.5 text-sm font-medium text-primary-800 shadow-lg';
                toast.classList.remove('hidden');
                window.setTimeout(function() { toast.classList.add('hidden'); }, 3000);
            };

            returnButtons.forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    if (!confirm('Konfirmasi pengembalian buku?')) return;

                    btn.disabled = true;
                    btn.innerHTML = '⏳ Processing...';

                    try {
                        const response = await fetch(btn.dataset.returnUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) throw new Error('Return failed');
                        const data = await response.json();

                        showToast('✅ ' + data.message);
                        window.setTimeout(function() { window.location.reload(); }, 1000);
                    } catch (error) {
                        showToast('❌ Return failed.', true);
                        btn.disabled = false;
                        btn.innerHTML = '↩️ Return';
                    }
                });
            });
        })();
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Downloads\Smart_LMS\resources\views/borrowings/index.blade.php ENDPATH**/ ?>