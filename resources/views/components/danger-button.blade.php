<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 !bg-red-600 border border-red-600 rounded-md !font-bold font-bold text-xs !text-white uppercase tracking-widest hover:!bg-red-500 active:!bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150']) }} style="background-color: #dc2626; color: #ffffff;">
    {{ $slot }}
</button>
