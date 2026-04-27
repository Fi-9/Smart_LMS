<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Rack;
use App\Repositories\Contracts\RackRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RackService
{
    public function __construct(
        private readonly RackRepositoryInterface $rackRepository
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->rackRepository->paginate($perPage);
    }

    public function create(array $attributes): Rack
    {
        return $this->rackRepository->create($attributes);
    }

    public function findDefaultRackId(): ?int
    {
        return $this->rackRepository->findDefaultRackId();
    }

    public function update(Rack $rack, array $attributes): Rack
    {
        return $this->rackRepository->update($rack, $attributes);
    }

    public function delete(Rack $rack): void
    {
        $this->rackRepository->delete($rack);
    }

    public function generatePositions(int $rows, int $columns): array
    {
        $positions = [];

        for ($rowIndex = 0; $rowIndex < $rows; $rowIndex++) {
            for ($columnIndex = 1; $columnIndex <= $columns; $columnIndex++) {
                $positions[] = chr(65 + $rowIndex).$columnIndex;
            }
        }

        return $positions;
    }

    public function buildGrid(Rack $rack): array
    {
        $booksByPosition = $rack->books->groupBy('position_code');
        $grid = [];
        $capacity = $rack->capacity_per_slot ?? 1;
        $slotCategories = $rack->slotCategoryMap();

        foreach ($this->generatePositions($rack->rows, $rack->columns) as $positionCode) {
            $books = $booksByPosition->get($positionCode, collect());
            $count = $books->count();

            $grid[] = [
                'code' => $positionCode,
                'occupied' => $count > 0,
                'is_full' => $count >= $capacity,
                'count' => $count,
                'capacity' => $capacity,
                'slot_category_id' => isset($slotCategories[$positionCode]) && is_numeric($slotCategories[$positionCode])
                    ? (int) $slotCategories[$positionCode]
                    : null,
                'books' => $books->map(
                    fn (Book $book): array => ['id' => $book->id, 'title' => $book->title]
                )->toArray(),
                'book_title' => $books->first()?->title,
                'book_id' => $books->first()?->id,
            ];
        }

        return $grid;
    }

    public function buildGridMatrix(Rack $rack): array
    {
        $booksByPosition = $rack->books->groupBy('position_code');
        $matrix = [];
        $capacity = $rack->capacity_per_slot ?? 1;
        $slotCategories = $rack->slotCategoryMap();

        for ($rowIndex = 0; $rowIndex < $rack->rows; $rowIndex++) {
            $rowLabel = chr(65 + $rowIndex);
            $rowCells = [];

            for ($columnIndex = 1; $columnIndex <= $rack->columns; $columnIndex++) {
                $positionCode = $rowLabel.$columnIndex;
                $books = $booksByPosition->get($positionCode, collect());
                $count = $books->count();

                $rowCells[] = [
                    'code' => $positionCode,
                    'occupied' => $count > 0,
                    'is_full' => $count >= $capacity,
                    'count' => $count,
                    'capacity' => $capacity,
                    'slot_category_id' => isset($slotCategories[$positionCode]) && is_numeric($slotCategories[$positionCode])
                        ? (int) $slotCategories[$positionCode]
                        : null,
                    'books' => $books->map(
                        fn (Book $book): array => ['id' => $book->id, 'title' => $book->title]
                    )->toArray(),
                    'book_title' => $books->first()?->title,
                    'book_id' => $books->first()?->id,
                ];
            }

            $matrix[] = [
                'label' => $rowLabel,
                'cells' => $rowCells,
            ];
        }

        return $matrix;
    }

    public function buildMiniMap(Rack $rack, int $currentBookId): array
    {
        $rack->loadMissing(['books:id,title,rack_id,position_code']);

        $matrix = $this->buildGridMatrix($rack);

        foreach ($matrix as $rowIndex => $row) {
            foreach ($row['cells'] as $cellIndex => $cell) {
                $state = 'empty';

                if ($cell['occupied']) {
                    $state = ((int) $cell['book_id'] === $currentBookId) ? 'current' : 'filled';
                }

                $matrix[$rowIndex]['cells'][$cellIndex]['state'] = $state;
            }
        }

        return [
            'rack_name' => $rack->name,
            'matrix' => $matrix,
        ];
    }

    public function availableSlots(): array
    {
        $slots = [];
        $racks = Rack::query()
            ->with(['books:id,rack_id,position_code'])
            ->orderBy('id')
            ->get();

        foreach ($racks as $rack) {
            $counts = $rack->books
                ->groupBy('position_code')
                ->map(fn (Collection $group): int => $group->count());
            
            $capacity = $rack->capacity_per_slot ?? 1;

            foreach ($this->generatePositions($rack->rows, $rack->columns) as $positionCode) {
                if ($counts->get($positionCode, 0) < $capacity) {
                    $slots[] = [
                        'rack_id' => $rack->id,
                        'position_code' => $positionCode,
                    ];
                }
            }
        }

        return $slots;
    }

    public function firstAvailableSlotInRack(int $rackId): ?array
    {
        $rack = Rack::query()
            ->with(['books:id,rack_id,position_code'])
            ->find($rackId);

        if (! $rack) {
            return null;
        }

        $counts = $rack->books
            ->groupBy('position_code')
            ->map(fn (Collection $group): int => $group->count());
        
        $capacity = $rack->capacity_per_slot ?? 1;

        foreach ($this->generatePositions($rack->rows, $rack->columns) as $positionCode) {
            if ($counts->get($positionCode, 0) < $capacity) {
                return [
                    'rack_id' => $rack->id,
                    'position_code' => $positionCode,
                ];
            }
        }

        return null;
    }
}
