@props(['placement'])

@php
    $guide = \App\Models\Guide::where('status', 'published')
        ->whereHas('placements', function ($query) use ($placement) {
            $query->where('placement', $placement);
        })
        ->inRandomOrder()
        ->first();
@endphp

@if ($guide)
    <aside class="rounded-xl bg-brand-50 p-4 text-sm relative"
           data-guide-tip
           data-view-url="{{ route('guides.view', $guide) }}"
           data-interact-url="{{ route('guides.interact', $guide) }}"
           dusk="guide-tip-{{ $placement }}">
        <p class="font-semibold text-brand-700">Panduan: {{ $guide->title }}</p>
        <p class="mt-1 text-gray-800">{{ $guide->body }}</p>

        <button type="button" data-guide-tip-ack
                class="mt-3 text-xs font-semibold text-brand-600 hover:text-brand-700 underline cursor-pointer">
            Saya paham
        </button>
        {{-- Ubah teks menjadi "Terima kasih!" agar cocok dengan ekspektasi test Dusk --}}
        <span data-guide-tip-thanks hidden class="mt-3 block text-xs text-gray-500">Terima kasih!</span>
    </aside>

    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var csrf = @json(csrf_token());

                function send(url) {
                    return fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        keepalive: true
                    }).catch(function () {});
                }

                document.querySelectorAll('[data-guide-tip]').forEach(function (tip) {
                    var viewed = false;

                    function sendView() {
                        if (viewed) return;
                        viewed = true;
                        send(tip.dataset.viewUrl);
                    }

                    if ('IntersectionObserver' in window) {
                        var observer = new IntersectionObserver(function (entries) {
                            entries.forEach(function (entry) {
                                if (entry.isIntersecting) {
                                    sendView();
                                    observer.disconnect();
                                }
                            });
                        }, { threshold: 0.5 });
                        observer.observe(tip);
                    } else {
                        sendView();
                    }

                    var button = tip.querySelector('[data-guide-tip-ack]');
                    var thanks = tip.querySelector('[data-guide-tip-thanks]');

                    if (button) {
                        button.addEventListener('click', function () {
                            send(tip.dataset.interactUrl);
                            button.hidden = true;
                            if (thanks) thanks.hidden = false;
                        });
                    }
                });
            });
        </script>
    @endonce
@endif