<div class="main-container">
    {{-- Message Toast --}}
    @include('components.CostCalculation.toast')
    {{-- Dark / Light Button --}}
    <div class="w-full mb-5 flex justify-end">
        <button id="dark-mode-toggle"
                class="px-2 py-2 rounded my-dark-class hover:bg-gray-300 w-auto flex items-center justify-center">
            <span id="dark-mode-icon" class="material-icons-outlined">brightness_7</span>
        </button>
    </div>
    {{-- Tabs Nav --}}
    <div x-data="{ activeTab: 'tableView' }">
        <ul class="nav nav-tabs flex border-b mb-4">
            <li class="nav-item">
                <button @click="activeTab = 'tableView'"
                        :class="activeTab === 'tableView'
                        ? 'nav-link bg-blue-500 text-white'
                        : 'nav-link bg-gray-200'">
                    View Cargo Log
                </button>
            </li>
            <li class="nav-item">
                <button @click="activeTab = 'form'"
                        :class="activeTab === 'form'
                        ? 'nav-link bg-blue-500 text-white'
                        : 'nav-link bg-gray-200'">
                    Add New Record
                </button>
            </li>
        </ul>
        {{-- Tabs Content --}}
        <div class="tab-content p-4 content-wrapper">
            {{-- Table Tab --}}
            <div x-show="activeTab === 'tableView'" x-transition.duration.500ms class="tab-pane">
                @livewire('cost-calculation.cost-calculation-table')
            </div>
            {{-- Form Tab --}}
            <div x-show="activeTab === 'form'" x-transition.duration.500ms class="tab-pane">
                @include('components.CostCalculation.form')
            </div>
        </div>
    </div>
</div>
