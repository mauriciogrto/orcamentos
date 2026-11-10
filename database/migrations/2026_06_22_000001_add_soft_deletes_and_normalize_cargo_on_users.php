<?php

use App\Enums\CargoUsuario;
use App\Enums\RoleName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        foreach (CargoUsuario::cases() as $cargo) {
            $roleId = DB::table('roles')->where('name', $cargo->role()->value)->value('id');

            if ($roleId) {
                $userIds = DB::table('model_has_roles')
                    ->where('role_id', $roleId)
                    ->where('model_type', 'App\\Models\\User')
                    ->pluck('model_id');

                DB::table('users')->whereIn('id', $userIds)->update(['cargo' => $cargo->value]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
