@php
    $usuario = $usuario ?? null;
@endphp

<div>
    <x-input-label for="name" value="Nome" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $usuario?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="cargo" value="Cargo e nível de acesso" />
    <select id="cargo" name="cargo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        @foreach ($cargos as $cargo)
            <option value="{{ $cargo->value }}" @selected(old('cargo', $usuario?->cargo) === $cargo->value)>{{ $cargo->value }}</option>
        @endforeach
    </select>
    <p class="mt-2 text-xs text-gray-500">Hierarquia: Gerente, Supervisor e Vendedor. O papel de acesso é definido automaticamente pelo cargo.</p>
    <x-input-error :messages="$errors->get('cargo')" class="mt-2" />
</div>

<div>
    <x-input-label for="email" value="E-mail" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $usuario?->email)" required />
    <p class="mt-1 text-xs text-gray-500">O e-mail é o login do usuário e não pode ser usado por outra conta.</p>
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="password" :value="$usuario ? 'Nova senha (opcional)' : 'Senha inicial'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $usuario" autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="password_confirmation" value="Confirmar senha" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="! $usuario" autocomplete="new-password" />
    </div>
</div>
