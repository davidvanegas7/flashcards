<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center bg-white text-[#7267cb] border border-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors']) }}>
    {{ $slot }}
</button>
