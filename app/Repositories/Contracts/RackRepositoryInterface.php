<?php

namespace App\Repositories\Contracts;

use App\Models\Rack;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RackRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findDefaultRackId(): ?int;

    public function create(array $attributes): Rack;

    public function update(Rack $rack, array $attributes): Rack;

    public function delete(Rack $rack): void;
}
