@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Rack {{ $rack->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Cinema-style placement: click empty slot to assign quickly</p>
        </div>
        <button
            id="auto-assign-btn"
            type="button"
            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
        >
            Auto Assign Unassigned
        </button>
    </div>

    <div class="mb-4">
        <label for="grid-search" class="mb-1 block text-sm text-slate-600">Highlight Book in Grid</label>
        <input
            id="grid-search"
            type="text"
            placeholder="Search title (e.g. Laravel)"
            class="w-full max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
        >
    </div>

    <x-card class="mb-6">
        <h2 class="mb-4 font-semibold text-slate-800">Visual Rack Grid</h2>
        @if(!$has_books_in_rack)
            <div class="mb-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">
                Belum ada buku di rack ini.
            </div>
        @endif

        <div id="rack-grid" class="{{ $grid_class }}">
            @foreach($grid as $cell)
                @if($cell['occupied'])
                    <button
                        type="button"
                        disabled
                        title="{{ $cell['book_title'] }}"
                        data-grid-cell
                        data-position-code="{{ $cell['code'] }}"
                        data-book-id="{{ $cell['book_id'] }}"
                        data-book-title="{{ strtolower($cell['book_title']) }}"
                        class="rounded-lg border border-rose-300 bg-rose-100 p-2 text-center text-xs text-rose-800 opacity-80"
                    >
                        <div class="font-semibold">{{ $cell['code'] }}</div>
                        <div class="mt-1 truncate">{{ $cell['book_title'] }}</div>
                    </button>
                @else
                    <button
                        type="button"
                        title="Klik untuk assign buku"
                        data-slot-button
                        data-grid-cell
                        data-position-code="{{ $cell['code'] }}"
                        class="rounded-lg border border-emerald-300 bg-emerald-100 p-2 text-center text-xs text-emerald-800 transition hover:bg-emerald-200 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                    >
                        <div class="font-semibold">{{ $cell['code'] }}</div>
                        <div class="mt-1">Empty</div>
                    </button>
                @endif
            @endforeach
        </div>
    </x-card>

    <x-card class="mb-6">
        <h2 class="mb-3 font-semibold text-slate-800">Quick Move</h2>
        <form id="quick-move-form" class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <select id="move-book-id" class="rounded-md border border-gray-300 p-2 text-sm">
                <option value="">Select assigned book</option>
                @foreach($assigned_books_in_rack as $book)
                    <option value="{{ $book->id }}" data-title="{{ $book->title }}">{{ $book->title }} ({{ $book->position_code }})</option>
                @endforeach
            </select>

            <select id="move-position-code" class="rounded-md border border-gray-300 p-2 text-sm">
                <option value="">Select target empty slot</option>
                @foreach($empty_positions as $position)
                    <option value="{{ $position }}">{{ $position }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Move</button>
        </form>
    </x-card>

    <div id="assign-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Assign Book to Position</h3>
                <button type="button" data-close-modal class="rounded px-2 py-1 text-sm text-slate-500 hover:bg-slate-100">Close</button>
            </div>

            <p class="mb-3 text-sm text-slate-600">Selected Position: <span id="selected-position" class="font-semibold text-slate-900">-</span></p>

            <form id="assign-form" class="space-y-3">
                <input type="hidden" id="position-code-input" name="position_code">
                <input type="hidden" id="rack-id-input" name="rack_id" value="{{ $rack->id }}">

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" id="unassigned-only-toggle" class="rounded border-gray-300">
                    Show only unassigned books
                </label>

                <div>
                    <label for="book-id-input" class="mb-1 block text-sm text-slate-600">Book</label>
                    <select id="book-id-input" name="book_id" class="w-full rounded-md border border-gray-300 p-2 text-sm" required>
                        <option value="">Select book</option>
                        @foreach($books as $book)
                            <option
                                value="{{ $book->id }}"
                                data-unassigned="{{ $book->isAssigned() ? '0' : '1' }}"
                                data-title="{{ $book->title }}"
                            >
                                {{ $book->title }}
                                @if(!$book->rack_id)
                                    (Unassigned)
                                @else
                                    (Assigned {{ $book->position_code }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-yellow-700">Unassigned = buku belum ditempatkan.</p>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" data-close-modal class="rounded-md border border-gray-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <div id="assign-toast" class="fixed bottom-6 right-6 z-50 hidden rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow">
        Book assigned.
    </div>

    <script>
        (() => {
            const csrfToken = '{{ csrf_token() }}';
            const rackId = '{{ $rack->id }}';
            const assignUrl = '{{ route('books.assign') }}';
            const autoAssignUrl = '{{ route('books.auto-assign') }}';

            const modal = document.getElementById('assign-modal');
            const selectedPositionLabel = document.getElementById('selected-position');
            const positionCodeInput = document.getElementById('position-code-input');
            const rackIdInput = document.getElementById('rack-id-input');
            const assignForm = document.getElementById('assign-form');
            const quickMoveForm = document.getElementById('quick-move-form');
            const moveBookSelect = document.getElementById('move-book-id');
            const movePositionSelect = document.getElementById('move-position-code');
            const gridSearchInput = document.getElementById('grid-search');
            const bookSelect = document.getElementById('book-id-input');
            const unassignedOnlyToggle = document.getElementById('unassigned-only-toggle');
            const autoAssignButton = document.getElementById('auto-assign-btn');
            const toast = document.getElementById('assign-toast');
            const closeButtons = document.querySelectorAll('[data-close-modal]');
            const rackGrid = document.getElementById('rack-grid');

            let activePosition = null;

            const getEmptySlotButtons = () => rackGrid.querySelectorAll('[data-slot-button]');

            const openModal = (positionCode) => {
                activePosition = positionCode;
                selectedPositionLabel.textContent = positionCode;
                positionCodeInput.value = positionCode;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                applyBookFilter();
            };

            const closeModal = () => {
                activePosition = null;
                selectedPositionLabel.textContent = '-';
                positionCodeInput.value = '';
                assignForm.reset();
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                applyBookFilter();
            };

            const showToast = (message, isError = false) => {
                toast.textContent = message;
                toast.className = isError
                    ? 'fixed bottom-6 right-6 z-50 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow'
                    : 'fixed bottom-6 right-6 z-50 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow';
                toast.classList.remove('hidden');
                window.setTimeout(() => toast.classList.add('hidden'), 2000);
            };

            const bindSlotClickEvents = () => {
                getEmptySlotButtons().forEach((button) => {
                    button.addEventListener('click', () => openModal(button.dataset.positionCode));
                });
            };

            const markCellAssigned = (positionCode, bookId, title) => {
                const target = rackGrid.querySelector(`[data-position-code="${positionCode}"]`);
                if (!target) {
                    return;
                }

                target.removeAttribute('data-slot-button');
                target.setAttribute('data-book-id', String(bookId));
                target.setAttribute('data-book-title', String(title).toLowerCase());
                target.disabled = true;
                target.title = title;
                target.className = 'rounded-lg border border-rose-300 bg-rose-100 p-2 text-center text-xs text-rose-800 opacity-80';
                target.innerHTML = `<div class="font-semibold">${positionCode}</div><div class="mt-1 truncate">${title}</div>`;
            };

            const markCellEmpty = (positionCode) => {
                const target = rackGrid.querySelector(`[data-position-code="${positionCode}"]`);
                if (!target) {
                    return;
                }

                target.removeAttribute('data-book-id');
                target.removeAttribute('data-book-title');
                target.setAttribute('data-slot-button', '');
                target.disabled = false;
                target.title = 'Klik untuk assign buku';
                target.className = 'rounded-lg border border-emerald-300 bg-emerald-100 p-2 text-center text-xs text-emerald-800 transition hover:bg-emerald-200 focus:outline-none focus:ring-2 focus:ring-emerald-300';
                target.innerHTML = `<div class="font-semibold">${positionCode}</div><div class="mt-1">Empty</div>`;
            };

            const applyBookFilter = () => {
                const onlyUnassigned = unassignedOnlyToggle.checked;
                const options = Array.from(bookSelect.options).filter((option) => option.value !== '');
                options.forEach((option) => {
                    const isUnassigned = option.dataset.unassigned === '1';
                    option.hidden = onlyUnassigned && !isUnassigned;
                });

                const selected = bookSelect.selectedOptions[0];
                if (selected && selected.hidden) {
                    bookSelect.value = '';
                }
            };

            const updateMoveOptions = () => {
                const emptyCodes = Array.from(getEmptySlotButtons()).map((button) => button.dataset.positionCode);
                const bookOptions = Array.from(rackGrid.querySelectorAll('[data-book-id]'));

                movePositionSelect.innerHTML = '<option value="">Select target empty slot</option>';
                emptyCodes.forEach((code) => {
                    const option = document.createElement('option');
                    option.value = code;
                    option.textContent = code;
                    movePositionSelect.appendChild(option);
                });

                moveBookSelect.innerHTML = '<option value="">Select assigned book</option>';
                bookOptions.forEach((cell) => {
                    const option = document.createElement('option');
                    option.value = cell.dataset.bookId;
                    option.dataset.title = cell.title;
                    option.dataset.positionCode = cell.dataset.positionCode;
                    option.textContent = `${cell.title} (${cell.dataset.positionCode})`;
                    moveBookSelect.appendChild(option);
                });
            };

            const highlightGrid = () => {
                const keyword = gridSearchInput.value.trim().toLowerCase();
                rackGrid.querySelectorAll('[data-grid-cell]').forEach((cell) => {
                    cell.classList.remove('ring-2', 'ring-blue-400');
                    const title = cell.dataset.bookTitle || '';
                    if (keyword !== '' && title.includes(keyword)) {
                        cell.classList.add('ring-2', 'ring-blue-400');
                    }
                });
            };

            const postAssign = async (bookId, positionCode) => {
                const response = await fetch(assignUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        book_id: bookId,
                        rack_id: rackIdInput.value || rackId,
                        position_code: positionCode,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Assign failed');
                }

                return response.json();
            };

            closeButtons.forEach((button) => button.addEventListener('click', closeModal));
            unassignedOnlyToggle.addEventListener('change', applyBookFilter);
            gridSearchInput.addEventListener('input', highlightGrid);

            bindSlotClickEvents();
            applyBookFilter();
            updateMoveOptions();

            assignForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const selectedBookId = bookSelect.value;
                if (!selectedBookId || !activePosition) {
                    return;
                }

                try {
                    const result = await postAssign(selectedBookId, activePosition);
                    const selectedOption = bookSelect.selectedOptions[0];
                    const title = selectedOption?.dataset.title || result.book.title;

                    markCellAssigned(activePosition, result.book.id, title);
                    if (selectedOption) {
                        selectedOption.remove();
                    }
                    closeModal();
                    bindSlotClickEvents();
                    updateMoveOptions();
                    highlightGrid();
                    showToast(`Book assigned to ${result.book.position_code}`);
                } catch (error) {
                    showToast('Assign failed. Slot may already be occupied.', true);
                }
            });

            quickMoveForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const bookId = moveBookSelect.value;
                const targetPosition = movePositionSelect.value;
                const selectedBookOption = moveBookSelect.selectedOptions[0];

                if (!bookId || !targetPosition || !selectedBookOption) {
                    return;
                }

                const oldPosition = selectedBookOption.dataset.positionCode;
                const title = selectedBookOption.dataset.title;

                try {
                    await postAssign(bookId, targetPosition);
                    markCellEmpty(oldPosition);
                    markCellAssigned(targetPosition, Number(bookId), title);
                    bindSlotClickEvents();
                    updateMoveOptions();
                    highlightGrid();
                    showToast(`Book moved to ${targetPosition}`);
                } catch (error) {
                    showToast('Move failed.', true);
                }
            });

            autoAssignButton.addEventListener('click', async () => {
                const response = await fetch(autoAssignUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ limit: 50 }),
                });

                if (!response.ok) {
                    showToast('Auto assign failed.', true);
                    return;
                }

                const data = await response.json();
                showToast(`Auto assigned ${data.assigned_count} book(s). Refreshing...`);
                window.setTimeout(() => window.location.reload(), 900);
            });
        })();
    </script>
@endsection

