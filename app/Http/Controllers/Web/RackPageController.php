<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRackPositionRequest;
use App\Http\Requests\StoreRackRequest;
use App\Http\Requests\UpdateRackRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\Rack;
use App\Services\RackPlacementService;
use App\Services\RackService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RackPageController extends Controller
{
    public function __construct(
        private readonly RackService $rackService,
        private readonly RackPlacementService $rackPlacementService
    ) {
    }

    public function index(): View
    {
        $racks = Rack::query()
            ->with(['books:id,title,rack_id,position_code'])
            ->orderBy('name')
            ->get();

        $rackCards = $racks->map(function (Rack $rack) {
            return [
                'rack' => $rack,
                'grid' => $this->rackService->buildGrid($rack),
                'grid_class' => $this->gridClass($rack->columns),
            ];
        });

        return view('racks.index', [
            'rack_cards' => $rackCards,
            'library_rooms' => $this->buildMockRooms($rackCards->values()->all()),
        ]);
    }

    public function store(StoreRackRequest $request)
    {
        $this->rackService->create($request->validated());

        return redirect()->route('racks.index')->with(
            'toast',
            ['type' => 'success', 'message' => 'Rack created.']
        );
    }

    public function show(Request $request, Rack $rack): View|RedirectResponse
    {
        $rack->load(['room:id,name', 'books:id,title,author,status,rack_id,position_code']);

        $accessKey = 'rack_detail_access_'.$rack->id;
        if ($request->integer('from_room') === (int) $rack->room_id) {
            $request->session()->put($accessKey, true);
        } elseif ($rack->room_id && ! $request->session()->get($accessKey)) {
            return redirect()
                ->route('rooms.show', $rack->room)
                ->with('toast', [
                    'type' => 'warning',
                    'message' => 'Buka rak melalui tombol Buka Rak di halaman ruangan.',
                ]);
        }

        $books = Book::query()->orderBy('title')->get(['id', 'title', 'author', 'status', 'rack_id', 'position_code']);
        $assignedBooksInRack = $rack->books()->orderBy('title')->get(['id', 'title', 'author', 'status', 'rack_id', 'position_code']);
        $grid = $this->rackService->buildGrid($rack);
        $availablePositions = collect($grid)
            ->where('is_full', false)
            ->pluck('code')
            ->values();

        return view('racks.show', [
            'rack' => $rack,
            'grid' => $grid,
            'grid_matrix' => $this->rackService->buildGridMatrix($rack),
            'grid_class' => $this->gridClass($rack->columns),
            'positions' => $this->rackService->generatePositions($rack->rows, $rack->columns),
            'books' => $books,
            'unassigned_books' => Book::query()->unassigned()->orderBy('title')->get(['id', 'title', 'author', 'status', 'rack_id', 'position_code']),
            'assigned_books_in_rack' => $assignedBooksInRack,
            'available_positions' => $availablePositions,
            'has_books_in_rack' => $rack->books->isNotEmpty(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function assign(AssignRackPositionRequest $request, Rack $rack)
    {
        $this->rackPlacementService->assign(
            $rack,
            (int) $request->validated('book_id'),
            $request->validated('position_code')
        );

        return redirect()->route('racks.show', $rack)->with(
            'toast',
            ['type' => 'success', 'message' => 'Book placement updated.']
        );
    }

    public function update(UpdateRackRequest $request, Rack $rack)
    {
        $this->rackService->update($rack, $request->validated());

        return redirect()->route('racks.index')->with(
            'toast',
            ['type' => 'success', 'message' => 'Rack updated.']
        );
    }

    public function destroy(Rack $rack)
    {
        $this->rackService->delete($rack);

        return redirect()->route('racks.index')->with(
            'toast',
            ['type' => 'success', 'message' => 'Rack removed.']
        );
    }

    /**
     * Set category for a specific column in a rack (AJAX).
     */
    public function setColumnCategory(Request $request, Rack $rack)
    {
        if ($request->input('category_id') === '') {
            $request->merge(['category_id' => null]);
        }

        $validated = $request->validate([
            'column' => ['nullable', 'integer', 'min:1', 'max:' . $rack->columns],
            'position_code' => ['nullable', 'string', 'max:10'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $positionCode = isset($validated['position_code'])
            ? strtoupper(trim($validated['position_code']))
            : null;

        if ($positionCode !== null) {
            if (! $rack->isValidPosition($positionCode)) {
                abort(422, 'Posisi slot tidak valid.');
            }

            $metadata = $rack->metadata ?? [];
            $slotCategories = $metadata['slot_categories'] ?? [];
            if (! is_array($slotCategories)) {
                $slotCategories = [];
            }
            $slotCategories[$positionCode] = $validated['category_id'];
            $metadata['slot_categories'] = $slotCategories;
            $rack->update(['metadata' => $metadata]);
        } else {
            $categories = $rack->column_categories ?? [];
            $categories[(string) $validated['column']] = $validated['category_id'];
            $rack->update(['column_categories' => $categories]);
        }

        $categoryName = $validated['category_id']
            ? Category::find($validated['category_id'])->name
            : null;

        $payload = [
            'success' => true,
            'column' => $validated['column'] ?? null,
            'position_code' => $positionCode,
            'category_id' => $validated['category_id'],
            'category_name' => $categoryName,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => $positionCode
                ? ($categoryName
                    ? "Slot {$positionCode} diatur ke {$categoryName}."
                    : "Kategori slot {$positionCode} dihapus.")
                : ($categoryName
                    ? "Kolom {$validated['column']} diatur ke {$categoryName}."
                    : "Kategori kolom {$validated['column']} dihapus."),
        ]);
    }

    private function gridClass(int $columns): string
    {
        return match ($columns) {
            1 => 'grid grid-cols-1 gap-2',
            2 => 'grid grid-cols-2 gap-2',
            3 => 'grid grid-cols-3 gap-2',
            4 => 'grid grid-cols-4 gap-2',
            5 => 'grid grid-cols-5 gap-2',
            6 => 'grid grid-cols-6 gap-2',
            default => 'grid grid-cols-3 gap-2',
        };
    }

    private function buildMockRooms(array $rackCards): array
    {
        $roomTemplates = [
            [
                'name' => 'Ruang Referensi',
                'code' => 'RM-01',
                'description' => 'Zona koleksi inti, referensi umum, dan buku yang paling sering dipakai siswa.',
                'status' => 'Aktif',
                'accent' => 'emerald',
            ],
            [
                'name' => 'Area Literasi',
                'code' => 'RM-02',
                'description' => 'Area baca santai, buku pengembangan diri, dan koleksi populer untuk program literasi.',
                'status' => 'Preview',
                'accent' => 'sky',
            ],
            [
                'name' => 'Creative Zone',
                'code' => 'RM-03',
                'description' => 'Zona eksperimen untuk koleksi proyek, jurusan, dan rak kurasi khusus.',
                'status' => 'Preview',
                'accent' => 'amber',
            ],
        ];

        $rooms = array_map(function (array $room): array {
            return [...$room, 'racks' => []];
        }, $roomTemplates);

        foreach ($rackCards as $index => $rackCard) {
            $roomIndex = $index % max(count($rooms), 1);
            $rooms[$roomIndex]['racks'][] = $rackCard;
        }

        foreach ($rooms as $index => $room) {
            $rooms[$index]['rack_count'] = count($room['racks']);
        }

        return $rooms;
    }
}
