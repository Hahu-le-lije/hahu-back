<?php

namespace App\Http\Controllers;

use App\Http\Requests\Parents\ParentSignUpRequest;
use App\Http\Requests\Parents\ParentUpdateRequest;
use App\Models\ParentUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParentController extends Controller
{
    public function signUp(ParentSignUpRequest $request)
    {
        $existing = ParentUser::where('clerk_id', $request->input('clerk_id'))->first();

        if ($existing) {
            return response()->json(['parent' => $existing]);
        }

        $parent = ParentUser::create([
            'clerk_id' => $request->input('clerk_id'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'phone_number' => $request->input('phone_number'),
        ]);

        return response()->json(['parent' => $parent], Response::HTTP_CREATED);
    }

    public function me(Request $request)
    {
        return response()->json([
            'parent' => $request->attributes->get('parent'),
        ]);
    }

    public function update(ParentUpdateRequest $request)
    {
        $parent = $request->attributes->get('parent');

        $parent->update($request->only(['first_name', 'last_name', 'phone_number']));

        return response()->json([
            'parent' => $parent->fresh(),
        ]);
    }

    public function delete(Request $request)
    {
        $parent = $request->attributes->get('parent');

        $parent->delete();

        return response()->json([
            'message' => 'Parent deleted successfully.',
        ]);
    }
}
