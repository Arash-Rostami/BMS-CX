@php
    $tabs = [
        '' => ['label' => 'All',        'icon' => 'heroicon-o-inbox'],
        'pending'    => ['label' => 'New',        'icon' => 'heroicon-o-document-plus'],
        'processing' => ['label' => 'Processing', 'icon' => 'heroicon-o-clock'],
        'Rial'       => ['label' => 'Rial',       'icon' => 'heroicon-o-currency-rupee'],
        'USD'        => ['label' => 'USD',        'icon' => 'heroicon-o-currency-dollar'],
        'advance'    => ['label' => 'Advance',    'icon' => 'heroicon-o-credit-card'],
        'balance'    => ['label' => 'Balance',    'icon' => 'heroicon-o-scale'],
        'other'      => ['label' => 'Other',      'icon' => 'heroicon-o-ellipsis-horizontal-circle'],
    ];
@endphp

<div id="filter-tabs" class="tab-container">
    @foreach ($tabs as $scope => $tab)
        <div
            class="tab-item {{ ($activeTab === $scope || (empty($activeTab) && empty($scope))) ? 'active' : '' }}"
            data-scope="{{ $scope }}">
            <x-dynamic-component :component="$tab['icon']" class="w-3 h-3 mr-1 inline-block"/>
            <span>{{ $tab['label'] }}</span>
        </div>
    @endforeach
</div>

<script>
    (() => {
        const initFilterTabs = () => {
            const container = document.getElementById('filter-tabs');
            if (!container) return;

            const tabs = container.querySelectorAll('[data-scope]');

            tabs.forEach(tab => {
                tab.removeEventListener('click', handleClick);
                tab.addEventListener('click', handleClick);
            });

            function handleClick(event) {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const scope = this.dataset.scope;
                Livewire.dispatch('updateActiveTab', {scope});
            }
        };

        document.addEventListener('DOMContentLoaded', initFilterTabs);
        Livewire.on('refreshTabFilters', initFilterTabs);
    })();
</script>
