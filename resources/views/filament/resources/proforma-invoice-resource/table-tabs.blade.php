@php
    $categoryTabs = [
        '' => ['label' => 'All', 'icon' => 'heroicon-o-inbox'],
        'Mineral' => ['label' => 'Mineral', 'icon' => 'heroicon-o-cube'],
        'Polymers' => ['label' => 'Polymers', 'icon' => 'heroicon-m-circle-stack'],
        'Chemicals' => ['label' => 'Chemicals', 'icon' => 'heroicon-o-beaker'],
        'Petro' => ['label' => 'Petroleum', 'icon' => 'heroicon-s-fire'],
    ];
@endphp

<div id="category-tabs" class="tab-container">
    @foreach ($categoryTabs as $scope => $tab)
        <div
            class="tab-item {{ ($activeTab === $scope || (empty($activeTab) && empty($scope))) ? 'active' : '' }}"
            data-scope="{{ $scope }}">
            <x-dynamic-component :component="$tab['icon']" class="w-3 h-3 mr-1 inline-block"/>
            {{ $tab['label'] }}
        </div>
    @endforeach
</div>

<script>
    (() => {
        const initCategoryTabs = () => {
            const container = document.getElementById('category-tabs');
            if (!container) return;

            const tabs = container.querySelectorAll('.tab-item');

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

        document.addEventListener('DOMContentLoaded', initCategoryTabs);
        Livewire.on('refreshTabFilters', initCategoryTabs);
    })();
</script>
