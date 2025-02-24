<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function toggleTableDesign(): \Illuminate\Http\RedirectResponse
    {
        return $this->togglePreference('tableDesign', 'modern', 'modern', 'classic');
    }

    public function toggleMenuDesign(): \Illuminate\Http\RedirectResponse
    {
        return $this->togglePreference('menuDesign', 'side', 'side', 'top');
    }

    public function toggleFilterDesign(): \Illuminate\Http\RedirectResponse
    {
        return $this->togglePreference('filterDesign', 'hide', 'hide', 'show');
    }

    public function toggleSidebarItems(): \Illuminate\Http\RedirectResponse
    {
        return $this->togglePreference('sideBarItems', 'show', 'show', 'hide');
    }

    public function toggleShadeDesign(): \Illuminate\Http\RedirectResponse
    {
        return $this->togglePreference('shadeDesign', 'hide', 'hide', 'show');
    }


    private function togglePreference(string $key, string $default, string $option1, string $option2): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        if ($user) {
            $user->info = array_merge(
                (array)$user->info,
                [$key => ($user->info[$key] ?? $default) === $option1 ? $option2 : $option1]
            );
            $user->save();
        }

        return redirect()->back();
    }
}
