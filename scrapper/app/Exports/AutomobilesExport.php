<?php

namespace App\Exports;

use App\Models\Automobile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AutomobilesExport implements FromCollection, WithHeadings
{
    use Exportable;

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return Automobile::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'id',
            'brand_id',
            'name',
            'url',
            'image',
            'price',
            'engine_id',
            'engine_name',
            'engine_specs',
            'created_at',
            'updated_at',
        ];
    }
}
