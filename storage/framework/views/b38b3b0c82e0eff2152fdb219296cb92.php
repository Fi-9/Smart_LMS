<?php $__env->startSection('content'); ?>
    <div class="mb-6">
        <h1 class="page-title">Import Books</h1>
        <p class="page-subtitle">CSV import atau manual input dengan ISBN autofill.</p>
    </div>

    <?php
        $manualFieldNames = ['title', 'author', 'isbn', 'category_id', 'rack_id', 'cover_url'];
        $hasManualErrors = collect($manualFieldNames)->contains(fn ($name) => $errors->has($name));
    ?>

    
    <div class="mb-5 flex gap-1 rounded-xl bg-gray-100 p-1" style="width: fit-content">
        <button type="button" data-tab-trigger="csv" class="rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">📄 CSV Import</button>
        <button type="button" data-tab-trigger="manual" class="rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">✏️ Manual Input</button>
    </div>

    
    <div data-tab-panel="csv" class="<?php echo e($hasManualErrors ? 'hidden' : ''); ?>">
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <h2 class="section-title mb-3">📄 Upload CSV File</h2>
            <form method="POST" action="<?php echo e(route('books.import.preview')); ?>" enctype="multipart/form-data" class="space-y-3" data-loading-form>
                <?php echo csrf_field(); ?>
                <input type="file" name="file" class="form-input" required>
                <progress class="hidden h-2 w-full overflow-hidden rounded bg-gray-100 [&::-webkit-progress-bar]:bg-gray-100 [&::-webkit-progress-value]:bg-primary-600 [&::-moz-progress-bar]:bg-primary-600" max="100"></progress>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>Upload & Preview <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
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

        <?php if($import_summary): ?>
            <div class="mt-6 animate-slide-up rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm text-primary-800">
                ✅ Imported: <?php echo e($import_summary['imported']); ?> | Skipped: <?php echo e($import_summary['skipped']); ?>

                <?php if(!empty($import_summary['skipped_reasons'])): ?>
                    <ul class="mt-2 list-inside list-disc text-xs text-primary-900">
                        <?php $__currentLoopData = $import_summary['skipped_reasons']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reason => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($reason); ?> (<?php echo e($count); ?>)</li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if($preview): ?>
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mt-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-6']); ?>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-900">Preview</h2>
                    <p class="text-xs text-gray-500">
                        Total: <?php echo e($preview['summary']['total_rows']); ?> |
                        Valid: <span class="text-primary-700"><?php echo e($preview['summary']['valid_rows']); ?></span> |
                        Invalid: <span class="text-red-600"><?php echo e($preview['summary']['invalid_rows']); ?></span>
                    </p>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Row</th>
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Title</th>
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Author</th>
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Errors</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $preview['analyzed_rows']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="border-t border-gray-100 <?php echo e($row['is_valid'] ? 'bg-white' : 'bg-red-50'); ?>">
                                    <td class="p-2.5"><?php echo e($row['row']); ?></td>
                                    <td class="p-2.5 font-medium"><?php echo e($row['data']['title']); ?></td>
                                    <td class="p-2.5"><?php echo e($row['data']['author']); ?></td>
                                    <td class="p-2.5">
                                        <?php if($row['is_valid']): ?>
                                            <span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700">Valid</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Error</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-2.5 text-xs text-red-600"><?php echo e($row['errors'] ? implode('; ', $row['errors']) : '-'); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="<?php echo e(route('books.import.commit')); ?>" class="mt-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="preview_token" value="<?php echo e($preview['preview_token']); ?>">
                    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'success','disabled' => $preview['summary']['valid_rows'] === 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'success','disabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($preview['summary']['valid_rows'] === 0)]); ?>Confirm Import (<?php echo e($preview['summary']['valid_rows']); ?> rows) <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
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
        <?php endif; ?>
    </div>

    
    <div data-tab-panel="manual" class="<?php echo e($hasManualErrors ? '' : 'hidden'); ?>">
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <h2 class="section-title mb-4">✏️ Manual Book Entry</h2>
            <form method="POST" action="<?php echo e(route('books.import.manual')); ?>" class="space-y-4">
                <?php echo csrf_field(); ?>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="isbn-input" class="form-label">ISBN / Scan Input (optional)</label>
                        <div class="flex gap-2">
                            <input id="isbn-input" name="isbn" value="<?php echo e(old('isbn')); ?>" type="text" class="form-input" placeholder="Scan or type ISBN">
                            <button id="isbn-lookup-btn" type="button" class="inline-flex items-center gap-1 rounded-lg border border-border bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-primary-50 hover:text-primary-700">
                                🔍 Fetch
                            </button>
                        </div>
                        <?php $__errorArgs = ['isbn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <p id="isbn-lookup-status" class="mt-1 text-xs text-gray-500"></p>
                    </div>

                    <div>
                        <label for="title-input" class="form-label">Title</label>
                        <input id="title-input" name="title" value="<?php echo e(old('title')); ?>" type="text" class="form-input" required>
                        <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="author-input" class="form-label">Author</label>
                        <input id="author-input" name="author" value="<?php echo e(old('author')); ?>" type="text" class="form-input" required>
                        <?php $__errorArgs = ['author'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="category-name-input" class="form-label">Category</label>
                        <input list="category-list" id="category-name-input" name="category_name" value="<?php echo e(old('category_name')); ?>" type="text" class="form-input" required placeholder="Type or select category">
                        <datalist id="category-list">
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($category->name); ?>"></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </datalist>
                        <?php $__errorArgs = ['category_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="rack-id-input" class="form-label">Rack (optional)</label>
                        <select id="rack-id-input" name="rack_id" class="form-input">
                            <option value="">Auto Assign</option>
                            <?php $__currentLoopData = $racks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rack): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($rack->id); ?>" <?php if((string) old('rack_id') === (string) $rack->id): echo 'selected'; endif; ?>><?php echo e($rack->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['rack_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="md:col-span-2">
                        <label for="cover-url-input" class="form-label">Cover URL (optional)</label>
                        <input id="cover-url-input" name="cover_url" value="<?php echo e(old('cover_url')); ?>" type="url" class="form-input" placeholder="https://...">
                        <?php $__errorArgs = ['cover_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'success']); ?>💾 Save Book <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
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
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        (() => {
            const tabTriggers = document.querySelectorAll('[data-tab-trigger]');
            const tabPanels = document.querySelectorAll('[data-tab-panel]');

            const activeTabClass = ['bg-white', 'text-primary-800', 'shadow-sm', 'font-semibold'];
            const inactiveTabClass = ['text-gray-500', 'hover:text-gray-700'];

            const setActiveTab = (name) => {
                tabPanels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.tabPanel !== name);
                });

                tabTriggers.forEach((trigger) => {
                    const isActive = trigger.dataset.tabTrigger === name;
                    activeTabClass.forEach(c => trigger.classList.toggle(c, isActive));
                    inactiveTabClass.forEach(c => trigger.classList.toggle(c, !isActive));
                });
            };

            tabTriggers.forEach((trigger) => {
                trigger.addEventListener('click', () => setActiveTab(trigger.dataset.tabTrigger));
            });

            const manualPanel = document.querySelector('[data-tab-panel="manual"]');
            setActiveTab(manualPanel.classList.contains('hidden') ? 'csv' : 'manual');

            // ISBN Lookup
            const lookupButton = document.getElementById('isbn-lookup-btn');
            const statusNode = document.getElementById('isbn-lookup-status');
            const isbnInput = document.getElementById('isbn-input');
            const titleInput = document.getElementById('title-input');
            const authorInput = document.getElementById('author-input');
            const coverUrlInput = document.getElementById('cover-url-input');
            const csrfToken = '<?php echo e(csrf_token()); ?>';
            const lookupUrl = "<?php echo e(route('books.import.isbn-lookup')); ?>";

            if (!lookupButton) return;

            lookupButton.addEventListener('click', async () => {
                const isbn = isbnInput.value.trim();
                if (isbn === '') {
                    statusNode.textContent = 'ISBN is required for lookup.';
                    statusNode.className = 'mt-1 text-xs text-red-600';
                    return;
                }

                lookupButton.disabled = true;
                lookupButton.innerHTML = '⏳ Fetching...';
                statusNode.textContent = 'Fetching metadata...';
                statusNode.className = 'mt-1 text-xs text-gray-500';

                try {
                    const response = await fetch(lookupUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ isbn }),
                    });

                    if (!response.ok) {
                        const error = await response.json();
                        throw new Error(error.message || 'Lookup failed');
                    }

                    const data = await response.json();
                    titleInput.value = data.title ?? titleInput.value;
                    authorInput.value = data.author ?? authorInput.value;
                    coverUrlInput.value = data.cover_url ?? coverUrlInput.value;

                    statusNode.textContent = `✅ Metadata loaded from ${data.source ?? 'provider'}.`;
                    statusNode.className = 'mt-1 text-xs text-primary-700 font-medium';
                } catch (error) {
                    statusNode.textContent = error.message;
                    statusNode.className = 'mt-1 text-xs text-red-600';
                } finally {
                    lookupButton.disabled = false;
                    lookupButton.innerHTML = '🔍 Fetch';
                }
            });
        })();
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Smart_LMS\resources\views/books/import.blade.php ENDPATH**/ ?>