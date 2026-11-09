<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\AddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->latest()->get();

        return response()->json([
            'data' => $addresses,
        ], Response::HTTP_OK);
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($request->boolean('is_default')) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($request->validated());

        return response()->json([
            'message' => 'Address created successfully',
            'data' => $address,
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized action',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($request->boolean('is_default')) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($request->validated());

        return response()->json([
            'message' => 'Address updated successfully',
            'data' => $address,
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized action',
            ], Response::HTTP_FORBIDDEN);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully',
        ], Response::HTTP_OK);
    }

}