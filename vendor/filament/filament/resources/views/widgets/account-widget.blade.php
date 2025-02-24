@php
    use Carbon\Carbon;
    use App\Services\WelcomeMessage;
    use Stevebauman\Location\Facades\Location;

    $user = filament()->auth()->user();
    $ipAddress = request()->ip();
    $location = Location::get($ipAddress);
    $lastOnline = Carbon::parse($user->updated_at)->diffForHumans();

    $info = $user->info ?? [];
    if (isset($info['department'])) unset($info['department']);
    if (isset($info['position'])) unset($info['position']);
    $formatKey = fn ($key) => ucwords(preg_replace('/(?<!^)([A-Z])/', ' $1', $key));
@endphp
<x-filament-widgets::widget class="fi-account-widget bg-white dark:bg-gray-800 p-4 rounded-md">
    <x-filament::section>
        <div class="flex items-center justify-between space-x-4">
            <!-- User Avatar -->
            <x-filament-panels::avatar.user :user="$user"/>

            <!-- User Information and Preferences -->
            <div class="flex-1">
                <!-- Welcome Message -->
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white leading-snug">
                    {{ (new WelcomeMessage())->generate() }}
                </h2>

                <!-- Grid Layout -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <!-- User Details -->
                    <div class="space-y-2">
                        <!-- Username -->
                        <div class="flex items-center text-primary-500 leading-relaxed">
                            <x-heroicon-o-user-circle class="w-5 h-5 mr-2 text-gray-500 dark:text-gray-400"/>
                            <span>{{ filament()->getUserName($user) }}</span>
                        </div>

                        <!-- Location -->
                        <div class="flex items-center text-primary-500 leading-relaxed">
                            <x-heroicon-o-map-pin class="w-5 h-5 mr-2 text-gray-500 dark:text-gray-400"/>
                            @if ($location)
                                <span>{{ $location->cityName }}, {{ $location->countryName }}</span>
                            @else
                                <span>Location: Unidentified</span>
                            @endif
                        </div>

                        <!-- Last Online -->
                        <div class="flex items-center text-primary-500 leading-relaxed">
                            <x-heroicon-o-clock class="w-5 h-5 mr-2 text-gray-500 dark:text-gray-400"/>
                            <span>Last Online: {{ $lastOnline }}</span>
                        </div>
                    </div>

                    <!-- Preferences -->
                    @if (! empty($info))
                        <div class="hidden md:block lg:block">
                            <!-- Dotted Divider -->
                            <hr class="border-t-2 border-dotted border-gray-300 dark:border-gray-600 my-2">

                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                Settings:
                            </h3>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($info as $key => $value)
                                    <div class="flex items-start text-sm text-primary-500 leading-relaxed">
                                        <x-heroicon-o-cog class="w-4 h-4 mr-2 text-gray-500 dark:text-gray-400"/>
                                        <div>
                                            <span class="font-semibold capitalize">{{ $formatKey($key) }}</span>:
                                            <span class="ml-1 capitalize">{{ $value }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Logout Button -->
            <form action="{{ filament()->getLogoutUrl() }}" method="post" class="ml-4">
                @csrf
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-left-on-rectangle"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                >
                    {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                </x-filament::button>
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
