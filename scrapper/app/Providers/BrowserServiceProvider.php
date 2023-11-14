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

        $this->app->singleton(Browser::class, function () {

            $browserFactory = new BrowserFactory();

            $browser = $browserFactory->createBrowser([
                'headless' => true,
                'sandbox' => false,
                'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
                'windowSize' => [1440, 900],
                'keepAlive' => true,
                'imagesEnabled' => true,
                'ignoreCertificateErrors' => true,
                'userDataDir' => storage_path('browser'),
                'userCrashDumpsDir' => storage_path('browser/crash-dumps'),
                'customFlags' => [
                    '--disable-blink-features',
                    '--disable-blink-features=AutomationControlled',
                    '--incognito',
                    '--enable-automation=false',
                ],
            ]);

            $browser->createPage();

            return $browser;

        });

    }

}
