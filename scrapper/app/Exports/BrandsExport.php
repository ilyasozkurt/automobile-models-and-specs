<?php

namespace App\Exports;

use App\Models\Brand;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BrandsExport implements FromCollection, WithHeadings
{
    use Exportable;

    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        return Brand::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'id',
            'url_hash',
            'url',
            'name',
            'logo',
            'created_at',
            'updated_at',
        ];
    }
}
