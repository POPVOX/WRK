<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\Person;
use App\Models\Project;
use App\Observers\OrganizationObserver;
use App\Observers\PersonObserver;
use App\Observers\ProjectObserver;
use Illuminate\Support\ServiceProvider;

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
        // Register observers for automatic LinkedIn sync
        Person::observe(PersonObserver::class);
        Organization::observe(OrganizationObserver::class);
        Project::observe(ProjectObserver::class);
    }
}
