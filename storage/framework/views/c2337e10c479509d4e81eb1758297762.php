<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Smart Library'); ?></title>
    <meta name="description" content="Smart Library Management System — Kelola koleksi perpustakaan SMK Mustaqbal dengan mudah.">
    <script>
        (() => {
            const storedTheme = localStorage.getItem('smart-library-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = storedTheme ?? (prefersDark ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.dataset.theme = theme;
        })();
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
    <?php
        $routeName = request()->route()?->getName() ?? '';
        $navItems = [
            ['route' => 'dashboard',        'label' => 'Dashboard',     'match' => 'dashboard',          'icon' => 'dashboard'],
            ['route' => 'books.index',       'label' => 'Search Book',   'match' => 'books.index|books.web.show|books.web.panel', 'icon' => 'search'],
            ['route' => 'racks.index',       'label' => 'Library Map',   'match' => 'racks.*',            'icon' => 'map'],
            ['route' => 'categories.index',  'label' => 'Categories',    'match' => 'categories.*',       'icon' => 'tag'],
            ['route' => 'books.import',      'label' => 'Smart Ingest',  'match' => 'books.import*',      'icon' => 'ingest'],
            ['route' => 'borrowings.index',  'label' => 'Borrowing',     'match' => 'borrowings.*|scanner', 'icon' => 'book'],
            ['route' => 'members.index',     'label' => 'Members',       'match' => 'members.*',           'icon' => 'members'],
            ['route' => 'settings.index',    'label' => 'Settings',      'match' => 'settings.*',         'icon' => 'settings'],
        ];

        $breadcrumbs = match (true) {
            request()->routeIs('dashboard') => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
            ],
            request()->routeIs('books.index'), request()->routeIs('books.web.show'), request()->routeIs('books.web.panel') => array_values(array_filter([
                ['label' => 'Search Book', 'url' => route('books.index')],
                request()->route('book') ? ['label' => request()->route('book')->title, 'url' => null] : null,
            ])),
            request()->routeIs('books.import*') => [
                ['label' => 'Smart Ingest', 'url' => route('books.import')],
            ],
            request()->routeIs('categories.*') => [
                ['label' => 'Categories', 'url' => route('categories.index')],
            ],
            request()->routeIs('racks.index') => [
                ['label' => 'Library Map', 'url' => route('racks.index')],
            ],
            request()->routeIs('rooms.show') => array_values(array_filter([
                ['label' => 'Library Map', 'url' => route('racks.index')],
                request()->route('room') ? ['label' => request()->route('room')->name, 'url' => null] : null,
            ])),
            request()->routeIs('racks.show') => array_values(array_filter([
                ['label' => 'Library Map', 'url' => route('racks.index')],
                request()->route('rack')?->room ? ['label' => request()->route('rack')->room->name, 'url' => route('rooms.show', request()->route('rack')->room)] : null,
                request()->route('rack') ? ['label' => request()->route('rack')->name, 'url' => null] : null,
            ])),
            request()->routeIs('borrowings.*') || request()->routeIs('scanner') => [
                ['label' => 'Borrowing', 'url' => route('borrowings.index')],
            ],
            request()->routeIs('settings.*') => [
                ['label' => 'Settings', 'url' => route('settings.index')],
            ],
            request()->routeIs('members.show') => array_values(array_filter([
                ['label' => 'Members', 'url' => route('members.index')],
                request()->route('member') ? ['label' => request()->route('member')->name, 'url' => null] : null,
            ])),
            request()->routeIs('members.*') => [
                ['label' => 'Members', 'url' => route('members.index')],
            ],
            default => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
            ],
        };

        $pageTitle = $breadcrumbs[count($breadcrumbs) - 1]['label'] ?? 'Dashboard';
    ?>

    <div class="relative flex min-h-screen">
        
        <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-black/40 backdrop-blur-sm lg:hidden" onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('hidden');"></div>

        
        <aside id="sidebar" class="lumina-sidebar fixed inset-y-0 left-0 z-40 flex h-screen w-[232px] -translate-x-full flex-col border-r border-sidebar-border bg-sidebar px-4 py-5 transition-transform duration-300 lg:translate-x-0">

            
            <div class="mb-6 flex items-center gap-2.5 px-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-700 text-xs font-black text-white shadow-md">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-bold tracking-tight text-sidebar-foreground">Smart Library</h1>
                    <p class="text-[0.65rem] font-medium text-sidebar-muted">SMK Mustaqbal</p>
                </div>
            </div>

            
            <nav class="flex-1 space-y-1 overflow-y-auto">
                <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $patterns = explode('|', $item['match']);
                        $isActive = collect($patterns)->contains(fn ($p) => request()->routeIs($p));
                    ?>
                    <a
                        href="<?php echo e(route($item['route'])); ?>"
                        class="nav-item group flex items-center gap-3 rounded-xl px-3 py-2.5 text-[0.82rem] font-medium transition-all duration-150
                            <?php echo e($isActive
                                ? 'bg-primary-50 text-primary-800 font-semibold dark:bg-primary-500/15 dark:text-primary-300'
                                : 'text-sidebar-foreground hover:bg-sidebar-hover dark:hover:bg-white/5'); ?>"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg <?php echo e($isActive ? 'text-primary-700 dark:text-primary-300' : 'text-sidebar-muted group-hover:text-sidebar-foreground'); ?>">
                            <?php echo $__env->make('layouts.partials.nav-icon', ['icon' => $item['icon'], 'isActive' => $isActive], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </span>
                        <span><?php echo e($item['label']); ?></span>
                        <?php if($item['icon'] === 'map'): ?>
                            <span class="ml-auto h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>

            
            <div class="mt-4 space-y-3 border-t border-sidebar-border pt-4">
                
                <button
                    type="button"
                    data-theme-toggle
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-[0.82rem] font-medium text-sidebar-foreground transition hover:bg-sidebar-hover dark:hover:bg-white/5"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg text-sidebar-muted">
                        <svg data-theme-icon-sun viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                        <svg data-theme-icon-moon viewBox="0 0 24 24" class="hidden h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    </span>
                    <span data-theme-label>Dark</span> Mode
                </button>

                
                <div class="rounded-xl bg-sidebar-hover/50 px-3 py-3 dark:bg-white/5">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-800 dark:bg-primary-500/20 dark:text-primary-300">
                            <?php echo e(strtoupper(substr(auth()->user()->name, 0, 2))); ?>

                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-sidebar-foreground"><?php echo e(auth()->user()->name); ?></p>
                            <p class="text-[0.65rem] font-medium uppercase tracking-wider text-sidebar-muted"><?php echo e(auth()->user()->role->value); ?></p>
                        </div>
                    </div>
                    <form method="POST" action="<?php echo e(route('logout')); ?>" class="mt-2.5">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="w-full rounded-lg border border-sidebar-border bg-transparent px-3 py-1.5 text-xs font-medium text-sidebar-muted transition hover:bg-sidebar-hover hover:text-sidebar-foreground dark:border-white/10 dark:hover:bg-white/5">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        
        <main class="flex-1 lg:ml-[232px]">
            
            <header class="sticky top-0 z-20 border-b border-border/60 bg-background/80 backdrop-blur-lg">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3 lg:px-8">
                    <div class="flex items-center gap-3">
                        
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-surface text-muted transition hover:text-foreground lg:hidden"
                            onclick="document.getElementById('sidebar').classList.remove('-translate-x-full'); document.getElementById('sidebar-overlay').classList.remove('hidden');"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                        </button>

                        
                        <nav class="flex items-center gap-1.5 text-sm">
                            <?php $__currentLoopData = $breadcrumbs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $crumb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(!$loop->first): ?>
                                    <span class="text-muted">/</span>
                                <?php endif; ?>
                                <?php if(!empty($crumb['url']) && !$loop->last): ?>
                                    <a href="<?php echo e($crumb['url']); ?>" class="font-medium text-muted transition hover:text-primary-700"><?php echo e($crumb['label']); ?></a>
                                <?php else: ?>
                                    <span class="font-semibold text-foreground"><?php echo e($crumb['label']); ?></span>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </nav>
                    </div>

                    
                    <div class="flex items-center gap-2">
                        <span class="hidden items-center gap-1.5 rounded-full border border-border bg-surface px-3 py-1.5 text-xs font-medium text-muted sm:inline-flex">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            System Status: Online
                        </span>
                    </div>
                </div>
            </header>

            
            <div class="mx-auto max-w-7xl px-5 py-6 lg:px-8 lg:py-8">
                <?php if (isset($component)) { $__componentOriginal339c7fedf680433726dbafc2f156956f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal339c7fedf680433726dbafc2f156956f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.toast','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal339c7fedf680433726dbafc2f156956f)): ?>
<?php $attributes = $__attributesOriginal339c7fedf680433726dbafc2f156956f; ?>
<?php unset($__attributesOriginal339c7fedf680433726dbafc2f156956f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal339c7fedf680433726dbafc2f156956f)): ?>
<?php $component = $__componentOriginal339c7fedf680433726dbafc2f156956f; ?>
<?php unset($__componentOriginal339c7fedf680433726dbafc2f156956f); ?>
<?php endif; ?>
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </main>
    </div>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /mnt/data/Smart_LMS/resources/views/layouts/app.blade.php ENDPATH**/ ?>