<?php

namespace App\Exports;

use App\Models\Engine;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EnginesExport implements FromCollection, WithHeadings
{
    use Exportable;

    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        return Engine::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'id',
            'other_id',
            'automobile_id',
            'name',
            'specs',
            'created_at',
            'updated_at',
        ];
    }
}
