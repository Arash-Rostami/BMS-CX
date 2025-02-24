<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManagerialDashboard extends Controller
{
    public function index()
    {
        return view('components.managerial-dashboard');
    }
}
