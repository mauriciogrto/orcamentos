<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Excluir minha conta
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Esta ação remove seu acesso. Use somente se tiver certeza, pois o perfil pode estar vinculado a clientes, orçamentos e pedidos.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Excluir conta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                Confirma a exclusão da sua conta?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Informe sua senha para confirmar. Esta ação não deve ser usada para trocar de usuário ou sair do sistema.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Senha"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Excluir conta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
