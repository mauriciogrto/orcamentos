<?php

namespace Database\Factories;

use App\Enums\EmpresaStatus;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Empresa> */
class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'nome_fantasia' => fake()->company(),
            'razao_social' => fake()->company().' Ltda.',
            'cnpj' => fake()->unique()->numerify('##############'),
            'telefone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'status' => EmpresaStatus::Ativa->value,
        ];
    }
}
