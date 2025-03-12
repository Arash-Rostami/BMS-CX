<?php

namespace App\Filament\Resources\Core\UserResource\Pages;

use App\Models\Department;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Rawilk\FilamentPasswordInput\Password;
use Wallo\FilamentSelectify\Components\ButtonGroup;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class Admin
{

    /**
     * @return TextInput
     */
    public static function getFirstName(): TextInput
    {
        return TextInput::make('first_name')
            ->label(fn() => new HtmlString('<span class="grayscale">🖊 </span><span class="text-primary-500 font-normal">Forename</span>'))
            ->autocapitalize('words')
            ->placeholder('First Name in English only')
            ->minLength(2)
            ->maxLength(50)
            ->required();
    }


    /**
     * @return TextInput
     */
    public static function getMiddleName(): TextInput
    {
        return TextInput::make('middle_name')
            ->label(fn() => new HtmlString('<span class="grayscale">🖊 </span><span class="text-primary-500 font-normal">Middle Name</span>'))
            ->autocapitalize('words')
            ->placeholder('Middle Name in English only')
            ->minLength(2)
            ->maxLength(50);
    }

    /**
     * @return TextInput
     */
    public static function getLastName(): TextInput
    {
        return TextInput::make('last_name')
            ->label(fn() => new HtmlString('<span class="grayscale">🖊 </span><span class="text-primary-500 font-normal">Surname</span>'))
            ->autocapitalize('words')
            ->placeholder('Last Name in English only')
            ->minLength(2)
            ->maxLength(50)
            ->required();
    }

    /**
     * @return PhoneInput
     */
    public static function getPhoneNum(): PhoneInput
    {
        return PhoneInput::make('phone')
            ->label(fn() => new HtmlString('<span class="grayscale">📞 </span><span class="text-primary-500 font-normal">Phone No.</span>'))
            ->ipLookup(function () {
                return rescue(fn() => Http::get('http://ip-api.com/json/')->json('country'), app()->getLocale(), report: false);
            })
            ->autoPlaceholder('polite')
            ->placeholder('Phone number')
            ->required();
    }

    /**
     * @return Password
     */
    public static function getPassword(): Password
    {
        return Password::make('password')
            ->label(fn() => new HtmlString('<span class="grayscale">🗝️</span><span class="text-primary-500 font-normal">Password</span>'))
            ->placeholder('Write your Password')
            ->visibleOn('create')
            ->password()
            ->minLength(8)
            ->required(fn(?User $record) => $record === null)
            ->columnSpan(1);
    }

    /**
     * @return Password
     */
    public static function getPassWordConfirmation(): Password
    {
        return Password::make('password_confirmation')
            ->label(fn() => new HtmlString('<span class="grayscale">🗝️🗝  </span><span class="text-primary-500 font-normal">Password Confirmation</span>'))
            ->visibleOn('create')
            ->password()
            ->minLength(8)
            ->same('password')
            ->required()
            ->placeholder('Re-type your Password')
            ->columnSpan(1);
    }

    /**
     * @return TextInput
     */
    public static function getCompany(): TextInput
    {
        return TextInput::make('company')
            ->label(fn() => new HtmlString('<span class="grayscale">🏛 </span><span class="text-primary-500 font-normal">Company</span>'))
            ->placeholder('Write your Company Name')
            ->visibleOn('create')
            ->required();
    }

    /**
     * @return Select
     */
    public static function getDepartment(): Select
    {
        return Select::make('info.department')
            ->label(fn() => new HtmlString('<span class="grayscale"> 📚</span><span class="text-primary-500 font-normal">Department</span>'))
            ->relationship('department', 'name')
            ->default('0')
            ->required();
    }

    /**
     * @return TextInput
     */
    public static function getEmail(): TextInput
    {
        return TextInput::make('email')
            ->label(fn() => new HtmlString('<span class="grayscale">📧 </span><span class="text-primary-500 font-normal">Email</span>'))
            ->email()
            ->rules([self::validateEmail()])
            ->placeholder('Write your Company Email (it must end with @persoreco.com, @solsuntrading.com, or @persolco.com)')
            ->required();
    }

    /**
     * @return ButtonGroup
     */
    public static function getStatus(): ButtonGroup
    {
        return ButtonGroup::make('status')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Status</span>'))
            ->options([
                'active' => 'Active ✅',
                'inactive' => 'Inactive ❌',
                'pending' => '️Suspended ⚠',
            ])
            ->onColor('primary')
            ->offColor('gray')
            ->required();
    }


    public static function getPosition(): ButtonGroup
    {
        return ButtonGroup::make('info.position')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Positions</span>'))
            ->options([
                'jnr' => 'Junior 🟡',
                'mdr' => 'Medior 🟠',
                'snr' => 'Senior 🟢',
            ])
            ->default('jnr')
            ->onColor('primary')
            ->offColor('gray');
    }

    /**
     * @return ButtonGroup
     */
    public static function getRole(): ButtonGroup
    {
        return ButtonGroup::make('role')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Role</span>'))
            ->options([
                'agent' => 'Agent 🎧',
                'accountant' => 'Accountant 💰',
                'manager' => 'Manager 👑',
                'partner' => 'Partner 👓',
            ])
            ->onColor('primary')
            ->offColor('gray')
            ->required();
    }

    /**
     * @return ImageColumn|string
     */
    public static function showAvatar(): ImageColumn
    {
        return ImageColumn::make('avatar')
            ->square()
            ->height(20)
            ->grow(false)
            ->defaultImageUrl(fn(User $record) => Vite::asset(sprintf('%s%s.svg', 'resources/images/avatars/', strtolower(($record->role == 'partner') ? 'viewer' : $record->role))));
    }

    /**
     * @return TextColumn
     */
    public static function showFullName(): TextColumn
    {
        return TextColumn::make('fullName')
            ->searchable(['first_name', 'middle_name', 'last_name'])
            ->sortable(['first_name'])
            ->grow(false)
            ->toggleable()
            ->weight('medium')
            ->alignLeft()
            ->tooltip(fn(User $record) => "created at {$record->created_at}");
    }

    /**
     * @return TextColumn
     */
    public static function showEmail(): TextColumn
    {
        return TextColumn::make('email')
            ->icon('heroicon-m-envelope')
            ->sortable()
            ->searchable()
            ->toggleable()
            ->color('gray')
            ->alignLeft();
    }

    /**
     * @return TextColumn
     */
    public static function showPhone(): TextColumn
    {
        return TextColumn::make('phone')
            ->icon('heroicon-o-device-phone-mobile')
            ->sortable()
            ->searchable()
            ->toggleable()
            ->color('gray')
            ->alignLeft();
    }

    /**
     * @return TextColumn
     */
    public static function showIP(): TextColumn
    {
        return TextColumn::make('ip_address')
            ->sortable()
            ->toggleable()
            ->icon('heroicon-o-globe-alt')
            ->color('secondary')
            ->alignLeft()
            ->tooltip(fn() => "IP address")
            ->formatStateUsing(fn(string $state) => self::getCountryFromIp($state));
    }


    /**
     * @return TextColumn
     */
    public static function showCompany(): TextColumn
    {
        return TextColumn::make('company')
            ->searchable()
            ->sortable()
            ->toggleable()
            ->state(function (?Model $record) {
                if (!$record->company && !isset($record->info['department'])) {
                    return 'N/A';
                }

                $departmentName = isset($record->info['department']) ? Department::find($record->info['department'])->name : null;

                return $record->company . ($departmentName ? ' (' . $departmentName . ')' : '');
            })
            ->color('secondary')
            ->alignLeft()
            ->icon('heroicon-o-building-office-2');
    }


    /**
     * @return TextColumn
     */
    public static function showLastOnline(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->badge()
            ->formatStateUsing(fn($state) => "Last seen: " . $state->diffForHumans())
            ->sortable()
            ->alignRight();
    }

    /**
     * @return TextColumn
     */
    public static function showStatus(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->searchable()
            ->sortable()
            ->toggleable()
            ->icon(fn(string $state): string => match ($state) {
                'active' => 'heroicon-m-check-badge',
                'inactive' => 'heroicon-o-x-circle',
                'pending' => 'heroicon-o-question-mark-circle',
            })
            ->formatStateUsing(fn(string $state): string => match ($state) {
                'active' => 'Active',
                'inactive' => 'Inactive',
                'pending' => 'Suspended',
            })
            ->color(fn(string $state): string => match ($state) {
                'active' => 'success',
                'inactive' => 'danger',
                'pending' => 'warning',
            })
            ->alignLeft();
    }

    /**
     * @return TextColumn
     */
    public static function showRole(): TextColumn
    {
        return TextColumn::make('role')
            ->badge()
            ->searchable()
            ->sortable()
            ->toggleable()
            ->icon(function (string $state): string {
                $iconMap = [
                    'agent' => 'heroicon-o-pencil',
                    'accountant' => 'heroicon-o-calculator',
                    'manager' => 'heroicon-o-briefcase',
                    'partner' => 'heroicon-o-book-open',
                    'admin' => 'heroicon-o-shield-check',
                ];
                foreach ($iconMap as $key => $icon) {
                    if (str_contains($state, $key)) {
                        return $icon;
                    }
                }
                return 'heroicon-o-circle';
            })
            ->color(function (string $state): string {
                $colorMap = [
                    'agent' => 'primary',
                    'accountant' => 'warning',
                    'manager' => 'success',
                    'partner' => 'gray',
                    'admin' => 'danger',
                ];
                foreach ($colorMap as $key => $color) {
                    if (str_contains($state, $key)) {
                        return $color;
                    }
                }
                return 'secondary';
            })
            ->state(function (?Model $record) {
                $role = $record->role;
                $position = $record->info['position'] ?? null;

                return ($role && $position) ? "$role ($position)" : $role ?? 'N/A';
            })
            ->alignLeft();
    }

    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public static function filterRole(): SelectFilter
    {
        return SelectFilter::make('role')
            ->options([
                'agent' => 'Agent',
                'accountant' => 'Accountant',
                'manager' => 'Manager',
                'partner' => 'Partner',
            ]);
    }

    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public static function filterStatus(): SelectFilter
    {
        return SelectFilter::make('status')
            ->options([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'pending' => 'Suspended',
            ]);
    }

    public static function validateEmail()
    {
        return function () {
            return function (string $attribute, $value, Closure $fail) {
                if (preg_match('/@(persoreco\.com|solsuntrading\.com|persolco\.com)$/i', $value)) {
                    return true;
                }
                $fail("The email given is invalid.");
            };
        };
    }

    protected static function getCountryFromIp(string $state): string
    {
        return Cache::remember("country_{$state}", now()->addMinutes(10), function () use ($state) {
            $response = Http::timeout(30)->get('http://ip-api.com/json/' . $state);
            return $response->successful() && $response->json('status') === 'success'
                ? $response->json('country')
                : 'Unidentified IP';
        });
    }
}
