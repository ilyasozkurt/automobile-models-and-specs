<?php

namespace App\Console\Commands;

ini_set('memory_limit', '1G');
error_reporting(E_ALL);

use App\Models\Automobile;
use App\Models\Brand;
use App\Models\Engine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScrapeAutomobiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:automobiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This function scrapes automobile models from autoevolution.com';

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

        //Scrape brands from scratch.
        $this->call('scrape:brands');

        $this->output->info('Looking for automobile models.');

        //Make first request to get updated id list for brands
        $http = Http::retry(5)->get('https://www.autoevolution.com/carfinder/');

        if ($http->failed()) {
            $this->output->error('https://www.autoevolution.com/carfinder/ could not received');
            return Command::FAILURE;
        }

        $pageContents = $http->body();
        $pageDom = str_get_html($pageContents);

        $brandIds = [];
        $selectBoxItems = $pageDom->find('.cfrow', 1)->find('ul [data-opt]');

        foreach ($selectBoxItems as $boxItem) {
            $brandIds[] = $boxItem->getAttribute('data-opt');
        }

        $brandIds = implode(',', $brandIds);

        //Make another request to get all automobiles
        $http = Http::asForm()->withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://www.autoevolution.com/carfinder/', [
            'n[brand]' => $brandIds,
            'n[submitted]' => 1
        ]);

        if ($http->failed()) {
            $this->output->error('https://www.autoevolution.com/carfinder/ could not received');
            return Command::FAILURE;
        }

        $pageContents = $http->body();
        $pageDom = str_get_html($pageContents);
        $automobileDoms = $pageDom->find('h5');

        $enginesCount = 0;
        $modelsCount = count($automobileDoms);

        $this->output->info($modelsCount . ' models found.');

        $progressbar = $this->output->createProgressBar($modelsCount);
        $progressbar->setFormat('very_verbose');
        $progressbar->start();

        foreach ($automobileDoms as $automobileDom) {

            $detailURL = $automobileDom->find('a', 0)->href ?? null;

            //Make first request to get updated id list for brands
            $http = Http::retry(5)->get($detailURL);

            if ($http->failed()) {
                $this->output->error($detailURL . ' could not received');
            }

            $pageContents = $http->body();
            $pageDom = str_get_html($pageContents);

            $name = $pageDom->find('.newstitle', 0)->plaintext ?? null;
            $brandName = trim($pageDom->find('[itemprop="brand"]', 0)->plaintext ?? null);
            $description = $this->dropHtmlAttributes($pageDom->find('.modelcontainer [itemprop="description"]', 0)->innertext ?? null);
            $photosJSON = json_decode($pageDom->find('#schema-gallery-data', 0)->innertext ?? null);
            $engineVariants = $pageDom->find('[data-engid]');

            $brand = Brand::where('name', $brandName)->first();

            $photos = [];
            if (is_object($photosJSON)) {
                foreach ($photosJSON->associatedMedia as $media) {
                    $photos[] = $media->contentUrl;
                }
            } else {
                $photos = [];
            }

            $pressRelease = null;

            //Parse id to get press release
            $iForPressRelease = $pageDom->find('[onclick^="aeshowpress("]', 0);

            if ($iForPressRelease) {
                $onClick = $iForPressRelease->getAttribute('onclick');
                preg_match('/aeshowpress\(([0-9]*)\,/i', $onClick, $matches);
                if (is_numeric($matches[1])) {
                    $pressRelease = Http::get('https://www.autoevolution.com/rhh.php?k=pr_cars&i=' . $matches[1]);
                    $pressRelease = $pressRelease->body();
                    $pressRelease = str_get_html($pressRelease);
                    $pressRelease = $this->dropHtmlAttributes($pressRelease->find('.content', 0)->innertext);
                }
            }

            $automobile = Automobile::updateOrCreate([
                'url_hash' => hash('crc32', $detailURL),
            ], [
                'url' => $detailURL,
                'brand_id' => $brand->id,
                'name' => $name,
                'description' => $description,
                'press_release' => $pressRelease,
                'photos' => $photos,
            ]);


            foreach ($engineVariants as $engineVariant) {

                $otherId = $engineVariant->getAttribute('data-engid');
                $name = $engineVariant->find('.enginedata .title .col-green2', 0)->plaintext ?? null;

                if (!$name) {
                    continue;
                }

                $specs = [];

                foreach ($engineVariant->find('.techdata') as $index => $techData) {

                    $sectionName = $techData->find('.title', 0)->plaintext ?? null;

                    if (str_contains($sectionName, 'ENGINE SPECS')) {
                        $sectionName = 'ENGINE SPECS';
                    }

                    $sectionName = $this->toTitleCase($sectionName);
                    $sectionItems = $techData->find('dl dt');

                    foreach ($sectionItems as $dt) {
                        $specName = $this->toTitleCase($dt->plaintext ?? null);
                        $specValue = $this->toTitleCase($dt->next_sibling()->plaintext ?? null);
                        $specs[$sectionName][$specName] = $specValue;
                    }
                }

                Engine::updateOrCreate([
                    'other_id' => $otherId,
                ], [
                    'automobile_id' => $automobile->id,
                    'name' => $name,
                    'specs' => $specs,
                ]);

                $enginesCount++;

            }

            $progressbar->advance();

        }

        $progressbar->finish();

        $this->output->info(count($automobileDoms) . ' models and ' . $enginesCount . ' engines inserted/updated on database.');

        return Command::SUCCESS;

    }

    /**
     * @param string $text
     * @return string
     */
    private function toTitleCase(string $text): string
    {
        return mb_convert_case(trim($text), MB_CASE_TITLE);
    }

    /**
     * @param string $text
     * @return string
     */
    private function dropHtmlAttributes(string $text): string
    {
        return preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/si", '<$1$2>', $text);
    }

}
