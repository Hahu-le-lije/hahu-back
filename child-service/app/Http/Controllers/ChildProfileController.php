<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChildProfileController extends Controller
{
    public function show(Request $request)
    {
        return $request->user();
    }
}
