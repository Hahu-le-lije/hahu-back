<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->children;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'nullable|string',
            'username'   => 'required|string|unique:children,username',
            'password'   => 'required|string|min:6',
            'age'        => 'nullable|integer|min:1',
            'skill_level'=> 'nullable|string',
        ]);

        return $request->user()->children()->create($data);
    }

    public function show(Request $request, Child $child)
    {
        $this->authorize('view', $child);

        return $child;
    }

    public function update(Request $request, Child $child)
    {
        $this->authorize('update', $child);

        $data = $request->validate([
            'first_name' => 'sometimes|string',
            'last_name'  => 'nullable|string',
            'password'   => 'nullable|string|min:6',
            'age'        => 'nullable|integer|min:1',
            'skill_level'=> 'nullable|string',
        ]);

        $child->update($data);

        return $child;
    }

    public function destroy(Request $request, Child $child)
    {
        $this->authorize('delete', $child);

        $child->delete();

        return response()->noContent();
    }
}
