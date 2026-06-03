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

        $account = Account::query()
            ->whereNotNull('meta')
            ->where('meta', '!=', '')
            ->where('meta->password', $this->password)
            ->where(function ($query) {
                $query->where('name', $this->username)
                    ->orWhere('meta->username', $this->username)
                    ->orWhere('meta->user', $this->username);
            })
            ->first();

        if ($account) {
            Session::put('client_account_id', $account->id);

            return redirect()->route('client.portal');
        }

        $this->error = 'Invalid credentials provided.';
    }

    public function render()
    {
        return view('livewire.client-portal.login')
            ->layout('components.Client.app');
    }
}
