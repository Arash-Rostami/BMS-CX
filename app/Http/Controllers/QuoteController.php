<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuoteToken;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function authenticate($token)
    {
        if (!$token) {
            return abort(400, 'Invalid quote creation request. Missing token.');
        }

        $quoteToken = $this->getQuoteTokenDetails($token);

        if (!$quoteToken) {
            return abort(401, 'Unauthorized quote creation request.');
        }

        session()->put('quoteToken', $quoteToken);

        return view('components.create-quote');
    }

    /**
     * @param $token
     * @return mixed
     */
    public function getQuoteTokenDetails($token)
    {
        return QuoteToken::where('token', $token)->first();
    }
}
