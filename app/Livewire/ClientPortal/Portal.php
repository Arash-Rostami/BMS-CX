<?php

namespace App\Livewire\ClientPortal;

use App\Models\Account;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Portal extends Component
{
    public $account;
    public $activeTab = 'statements'; // 'statements' or 'loans'

    public function mount()
    {
        $accountId = Session::get('client_account_id');

        if (!$accountId) {
            return redirect()->route('client.login');
        }

        $this->account = Account::find($accountId);

        if (!$this->account) {
            Session::forget('client_account_id');
            return redirect()->route('client.login');
        }
    }

    public function logout()
    {
        Session::forget('client_account_id');
        return redirect()->route('client.login');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.client-portal.portal')->layout('components.layouts.app');
    }
}
