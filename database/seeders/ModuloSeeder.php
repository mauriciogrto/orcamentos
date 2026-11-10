<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;

class ModuloSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'orcamentos' => 'Orçamentos',
            'financeiro' => 'Financeiro',
            'fiscal_nfe' => 'Fiscal / NF-e',
        ] as $slug => $nome) {
            Modulo::query()->updateOrCreate(
                ['slug' => $slug],
                ['nome' => $nome],
            );
        }
    }
}
