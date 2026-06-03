<div class="p-6 max-w-full mx-auto space-y-6 bg-gray-200 min-h-screen dark:bg-gray-950">
    <x-filament::section shadow="sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <img
                    src="{{ Vite::asset('resources/images/logos/bms-new-logo.png') }}"
                    alt="BMS Logo"
                    style="height: 4rem;"
                    class="w-auto"
                >
                <div class="h-10 w-px bg-gray-300 dark:bg-gray-700 hidden sm:block"></div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Welcome, {{ $account->name }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                       BMS - Client Portal
                    </p>
                </div>
            </div>

            <div>
                <x-filament::button
                    wire:click="logout"
                    color="gray"
                    icon="heroicon-m-arrow-left-on-rectangle"
                >
                    Log out
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    <div class="w-full flex justify-center border-b-2 border-primary-500/20 dark:border-primary-500/10 pb-px">
        <div class="w-full max-w-md mx-auto">
            <x-filament::tabs label="Portal Sections" class="justify-center mx-auto">
                <x-filament::tabs.item
                    :active="$activeTab === 'statements'"
                    wire:click="setTab('statements')"
                    icon="heroicon-o-arrow-path"
                >
                    Account Statements
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$activeTab === 'loans'"
                    wire:click="setTab('loans')"
                    icon="heroicon-o-document-magnifying-glass"
                >
                    Loan Summaries
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>
    </div>

    <div class="mt-6">
        @if($activeTab === 'statements')
            <div wire:key="tab-statements" class="space-y-6">
                @livewire(\App\Filament\Resources\Dashboard\AccountStatementResource\Widgets\AccountStatementStatsWidget::class, [
                    'tableColumnSearches' => [],
                    'accountId' => $account->id
                ])

                <x-filament::section shadow="sm">
                    <livewire:client-portal.statement-table :accountId="$account->id"/>
                </x-filament::section>
            </div>
        @elseif($activeTab === 'loans')
            <div wire:key="tab-loans" class="space-y-6">
                @livewire(\App\Filament\Resources\Dashboard\LoanSummaryResource\Widgets\LoanSummaryStatsWidget::class, [
                    'tableColumnSearches' => [],
                    'accountId' => $account->id
                ])

                <x-filament::section shadow="sm">
                    <livewire:client-portal.loan-table :accountId="$account->id"/>
                </x-filament::section>
            </div>
        @endif
    </div>
</div>
