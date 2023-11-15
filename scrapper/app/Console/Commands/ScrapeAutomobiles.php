<?php

namespace App\Console\Commands;

ini_set('memory_limit', '1G');
error_reporting(E_ALL);

use App\Models\Automobile;
use App\Models\Brand;
use App\Models\Engine;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use simple_html_dom;
use Throwable;

class ScrapeAutomobiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:automobiles {--start-over=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This function scrapes automobile models from the autoevolution.com';

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
     * @throws Exception
     * @return int
     */
    public function handle(): int
    {

        //Ask for continue from that point process stopped.
        $forceAll = $this->option('start-over') ?? $this->ask('Do you want to start over? (yes/no)');

        //Truncate models
        if ($forceAll === 'yes') {
            Automobile::truncate();
            Engine::truncate();
            Brand::truncate();
        }

        //Scrape brands from scratch.
        $this->call('scrape:brands');

        //Print an info about process.
        $this->output->info('Looking for automobile models.');

        //Get rows as array that keeps row DOMs.
        $automobileRowsDOMs = $this->getAutomobileRowDOMs();

        if ($automobileRowsDOMs) {

            //Count automobile rows count.
            $modelsCount = count($automobileRowsDOMs);

            //Create a console progressbar.
            $progressbar = $this
                ->output
                ->createProgressBar($modelsCount);
            $progressbar->setFormat('very_verbose');
            $progressbar->start();

            //Print an info about models count.
            $this->output->info($modelsCount . ' models found.');

            foreach ($automobileRowsDOMs as $automobileRowDOM) {

                //Get automobile detail page url.
                $detailURL = $automobileRowDOM->find('a', 0)->href ?? null;

                DB::beginTransaction();

                try{

                    //Check process continue option
                    $automobile = Automobile::where('url_hash', hash('crc32', $detailURL))->first();

                    //If automobile exists in database, do not process it.
                    if ($automobile) {
                        $progressbar->advance();
                        continue;
                    }

                    //Process automobile detail page.
                    $this->processAutomobileDetailPage($detailURL);

                    DB::commit();

                    //Increase progressbar.
                    $progressbar->advance();

                }catch (Throwable $exception){

                    DB::rollback();

                    throw $exception;

                }

            }

            //Finish progressbar.
            $progressbar->finish();

        } else {

            $this
                ->output
                ->error('There is no automobile row found in search page.');

        }

        //Print an information that process finished.
        $this
            ->output
            ->info(count($automobileRowsDOMs) . ' models inserted/updated on database.');

        return self::SUCCESS;

    }

    /**
     * Convert the case of the string to the title case.
     *
     * @param string $text
     * @return string
     */
    private function toTitleCase(string $text): string
    {
        return mb_convert_case(trim($text), MB_CASE_TITLE);
    }

    /**
     * Drops HTML elements' attributes to make them more clear.
     *
     * @param string $text
     * @return string
     */
    private function dropHtmlAttributes(string $text): string
    {
        $clean= preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/si", '<$1$2>', $text);
        return trim($clean);
    }

    /**
     * Loads a URL source as html DOM.
     *
     * @param string $url
     * @return simple_html_dom|bool
     */
    private function loadURLAsDom(string $url): simple_html_dom|bool
    {

        return str_get_html(browseUrl($url));

    }

    /**
     * Gets a press release if it exists for the automobile.
     *
     * @param simple_html_dom $pageDom
     * @return string|null
     */
    private function getPressRelease(simple_html_dom $pageDom): string|null
    {

        $pressRelease = null;

        //Parse id to get press release
        $iForPressRelease = $pageDom->find('[onclick^="aeshowpress("]', 0);

        if ($iForPressRelease) {

            $onClick = $iForPressRelease->getAttribute('onclick');
            preg_match('/aeshowpress\(([0-9]*)\,/i', $onClick, $matches);

            if (is_numeric($matches[1])) {

                $pressRelease = browseUrl('https://www.autoevolution.com/rhh.php?k=pr_cars&i=' . $matches[1]);
                $pressRelease = str_get_html($pressRelease);
                $pressRelease = $this->dropHtmlAttributes(
                    $pressRelease
                        ->find('.content', 0)
                        ->innertext
                );

            }

        }

        return $pressRelease;

    }

    /**
     * Loads search page and get brand ids as a comma-separated string.
     *
     * @return string|null
     */
    private function getBrandIds(): string|null
    {

        $pageContents = browseUrl('https://www.autoevolution.com/carfinder/');

        $pageDom = str_get_html($pageContents);

        $brandIds = null;

        $selectBoxItemDOMs = $pageDom
            ->find('.cfrow', 1)
            ->find('ul [data-opt]');

        foreach ($selectBoxItemDOMs as $boxItemDom) {
            $brandIds[] = $boxItemDom->getAttribute('data-opt');
        }

        return $brandIds ? implode(',', $brandIds) : null;

    }

    /**
     * Gets automobile rows from the search page.
     *
     * @return array|null
     */
    private function getAutomobileRowDOMs(): array|null
    {

        $brandIds = $this->getBrandIds();

        if (!$brandIds) {
            $this
                ->output
                ->error('Brand ids for searching models is could not received.');
            die();
        }

        //Make another request to get all automobiles
        $pageContents = browseUrlPost('https://www.autoevolution.com/carfinder/', [
            'n[brand]' => $this->getBrandIds(),
            'n[submitted]' => 1
        ]);

        $pageDom = str_get_html($pageContents);

        return $pageDom->find('h5');

    }

    /**
     * Get's automobile description from HTML source.
     *
     * @param simple_html_dom $pageDom
     * @return string|null
     */
    private function getContent(simple_html_dom $pageDom): string|null
    {

        $description = null;
        $descriptionDoms = $pageDom
            ->find('.fl.newstext .mgbot_20');
        //get last description dom
        $descriptionDom = end($descriptionDoms);

        if ($descriptionDom) {
            $description = $this->dropHtmlAttributes(
                $descriptionDom->innertext
            );
        }

        return $description;

    }

    /**
     * Gets automobile photos from HTML resource.
     *
     * @param simple_html_dom $pageDom
     * @return array|null
     */
    private function getPhotos(simple_html_dom $pageDom): array|null
    {

        $photos = null;

        $photosJSON = json_decode(
            $pageDom
                ->find('#schema-gallery-data', 0)
                ->innertext ?? null
        );

        if (is_object($photosJSON)) {
            foreach ($photosJSON->associatedMedia as $media) {
                $photos[] = $media->contentUrl;
            }
        }

        return $photos;

    }

    /**
     * Processes automobile detail page.
     *
     * @param string $detailURL
     * @return void
     * @throws Exception
     */
    private function processAutomobileDetailPage(string $detailURL): void
    {

        $pageDom = $this->loadURLAsDom($detailURL);

        if ($pageDom) {

            $name = $pageDom->find('.newstitle', 0)->plaintext ?? null;
            $brandName = trim($pageDom->find('[itemprop="itemListElement"]', 2)->plaintext ?? null);
            $brand = Brand::where('name', $brandName)->first();

            if (!$brand) {
                throw new Exception($brandName . ' could not found in database.');
            }

            $automobile = Automobile::updateOrCreate([
                'url_hash' => hash('crc32', $detailURL),
            ], [
                'url' => $detailURL,
                'brand_id' => $brand->id,
                'name' => $name,
                'description' => $this->getContent($pageDom),
                'press_release' => $this->getPressRelease($pageDom),
                'photos' => $this->getPhotos($pageDom),
            ]);

            $this->processEngineDOMs($automobile->id, $pageDom);

        } else {

            throw new Exception($detailURL . ' could not load as dom.');

        }

    }

    /**
     * Processes automobile's engine variants.
     *
     * @param int $automobileId
     * @param simple_html_dom $pageDom
     * @return void
     */
    private function processEngineDOMs(int $automobileId, simple_html_dom $pageDom): void
    {

        $engineVariants = $pageDom->find('[data-engid]');

        foreach ($engineVariants as $engineVariant) {

            $otherId = $engineVariant->getAttribute('data-engid');
            $name = $engineVariant->find('.enginedata .title .col-green', 0)->plaintext ?? null;

            if (!$name) {
                continue;
            }

            $specs = [];

            foreach ($engineVariant->find('.techdata') as $techData) {

                $sectionName = $techData->find('.title', 0)->plaintext ?? null;

                if (str_contains($sectionName, 'ENGINE SPECS')) {
                    $sectionName = 'ENGINE SPECS';
                }

                $sectionName = $this->toTitleCase($sectionName);
                $sectionRows = $techData->find('tr');

                foreach ($sectionRows as $row) {

                    $rowColumns = $row->find('td');

                    if (count($rowColumns) !== 2) {
                        continue;
                    }

                    $specColumn = $rowColumns[0];
                    $valueColumn = $rowColumns[1];

                    $specName = $this->toTitleCase($specColumn->plaintext ?? null);
                    $specValue = $this->toTitleCase($valueColumn->plaintext ?? null);
                    $specs[$sectionName][$specName] = $specValue;

                }

            }

            Engine::updateOrCreate([
                'other_id' => $otherId,
            ], [
                'automobile_id' => $automobileId,
                'name' => $name,
                'specs' => $specs,
            ]);

        }


    }

}
