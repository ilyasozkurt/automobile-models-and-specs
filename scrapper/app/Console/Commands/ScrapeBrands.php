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

        $this->output->info('Looking for brands.');

        $htmlSource = browseUrl('https://www.autoevolution.com/cars/');

        $pageDom = str_get_html($htmlSource);

        $brandDOMs = $pageDom->find('.carman');

        $progressbar = $this->output->createProgressBar(count($brandDOMs));
        $progressbar->start();

        foreach ($brandDOMs as $brandDOM) {

            $url = trim($brandDOM->find('[itemprop="url"]')[0]->content ?? null);
            $name = trim($brandDOM->find('[itemprop="name"]')[0]->plaintext ?? null);
            $logo = trim($brandDOM->find('[itemprop="logo"]')[0]->src ?? null);

            Brand::updateOrCreate(
                ['url_hash' => \hash('crc32', $url)],
                [
                    'url' => $url,
                    'name' => $name,
                    'logo' => $logo,
                ]);

            $progressbar->advance();

        }

        $progressbar->finish();

        $this->output->info(count($brandDOMs) .' brands inserted/updated on database.');

        return Command::SUCCESS;

    }
}
