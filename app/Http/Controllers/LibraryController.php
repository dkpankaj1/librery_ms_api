<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLibraryRequest;
use App\Models\Library;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class LibraryController extends Controller
{
    use ApiResponse;

    /**
     * Display the specified library.
     */
    public function show(Library $library): JsonResponse
    {
        return $this->successResponse($library, 'Library retrieved successfully');
    }

    /**
     * Store a newly created library.
     */
    public function store(StoreLibraryRequest $request): JsonResponse
    {
        $library = Library::create($request->validated());

        return $this->successResponse($library, 'Library created', 201);
    }

    /**
     * Update the specified library.
     */
    public function update(StoreLibraryRequest $request, Library $library): JsonResponse
    {
        $library->update($request->validated());

        return $this->successResponse($library, 'Library updated');
    }
}
