<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseCategoryRequest;
use App\Models\Warehouse\WarehouseConsumableCategory;

class WarehouseCategoryController extends Controller
{
    public function index()
    {
        return view('warehouse.categories.index', ['categories' => WarehouseConsumableCategory::query()->orderBy('name')->paginate(20)]);
    }

    public function store(StoreWarehouseCategoryRequest $request)
    {
        WarehouseConsumableCategory::query()->create($request->safe()->all() + [
            'created_by' => $request->user()->getKey(),
            'updated_by' => $request->user()->getKey(),
        ]);

        return back()->with('status', 'Kategori berhasil dibuat.');
    }

    public function update(StoreWarehouseCategoryRequest $request, WarehouseConsumableCategory $category)
    {
        $category->fill($request->safe()->all() + ['updated_by' => $request->user()->getKey()])->save();

        return back()->with('status', 'Kategori diperbarui.');
    }
}
