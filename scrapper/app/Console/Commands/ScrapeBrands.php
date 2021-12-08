<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScrapeBrands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:brands';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This function scrapes automobile manufacturers from autoevolution.com';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $http = Http::get('https://www.autoevolution.com/cars/');

        if ($http->failed()) {
            return Command::FAILURE;
        }

        $pageContents = $http->body();
        $pageDom = str_get_html($pageContents);

        $brandDoms = $pageDom->find('.carman');

        foreach ($brandDoms as $brandDom) {

            $url = trim($brandDom->find('[itemprop="url"]')[0]->content ?? null);
            $name = trim($brandDom->find('[itemprop="name"]')[0]->plaintext ?? null);
            $logo = trim($brandDom->find('[itemprop="logo"]')[0]->src ?? null);

            $brand = Brand::updateOrCreate(
                ['url_hash' => \hash('crc32', $url)],
                [
                    'url' => $url,
                    'name' => $name,
                    'logo' => $logo,
                ]);

            print $url . PHP_EOL;


        }

        return Command::SUCCESS;

    }
}
