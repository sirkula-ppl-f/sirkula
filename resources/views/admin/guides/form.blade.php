@php
    $selected = old('placements', $guide->exists ? $guide->placement_keys : []);
    $inputBase = 'w-full rounded-xl border px-4 py-3 text-base text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent transition';
@endphp
<x-layouts.app>
    <div class="max-w-2xl">
        <div class="mb-4">
            <a class="inline-flex items-center text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors group" href="{{ route('admin.guides.index') }}">
                <span class="mr-1.5 transition-transform group-hover:-translate-x-1">←</span>
                {{ __('guides.back_index') }}
            </a>
        </div>

        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-8">
            {{ $guide->exists ? __('guides.edit_title') : __('guides.create_title') }}
        </h1>

        <section class="bg-white rounded-2xl border border-gray-200/80 p-8 shadow-sm">
            <form method="POST"
                  action="{{ $guide->exists ? route('admin.guides.update', $guide) : route('admin.guides.store') }}"
                  data-submit-once>
                @csrf
                @if ($guide->exists) @method('PUT') @endif

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-900 mb-2" for="title">{{ __('guides.form.title') }}</label>
                    <input id="title" name="title" type="text" required dusk="title-input"
                           value="{{ old('title', $guide->title) }}"
                           class="{{ $inputBase }} {{ $errors->has('title') ? 'border-red-500' : 'border-gray-300' }}"
                           aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}" />
                    @error('title') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-900 mb-2" for="body">{{ __('guides.form.body') }}</label>
                    <textarea id="body" name="body" rows="4" required dusk="body-input"
                              class="{{ $inputBase }} {{ $errors->has('body') ? 'border-red-500' : 'border-gray-300' }}"
                              aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}">{{ old('body', $guide->body) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1.5 font-normal">{{ __('guides.form.body_hint') }}</p>
                    @error('body') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <fieldset class="mb-6">
                    <legend class="block text-sm font-bold text-gray-900 mb-2">{{ __('guides.form.placements') }}</legend>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        @foreach (\App\Models\Guide::PLACEMENTS as $value)
                            <label class="flex items-center gap-2 text-sm text-gray-900">
                                <input type="checkbox" name="placements[]" value="{{ $value }}"
                                       dusk="placement-{{ $value }}"
                                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-600"
                                       @checked(in_array($value, $selected))>
                                {{ __('guides.placements.'.$value) }}
                            </label>
                        @endforeach
                    </div>
                    @error('placements') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    @error('placements.*') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </fieldset>

                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-900 mb-2" for="status">{{ __('guides.form.status') }}</label>
                    <select id="status" name="status" dusk="status-input"
                            class="{{ $inputBase }} bg-white cursor-pointer {{ $errors->has('status') ? 'border-red-500' : 'border-gray-300' }}">
                        @foreach (\App\Models\Guide::STATUSES as $value)
                            <option value="{{ $value }}" @selected(old('status', $guide->status ?? 'draft') === $value)>{{ __('guides.statuses.'.$value) }}</option>
                        @endforeach
                    </select>
                    @error('status') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center space-x-3.5 pt-1">
                    <button type="submit" dusk="save-guide-button"
                            class="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-xl transition duration-150 ease-in-out shadow-sm">
                        {{ __('guides.form.save') }}
                    </button>
                    <a href="{{ route('admin.guides.index') }}"
                       class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-semibold px-6 py-3 rounded-xl transition duration-150 ease-in-out text-center">
                        {{ __('guides.form.cancel') }}
                    </a>
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>