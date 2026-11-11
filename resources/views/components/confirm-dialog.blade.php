@php
    $buttonClass = 'inline-flex items-center justify-center rounded-md border px-4 py-2.5 text-sm font-semibold';
@endphp

{{--
    Dialog konfirmasi untuk form ber-atribut data-confirm. Pasang satu kali per halaman.
    Judul, pesan, dan label tombol diisi resources/js/confirm-dialog.js dari atribut form.
    Tanpa JavaScript dialog tidak pernah dibuka dan form terkirim biasa.
--}}
<dialog
    data-confirm-dialog
    aria-labelledby="confirm-dialog-title"
    aria-describedby="confirm-dialog-message"
    class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl bg-white p-0 text-gray-900 shadow-xl backdrop:bg-gray-900/50"
    dusk="confirm-dialog"
>
    {{-- Padding di wadah dalam, jadi klik yang mengenai <dialog> sendiri pasti klik di backdrop. --}}
    <div class="p-6">
        <h2 id="confirm-dialog-title" class="text-lg font-semibold" data-confirm-dialog-title>{{ __('master-data.confirm.title') }}</h2>

        <p id="confirm-dialog-message" class="mt-2 text-sm wrap-break-word text-gray-700" data-confirm-dialog-message></p>

        <form method="dialog" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            {{-- Fokus awal di Batal karena aksinya berisiko. --}}
            <button
                type="submit"
                value="cancel"
                autofocus
                class="{{ $buttonClass }} border-gray-300 bg-white text-gray-800 hover:bg-gray-50"
                dusk="confirm-dialog-cancel"
            >{{ __('master-data.confirm.cancel_label') }}</button>

            <button
                type="submit"
                value="confirm"
                class="{{ $buttonClass }} border-brand-600 bg-brand-600 text-white hover:bg-brand-700 data-[variant=danger]:border-red-300 data-[variant=danger]:bg-white data-[variant=danger]:text-red-700 data-[variant=danger]:hover:bg-red-50"
                data-confirm-dialog-confirm
                dusk="confirm-dialog-confirm"
            >{{ __('master-data.confirm.confirm_label') }}</button>
        </form>
    </div>
</dialog>
