<?php

namespace App\Services;

use App\Models\Rack;
use App\Repositories\Contracts\RackRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        $booksByPosition = $rack->books->keyBy('position_code');
        $grid = [];

        foreach ($this->generatePositions($rack->rows, $rack->columns) as $positionCode) {
            $book = $booksByPosition->get($positionCode);

            $grid[] = [
                'code' => $positionCode,
                'occupied' => (bool) $book,
                'book_title' => $book?->title,
                'book_id' => $book?->id,
            ];
        }

        return $grid;
    }

    public function availableSlots(): array
    {
        $slots = [];
        $racks = Rack::query()
            ->with(['books:id,rack_id,position_code'])
            ->orderBy('id')
            ->get();

        foreach ($racks as $rack) {
            $occupied = $rack->books
                ->pluck('position_code')
                ->filter()
                ->flip()
                ->all();

            foreach ($this->generatePositions($rack->rows, $rack->columns) as $positionCode) {
                if (! isset($occupied[$positionCode])) {
                    $slots[] = [
                        'rack_id' => $rack->id,
                        'position_code' => $positionCode,
                    ];
                }
            }
        }

        return $slots;
    }
}
