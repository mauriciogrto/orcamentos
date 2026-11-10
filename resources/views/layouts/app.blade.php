<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @auth
            @if(session()->has('impersonator_id'))
                <div class="fixed inset-x-0 top-0 z-[60] border-b border-orange-400 bg-orange-500 px-4 py-2 text-sm font-semibold text-orange-950 shadow">
                    <div class="mx-auto flex max-w-7xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <span>
                            MODO MANUTENÇÃO: você está acessando como {{ Auth::user()->name }}.
                            Superadmin original: {{ session('impersonator_name') }}.
                        </span>
                        <form method="POST" action="{{ route('impersonation.stop') }}">
                            @csrf
                            <button class="rounded-md bg-orange-950 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-orange-900">
                                Voltar ao superadmin
                            </button>
                        </form>
                    </div>
                </div>
                <div class="h-16 sm:h-10"></div>
            @endif
        @endauth

        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <div id="app-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
            <div class="w-full max-w-sm rounded-xl bg-white p-5 text-center shadow-xl">
                <h3 id="app-confirm-title" class="text-base font-semibold text-gray-900">Confirmar ação</h3>
                <p id="app-confirm-message" class="mt-2 text-sm leading-5 text-gray-600"></p>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <button type="button" id="app-confirm-cancel" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" id="app-confirm-ok" class="rounded-md bg-blue-700 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-600">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('app-confirm-modal');
                const message = document.getElementById('app-confirm-message');
                const confirmButton = document.getElementById('app-confirm-ok');
                const cancelButton = document.getElementById('app-confirm-cancel');
                let pendingForm = null;
                let pendingSubmitter = null;

                const closeModal = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    pendingForm = null;
                    pendingSubmitter = null;
                };

                const openModal = (form, submitter) => {
                    pendingForm = form;
                    pendingSubmitter = submitter || null;
                    message.textContent = form.dataset.confirmMessage || 'Confirma esta ação?';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    cancelButton.focus();
                };

                document.addEventListener('submit', (event) => {
                    const form = event.target.closest('form[data-confirm-message]');

                    if (!form || form.dataset.confirmed === 'true') {
                        return;
                    }

                    event.preventDefault();
                    openModal(form, event.submitter);
                }, true);

                confirmButton.addEventListener('click', () => {
                    if (!pendingForm) {
                        closeModal();
                        return;
                    }

                    const form = pendingForm;
                    const submitter = pendingSubmitter;
                    form.dataset.confirmed = 'true';
                    closeModal();

                    if (submitter && typeof form.requestSubmit === 'function') {
                        form.requestSubmit(submitter);
                    } else if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });

                cancelButton.addEventListener('click', closeModal);
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });
            });
        </script>
    </body>
</html>
