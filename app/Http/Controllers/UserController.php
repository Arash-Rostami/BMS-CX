<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function toggleTableDesign()
    {
        $user = auth()->user();

        if ($user) {
            $user->info = array_merge(
                (array)$user->info,
                ['tableDesign' => ($user->info['tableDesign'] ?? 'modern') === 'classic' ? 'modern' : 'classic']
            );
            $user->save();
        }

        return redirect()->back();
    }

    public function test()
    {
        return view('test');
    }
}
