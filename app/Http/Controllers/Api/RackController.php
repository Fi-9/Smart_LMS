<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRackRequest;
use App\Http\Requests\UpdateRackRequest;
use App\Models\Rack;
use App\Services\RackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RackController extends Controller
{
    public function __construct(
        private readonly RackService $rackService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $racks = $this->rackService->paginate((int) $request->integer('per_page', 15));

        return response()->json($racks);
    }

    public function store(StoreRackRequest $request): JsonResponse
    {
        $rack = $this->rackService->create($request->validated());

        return response()->json($rack, JsonResponse::HTTP_CREATED);
    }

    public function show(Rack $rack): JsonResponse
    {
        return response()->json([
            'rack' => $rack,
            'positions' => $this->rackService->generatePositions($rack->rows, $rack->columns),
        ]);
    }

    public function update(UpdateRackRequest $request, Rack $rack): JsonResponse
    {
        $rack = $this->rackService->update($rack, $request->validated());

        return response()->json($rack);
    }

    public function destroy(Rack $rack): JsonResponse
    {
        $this->rackService->delete($rack);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}

