@extends('main')

@section('main')
    <div x-data="{
            darkMode: JSON.parse(localStorage.getItem('darkMode')) ?? false,
            activeTab: 'sales'
        }"
         x-init="$watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
         x-bind:class="darkMode ? 'bg-gray-800' : 'bg-gray-300'"
         class="min-h-screen flex flex-col transition-colors duration-300"
    >
        <div
            x-bind:class="darkMode ? 'dark' : ''"
            class="transition-colors duration-300 bg-transparent text-gray-700 dark:text-gray-100 p-0"
        >
            <!-- Dark mode toggle button -->
            <div class="container mx-auto p-4 flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Managerial Dashboard</h1>
                <x-Dashboard.darkLight/>
            </div>

            <!-- Dashboard content -->
            <div class="container mx-auto p-6 bg-white dark:bg-gray-700 rounded-lg shadow-lg">
                <!-- Navigation tabs -->
                <nav class="border-b border-gray-200 dark:border-gray-600 flex space-x-8" aria-label="Tabs">
                    <a
                        href="#"
                        @click.prevent="activeTab = 'sales'"
                        :class="{
                            'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'sales',
                            'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'sales'
                        }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                    >
                        Sales Analytics
                    </a>
                    <a
                        href="#"
                        @click.prevent="activeTab = 'marketing'"
                        :class="{
                            'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'marketing',
                            'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'marketing'
                        }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                    >
                        Marketing Analytics
                    </a>
                </nav>

                <!-- Tab content -->
                <div class="mt-6">
                    <!-- Sales Analytics -->
                    <div x-show="activeTab === 'sales'">
                        <livewire:dashboard.myChart />
                        <p class="mt-4 text-gray-600 dark:text-gray-300">Sales Analytics Component</p>
                    </div>
                    <!-- Marketing Analytics -->
                    <div x-show="activeTab === 'marketing'">
                        <p class="text-gray-600 dark:text-gray-300">Marketing Analytics Component</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endsection

@section('headJS')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.min.js"></script>
@endsection
