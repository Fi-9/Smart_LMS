<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;

class CategoryPageController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {
    }

    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->categoryService->create($request->validated());

        return redirect()->route('categories.index')->with(
            'toast',
            ['type' => 'success', 'message' => 'Category created.']
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('categories.index')->with(
            'toast',
            ['type' => 'success', 'message' => 'Category updated.']
        );
    }

    public function destroy(Category $category)
    {
        $this->categoryService->delete($category);

        return redirect()->route('categories.index')->with(
            'toast',
            ['type' => 'success', 'message' => 'Category removed.']
        );
    }
}

