<?php

namespace App\Providers;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class BrowserServiceProvider extends ServiceProvider
{

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        createBrowser();
    }

}
