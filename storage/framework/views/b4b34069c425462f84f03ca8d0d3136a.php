<?php $__env->startSection('content'); ?>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="<?php echo e(route('admin.observability.index')); ?>" class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 flex items-center gap-1">
                    <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Kembali ke Observability
                </a>
            </div>
            <h1 class="page-title text-2xl font-bold tracking-tight text-foreground">API Provider Health Status</h1>
            <p class="page-subtitle text-sm text-muted">Pemantauan real-time untuk status koneksi, latency, dan success rate dari API eksternal.</p>
        </div>
    </div>

    
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 mb-6">
        <?php $__currentLoopData = $providerStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $providerName => $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                // Status calculation
                if ($stat['total'] === 0) {
                    $statusText = 'No Data';
                    $statusColor = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
                    $dotColor = 'bg-gray-400';
                } elseif ($stat['success_rate'] >= 90.0) {
                    $statusText = 'Healthy';
                    $statusColor = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
                    $dotColor = 'bg-emerald-500';
                } elseif ($stat['success_rate'] >= 60.0) {
                    $statusText = 'Degraded';
                    $statusColor = 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
                    $dotColor = 'bg-amber-500';
                } else {
                    $statusText = 'Critical';
                    $statusColor = 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400';
                    $dotColor = 'bg-rose-500';
                }
            ?>

            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'relative overflow-hidden transition-all duration-300 hover:shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'relative overflow-hidden transition-all duration-300 hover:shadow-md']); ?>
                <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-foreground"><?php echo e($providerName); ?></h2>
                        <p class="text-[0.68rem] text-muted">
                            <?php if($providerName === 'Gemini'): ?>
                                AI Image Recognition
                            <?php elseif($providerName === 'GoogleBooks'): ?>
                                Google Books API
                            <?php elseif($providerName === 'OpenLibrary'): ?>
                                Open Library API
                            <?php elseif($providerName === 'Tavily'): ?>
                                Tavily Web Search API
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo e($statusColor); ?>">
                        <span class="h-1.5 w-1.5 rounded-full <?php echo e($dotColor); ?>"></span>
                        <?php echo e($statusText); ?>

                    </span>
                </div>

                <div class="space-y-4">
                    
                    <div class="grid grid-cols-2 gap-2 text-center bg-muted/30 p-2.5 rounded-lg">
                        <div>
                            <p class="text-[0.65rem] uppercase tracking-wider text-muted font-medium">Success Rate</p>
                            <p class="text-lg font-extrabold text-foreground mt-0.5"><?php echo e($stat['success_rate']); ?>%</p>
                        </div>
                        <div>
                            <p class="text-[0.65rem] uppercase tracking-wider text-muted font-medium">Avg Latency</p>
                            <p class="text-lg font-extrabold text-foreground mt-0.5">
                                <?php echo e($stat['avg_latency'] >= 1000 ? number_format($stat['avg_latency'] / 1000, 2) . 's' : $stat['avg_latency'] . 'ms'); ?>

                            </p>
                        </div>
                    </div>

                    
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-muted">Total Requests:</span>
                            <span class="font-bold text-foreground"><?php echo e($stat['total']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Successful calls:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400"><?php echo e($stat['success']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Failed calls:</span>
                            <span class="font-bold text-rose-600 dark:text-rose-400"><?php echo e($stat['failed']); ?></span>
                        </div>
                    </div>

                    
                    <div class="border-t border-border pt-3 space-y-2 text-[0.7rem] text-muted">
                        <div>
                            <p class="font-semibold text-foreground/80 mb-0.5">Last Successful Call:</p>
                            <?php if($stat['last_success']): ?>
                                <p><?php echo e(\Carbon\Carbon::parse($stat['last_success'])->setTimezone('Asia/Jakarta')->format('d M Y H:i:s')); ?></p>
                            <?php else: ?>
                                <p class="italic text-muted">Belum pernah sukses</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="font-semibold text-foreground/80 mb-0.5">Last Failed Call:</p>
                            <?php if($stat['last_failure']): ?>
                                <p class="text-rose-600 dark:text-rose-400 font-medium">
                                    <?php echo e(\Carbon\Carbon::parse($stat['last_failure'])->setTimezone('Asia/Jakarta')->format('d M Y H:i:s')); ?>

                                </p>
                            <?php else: ?>
                                <p class="text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                                    <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Tidak ada error tercatat
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
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
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-900/30 dark:bg-blue-950/10">
        <div class="flex items-start gap-3">
            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </span>
            <div>
                <h3 class="text-xs font-bold text-blue-900 dark:text-blue-300">Bagaimana status ditentukan?</h3>
                <p class="mt-1 text-xs text-blue-800 dark:text-blue-400 leading-relaxed">
                    Status <strong>Healthy</strong> diberikan jika Success Rate berada di atas 90%. Status <strong>Degraded</strong> menunjukkan Success Rate antara 60% sampai 90%, mengindikasikan adanya beberapa limitasi kuota atau gangguan jaringan intermiten. Status <strong>Critical</strong> menunjukkan kegagalan beruntun atau kehabisan kuota API.
                </p>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Downloads\Smart_LMS\resources\views/admin/observability/providers.blade.php ENDPATH**/ ?>