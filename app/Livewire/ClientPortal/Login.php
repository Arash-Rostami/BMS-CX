<?php

namespace App\Livewire\ClientPortal;

use App\Models\Account;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Login extends Component
{
    public $username;
    public $password;
    public $error = '';

    public function login()
    {
        $this->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $account = Account::where('name', $this->username)->first();

        if ($account && isset($account->meta['user']) && isset($account->meta['password'])) {
            if ($account->meta['user'] === $this->username && $account->meta['password'] === $this->password) {
                // Alternatively, if the username they use is just the account name and we just check password
                Session::put('client_account_id', $account->id);
                return redirect()->route('client.portal');
            }
        }

        // Also check if they login with account name and password
        if ($account && isset($account->meta['password']) && !isset($account->meta['user'])) {
            if ($account->meta['password'] === $this->password) {
                Session::put('client_account_id', $account->id);
                return redirect()->route('client.portal');
            }
        }

        // Final check, if user and password exists in meta and user matches input
        $accountByMetaUser = Account::where('meta->user', $this->username)->first();
        if ($accountByMetaUser && isset($accountByMetaUser->meta['password'])) {
             if ($accountByMetaUser->meta['password'] === $this->password) {
                Session::put('client_account_id', $accountByMetaUser->id);
                return redirect()->route('client.portal');
             }
        }

        $this->error = 'Invalid credentials provided.';
    }

    public function render()
    {
        return view('livewire.client-portal.login')->layout('components.layouts.app');
    }
}
