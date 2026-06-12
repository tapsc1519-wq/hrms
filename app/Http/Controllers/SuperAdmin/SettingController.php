<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = [
            'site_title'    => Setting::get('site_title',    'ITAM'),
            'site_subtitle' => Setting::get('site_subtitle', 'Asset Management'),
            'site_logo'     => Setting::get('site_logo'),
            'site_favicon'  => Setting::get('site_favicon'),
        ];

        return view('super-admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_title'    => 'required|string|max:60',
            'site_subtitle' => 'nullable|string|max:80',
            'site_logo'     => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'site_favicon'  => 'nullable|mimes:ico,png,jpg,jpeg,svg|max:512',
        ]);

        Setting::set('site_title',    $request->site_title);
        Setting::set('site_subtitle', $request->site_subtitle ?? '');

        if ($request->hasFile('site_logo')) {
            $old = Setting::get('site_logo');
            if ($old) Storage::disk('public')->delete($old);
            Setting::set('site_logo', $request->file('site_logo')->store('branding', 'public'));
        }

        if ($request->hasFile('site_favicon')) {
            $old = Setting::get('site_favicon');
            if ($old) Storage::disk('public')->delete($old);
            Setting::set('site_favicon', $request->file('site_favicon')->store('branding', 'public'));
        }

        // Clear cached values
        cache()->forget('setting:site_title');
        cache()->forget('setting:site_subtitle');
        cache()->forget('setting:site_logo');
        cache()->forget('setting:site_favicon');

        return back()->with('success', 'Settings saved successfully.');
    }

    public function removeLogo()
    {
        $old = Setting::get('site_logo');
        if ($old) Storage::disk('public')->delete($old);
        Setting::set('site_logo', null);
        cache()->forget('setting:site_logo');
        return back()->with('success', 'Logo removed.');
    }

    public function removeFavicon()
    {
        $old = Setting::get('site_favicon');
        if ($old) Storage::disk('public')->delete($old);
        Setting::set('site_favicon', null);
        cache()->forget('setting:site_favicon');
        return back()->with('success', 'Favicon removed.');
    }
}
