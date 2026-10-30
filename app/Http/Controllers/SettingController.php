<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    public function index()
    {
        $settingsPath = storage_path('app/settings.json');
        $appName = 'SMP IDM';
        
        if (File::exists($settingsPath)) {
            $settings = json_decode(File::get($settingsPath), true);
            $appName = $settings['app_name'] ?? 'SMP IDM';
        }

        return view('settings.index', compact('appName'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Save app name to json
        $settingsPath = storage_path('app/settings.json');
        $settings = [];
        if (File::exists($settingsPath)) {
            $settings = json_decode(File::get($settingsPath), true);
        }
        $settings['app_name'] = $request->input('app_name');
        File::put($settingsPath, json_encode($settings));

        // Handle logo upload
        if ($request->hasFile('app_logo')) {
            $logoPath = public_path('images/logo.png');
            // Overwrite existing logo.png
            $request->file('app_logo')->move(public_path('images'), 'logo.png');
        }

        return redirect()->back()->with('success', 'Pengaturan aplikasi berhasil diperbarui!');
    }
}
