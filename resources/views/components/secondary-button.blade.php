<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2 !bg-white border border-gray-300 rounded-md !font-bold font-bold text-xs !text-gray-700 uppercase tracking-widest shadow-sm hover:!bg-gray-50 hover:!text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }} style="background-color: #ffffff; color: #374151;">
    {{ $slot }}
</button>
