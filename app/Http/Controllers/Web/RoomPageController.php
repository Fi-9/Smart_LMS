<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Rack;
use App\Services\RackService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomPageController extends Controller
{
    public function __construct(
        private readonly RackService $rackService
    ) {}

    /**
     * Level 1: The Lobby — clean room cards, no rack details.
     */
    public function index(): View
    {
        $rooms = Room::query()
            ->withCount(['racks', 'books'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $unassignedRacks = Rack::query()
            ->whereNull('room_id')
            ->withCount('books')
            ->orderBy('name')
            ->get();

        $stats = [
            'rooms' => $rooms->count(),
            'racks' => Rack::count(),
            'books_mapped' => Rack::withCount('books')->get()->sum('books_count'),
        ];

        return view('racks.index', [
            'rooms' => $rooms,
            'unassigned_racks' => $unassignedRacks,
            'stats' => $stats,
        ]);
    }

    /**
     * Level 2: The Hallway — room detail with its rack cards.
     */
    public function show(Room $room): View
    {
        $room->loadCount(['racks', 'books']);

        $racks = Rack::query()
            ->where('room_id', $room->id)
            ->withCount('books')
            ->orderBy('name')
            ->get();

        $racks->each(function (Rack $rack) {
            $rack->setAttribute('grid_preview', $this->rackService->buildGrid($rack));
        });

        $allRooms = Room::orderBy('sort_order')->get(['id', 'name', 'code']);

        return view('rooms.show', [
            'room' => $room,
            'racks' => $racks,
            'allRooms' => $allRooms,
            'categories' => \App\Models\Category::all()->keyBy('id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:rooms,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'preview', 'inactive'])],
            'accent' => ['required', Rule::in(['emerald', 'sky', 'amber', 'rose', 'violet'])],
        ]);

        $validated['sort_order'] = Room::max('sort_order') + 1;
        Room::create($validated);

        return redirect()->route('racks.index')
            ->with('success', "Ruangan '{$validated['name']}' berhasil dibuat.");
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('rooms', 'code')->ignore($room->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'preview', 'inactive'])],
            'accent' => ['required', Rule::in(['emerald', 'sky', 'amber', 'rose', 'violet'])],
        ]);

        $room->update($validated);

        return redirect()->route('racks.index')
            ->with('success', "Ruangan '{$room->name}' berhasil diperbarui.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->racks()->count() > 0) {
            return redirect()->route('racks.index')
                ->with('error', "Ruangan '{$room->name}' masih memiliki rak. Pindahkan rak ke ruangan lain dulu.");
        }

        $name = $room->name;
        $room->delete();

        return redirect()->route('racks.index')
            ->with('success', "Ruangan '{$name}' berhasil dihapus.");
    }

    /**
     * Suggest the best empty slot across all racks for a given book category.
     */
    public function suggestSlot(Request $request)
    {
        $racks = Rack::query()
            ->with('room:id,name,code')
            ->withCount('books')
            ->get();

        $suggestions = [];
        foreach ($racks as $rack) {
            $grid = $this->rackService->buildGrid($rack);
            $emptySlots = collect($grid)->where('is_full', false);

            if ($emptySlots->isEmpty()) continue;

            $totalCapacity = $rack->rows * $rack->columns * ($rack->capacity_per_slot ?? 1);
            $occupancy = $rack->books_count / max($totalCapacity, 1);

            $suggestions[] = [
                'rack_id' => $rack->id,
                'rack_name' => $rack->name,
                'room' => $rack->room ? "{$rack->room->code} — {$rack->room->name}" : 'Unassigned',
                'empty_slots' => $emptySlots->count(),
                'occupancy_pct' => round($occupancy * 100),
                'suggested_slot' => $emptySlots->first()['code'] ?? null,
            ];
        }

        // Sort by lowest occupancy (most empty first)
        usort($suggestions, fn ($a, $b) => $a['occupancy_pct'] <=> $b['occupancy_pct']);

        return response()->json(array_slice($suggestions, 0, 5));
    }
}
