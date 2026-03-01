<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-red-700 border border-red-200 bg-red-50 hover:bg-red-100 disabled:opacity-60 disabled:cursor-not-allowed transition']) }}>
    {{ $slot }}
</button>
