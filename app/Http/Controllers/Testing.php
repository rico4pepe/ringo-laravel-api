<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Testing extends Controller
{
    public function test()
    {
        return response()->json([
            'message' => 'Hello from Laravel!',
            'data' => [
                'name' => 'John Doe',
                'age' => 30
            ]
        ]);
    }
}
