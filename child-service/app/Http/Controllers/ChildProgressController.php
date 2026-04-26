<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChildProgressController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->progress;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lesson_id' => 'required|integer',
            'score'     => 'nullable|integer',
            'completed' => 'boolean',
        ]);

        return $request->user()->progress()->create($data);
    }
}
