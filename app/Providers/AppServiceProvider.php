<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
        \Carbon\Carbon::setLocale(config('app.locale', 'id'));

        $settingsPath = storage_path('app/settings.json');
        $appName = 'SMP IDM';
        if (File::exists($settingsPath)) {
            $settings = json_decode(File::get($settingsPath), true);
            $appName = $settings['app_name'] ?? 'SMP IDM';
        }
        $logoVersion = File::exists(public_path('images/logo.png')) ? filemtime(public_path('images/logo.png')) : time();

        View::share('globalAppName', $appName);
        View::share('globalLogoVersion', $logoVersion);

        View::composer(['layouts.admin', 'layouts.guru', 'kesiswaan.*'], function ($view) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('teachers')) {
                    $view->with('globalTeachers', \App\Models\Teacher::orderBy('name')->get());
                }
            } catch (\Throwable $e) {}
        });

        // Otomatis pastikan kolom check_in_time ada di tabel student_attendances (untuk server produksi / aaPanel)
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('student_attendances')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('student_attendances', 'check_in_time')) {
                    \Illuminate\Support\Facades\Schema::table('student_attendances', function ($table) {
                        $table->time('check_in_time')->nullable()->after('status');
                    });
                }
            }
        } catch (\Throwable $e) {}
    }
}
