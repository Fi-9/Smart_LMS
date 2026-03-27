<?php

namespace App\Repositories\Eloquent;

use App\Models\Rack;
use App\Repositories\Contracts\RackRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RackRepository implements RackRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Rack::query()->latest()->paginate($perPage);
    }

    public function create(array $attributes): Rack
    {
        return Rack::query()->create($attributes);
    }

    public function findDefaultRackId(): ?int
    {
        return Rack::query()->orderBy('id')->value('id');
    }

    public function update(Rack $rack, array $attributes): Rack
    {
        $rack->update($attributes);

        return $rack->refresh();
    }

    public function delete(Rack $rack): void
    {
        $rack->delete();
    }
}
