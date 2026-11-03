<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 !bg-gray-900 border border-gray-900 rounded-md !font-bold font-bold text-xs !text-white uppercase tracking-widest shadow-sm hover:!bg-gray-800 focus:!bg-gray-800 active:!bg-black focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150']) }} style="background-color: #111827; color: #ffffff;">
    {{ $slot }}
</button>
