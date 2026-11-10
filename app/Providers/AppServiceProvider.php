<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\Cliente;
use App\Models\FaixaComissao;
use App\Models\Item;
use App\Models\Orcamento;
use App\Models\Pedido;
use App\Models\User;
use App\Observers\ItemObserver;
use App\Policies\ClientePolicy;
use App\Policies\FaixaComissaoPolicy;
use App\Policies\ItemPolicy;
use App\Policies\OrcamentoPolicy;
use App\Policies\PedidoPolicy;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Cliente::class, ClientePolicy::class);
        Gate::policy(FaixaComissao::class, FaixaComissaoPolicy::class);
        Gate::policy(Item::class, ItemPolicy::class);
        Gate::policy(Orcamento::class, OrcamentoPolicy::class);
        Gate::policy(Pedido::class, PedidoPolicy::class);
        Item::observe(ItemObserver::class);

        Gate::before(function (User $user): ?bool {
            return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
        });
    }
}
