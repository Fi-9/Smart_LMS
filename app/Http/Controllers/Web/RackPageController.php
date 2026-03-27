<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRackPositionRequest;
use App\Http\Requests\StoreRackRequest;
use App\Http\Requests\UpdateRackRequest;
use App\Models\Book;
use App\Models\Rack;
use App\Services\RackPlacementService;
use App\Services\RackService;
use Illuminate\Contracts\View\View;

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

    public function show(Rack $rack): View
    {
        $rack->load(['books:id,title,rack_id,position_code']);
        $books = Book::query()->orderBy('title')->get(['id', 'title', 'rack_id', 'position_code']);
        $assignedBooksInRack = $rack->books()->orderBy('title')->get(['id', 'title', 'rack_id', 'position_code']);
        $grid = $this->rackService->buildGrid($rack);
        $emptyPositions = collect($grid)
            ->where('occupied', false)
            ->pluck('code')
            ->values();

        return view('racks.show', [
            'rack' => $rack,
            'grid' => $grid,
            'grid_class' => $this->gridClass($rack->columns),
            'positions' => $this->rackService->generatePositions($rack->rows, $rack->columns),
            'books' => $books,
            'unassigned_books' => Book::query()->unassigned()->orderBy('title')->get(['id', 'title', 'rack_id', 'position_code']),
            'assigned_books_in_rack' => $assignedBooksInRack,
            'empty_positions' => $emptyPositions,
            'has_books_in_rack' => $rack->books->isNotEmpty(),
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
}
