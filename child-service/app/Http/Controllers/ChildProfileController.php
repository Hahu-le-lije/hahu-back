<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChildProfileController extends Controller
{
    public function show(Request $request)
    {
        return $request->user(); // child
    }

    public function update(Request $request)
    {
        $child = $request->user();

        $data = $request->validate([
            'first_name' => 'sometimes|string',
            'last_name'  => 'nullable|string',
            'password'   => 'nullable|string|min:6',
        ]);

        $child->update($data);

        return $child;
    }
}
