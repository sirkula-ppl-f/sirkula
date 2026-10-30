<x-layouts.app title="Edit Hub">

<form method="POST"
      action="{{ route('admin.hubs.update', $hub) }}"
      class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
    @csrf
    @method('PUT')

value="{{ old('name', $hub->name) }}"
@selected(old('type', $hub->type) === $value)
@selected(old('region_id', $hub->region_id) == $region->id)
{{ old('public_address', $hub->public_address) }}
{{ old('description', $hub->description) }}