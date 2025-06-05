<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CostCalculationController extends Controller
{
    public function index()
    {
        return view('components.CostCalculation.main');
    }
}
