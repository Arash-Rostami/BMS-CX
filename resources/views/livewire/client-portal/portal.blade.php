<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full space-y-6">

        <!-- Header -->
        <div class="bg-white p-6 rounded-xl shadow-sm flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ $account->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Client Portal</p>
            </div>

            <button wire:click="logout" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Log out
            </button>
        </div>

        <!-- Navigation -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex" aria-label="Tabs">
                    <button wire:click="setTab('statements')"
                            class="{{ $activeTab === 'statements' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm">
                        Account Statements
                    </button>
                    <button wire:click="setTab('loans')"
                            class="{{ $activeTab === 'loans' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm">
                        Loan Summaries
                    </button>
                </nav>
            </div>

            <div class="p-6">
                @if($activeTab === 'statements')
                    <div class="space-y-4">
                        @livewire(\App\Filament\Resources\Dashboard\AccountStatementResource\Widgets\AccountStatementStatsWidget::class, ['tableColumnSearches' => []])
                        <livewire:client-portal.statement-table :accountId="$account->id" />
                    </div>
                @elseif($activeTab === 'loans')
                    <div class="space-y-4">
                        @livewire(\App\Filament\Resources\Dashboard\LoanSummaryResource\Widgets\LoanSummaryStatsWidget::class, ['tableColumnSearches' => []])
                        <livewire:client-portal.loan-table :accountId="$account->id" />
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
