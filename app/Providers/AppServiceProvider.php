<?php

namespace App\Providers;

use App\Models\MasterType;
use App\Services\CompanySettingService;
use App\Support\SqliteFileDumper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Tasks\Backup\DbDumperFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        DbDumperFactory::extend('sqlite', fn () => new SqliteFileDumper);
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if (Schema::hasTable('company_settings')) {
            $settings = app(CompanySettingService::class)->get();

            View::share('companyName', $settings->company_name);
            View::share('companyAddress', $settings->company_address);
            View::share('companyLogoUrl', $settings->logo_url);
        }

        if (Schema::hasTable('master_types')) {
            View::composer('components.sidebar-menu', function ($view): void {
                $view->with('masterMenus', MasterType::query()
                    ->where('aktif', true)
                    ->where('is_system', false)
                    ->orderBy('sort')
                    ->orderBy('nama')
                    ->get());
            });
        }
    }
}
