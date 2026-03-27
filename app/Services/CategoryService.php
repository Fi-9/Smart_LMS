<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($perPage);
    }

    public function create(array $attributes): Category
    {
        return $this->categoryRepository->create($attributes);
    }

    public function update(Category $category, array $attributes): Category
    {
        return $this->categoryRepository->update($category, $attributes);
    }

    public function delete(Category $category): void
    {
        $this->categoryRepository->delete($category);
    }
}

