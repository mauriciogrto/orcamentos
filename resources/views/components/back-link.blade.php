@props(['href', 'label' => 'Voltar'])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900']) }}
>
    ← {{ $label }}
</a>
