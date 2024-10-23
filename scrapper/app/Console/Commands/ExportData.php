<?php

namespace App\Console\Commands;

use App\Exports\AutomobilesExport;
use App\Exports\BrandsExport;
use App\Exports\EnginesExport;
use App\Models\Automobile;
use App\Models\Brand;
use App\Models\Engine;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use ZipArchive;

class ExportData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrapper:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This function exports data from database';

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
    public function handle(): int
    {

        // Inform user about the process
        $this->info('Exporting as json...');

        // Export to json file
        $this->exportToJson();

        // Inform user about the process
        $this->info('Exporting as csv...');

        // Export to csv file
        $this->exportToCsv();

        // Inform user about the process
        $this->info('Exporting as sql...');

        // Export to sql file
        $this->exportToSql();

        // Inform user about the process
        $this->info('Exporting as xml...');

        // Export to xml file
        $this->exportToXML();

        // Inform user about the process
        $this->info('Data exported successfully!');

        // Return success
        return self::SUCCESS;

    }

    /**
     * @return void
     */
    private function exportToJson(): void
    {

        //Export brands to json
        $brands = (new BrandsExport())->collection();
        Storage::disk('local')->put('brands.json', $brands->toJson());

        //Export automobiles to json
        $automobiles = (new AutomobilesExport())->collection();
        $automobiles->toJson();
        Storage::disk('local')->put('automobiles.json', $automobiles->toJson());

        //Export engines to json
        $engines = (new EnginesExport())->collection();
        $engines->toJson();
        Storage::disk('local')->put('engines.json', $engines->toJson());

        //zip them to root path
        $zip = new ZipArchive();
        $zip->open('../automobiles.json.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile(Storage::disk('local')->path('brands.json'), 'brands.json');
        $zip->addFile(Storage::disk('local')->path('automobiles.json'), 'automobiles.json');
        $zip->addFile(Storage::disk('local')->path('engines.json'), 'engines.json');
        $zip->close();

    }

    /**
     * Export data to csv file with model attributes
     * @return void
     */
    private function exportToCsv(): void
    {

        (new AutomobilesExport)->store('automobiles.csv');
        (new BrandsExport)->store('brands.csv');
        (new EnginesExport)->store('engines.csv');

        //zip them to root path
        $zip = new ZipArchive();
        $zip->open('../automobiles.csv.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile(Storage::disk('local')->path('brands.csv'), 'brands.csv');
        $zip->addFile(Storage::disk('local')->path('automobiles.csv'), 'automobiles.csv');
        $zip->addFile(Storage::disk('local')->path('engines.csv'), 'engines.csv');
        $zip->close();

    }

    /**
     * Export data to sql file with model attributes
     *
     * @return void
     */
    private function exportToSql(): void
    {

        // Dump brands table
        $this->dumpTable((new Brand())->getTable(), Storage::disk('local')->path('brands.sql'));

        // Dump automobiles table
        $this->dumpTable((new Automobile())->getTable(), Storage::disk('local')->path('automobiles.sql'));

        // Dump engines table
        $this->dumpTable((new Engine())->getTable(), Storage::disk('local')->path('engines.sql'));

        //zip them to root path
        $zip = new ZipArchive();
        $zip->open('../automobiles.sql.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile(Storage::disk('local')->path('brands.sql'), 'brands.sql');
        $zip->addFile(Storage::disk('local')->path('automobiles.sql'), 'automobiles.sql');
        $zip->addFile(Storage::disk('local')->path('engines.sql'), 'engines.sql');
        $zip->close();


    }

    /**
     * @return void
     */
    private function exportToXML(): void
    {

        // Export brands to xml
        $brands = (new BrandsExport())->collection();
        Storage::disk('local')->put('brands.xml', view('exports.brands', ['brands' => $brands])->render());

        // Export automobiles to xml
        $automobiles = (new AutomobilesExport())->collection();
        Storage::disk('local')->put('automobiles.xml', view('exports.automobiles', ['automobiles' => $automobiles])->render());

        // Export engines to xml
        $engines = (new EnginesExport())->collection();
        Storage::disk('local')->put('engines.xml', view('exports.engines', ['engines' => $engines])->render());

        //zip them to root path
        $zip = new ZipArchive();
        $zip->open('../automobiles.xml.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile(Storage::disk('local')->path('brands.xml'), 'brands.xml');
        $zip->addFile(Storage::disk('local')->path('automobiles.xml'), 'automobiles.xml');
        $zip->addFile(Storage::disk('local')->path('engines.xml'), 'engines.xml');
        $zip->close();

    }

    /**
     * @param string $tableName
     * @param string $filePath
     * @return void
     */
    private function dumpTable(string $tableName, string $filePath): void
    {

        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s %s > %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.host'),
            config('database.connections.mysql.database'),
            $tableName,
            $filePath
        );

        // Execute the dump command
        system($command);

    }

}
