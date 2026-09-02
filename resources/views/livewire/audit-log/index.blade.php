<div class="py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Audit Log') }}</h2>

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            @if ($this->activities->isEmpty())
                <p class="p-6 text-sm text-gray-500">No recorded activity yet.</p>
            @else
                <div class="p-6">
                    @foreach ($this->activities->groupBy(fn ($activity) => $activity->created_at->format('Y-m-d')) as $date => $activities)
                        <div class="mb-8 last:mb-0">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>

                            <ol class="relative space-y-6 border-s border-gray-200 ps-6">
                                @foreach ($activities as $activity)
                                    @php
                                        $styles = [
                                            'created' => ['bg-blue-100', 'text-blue-700'],
                                            'updated' => ['bg-amber-100', 'text-amber-700'],
                                            'deleted' => ['bg-red-100', 'text-red-700'],
                                            'login' => ['bg-green-100', 'text-green-700'],
                                            'failed' => ['bg-orange-100', 'text-orange-700'],
                                            'logout' => ['bg-gray-100', 'text-gray-700'],
                                        ][$activity->action] ?? ['bg-gray-100', 'text-gray-700'];
                                    @endphp
                                    <li class="relative">
                                        <span class="absolute -start-[33px] top-1.5 h-3 w-3 rounded-full ring-4 ring-white {{ $styles[0] }}"></span>
                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="text-sm text-gray-800">{{ $activity->description }}</p>
                                                <p class="mt-0.5 text-xs text-gray-500">
                                                    <span class="{{ $styles[1] }}">{{ ucfirst($activity->action) }}</span>
                                                    <span class="mx-1">•</span>
                                                    {{ $activity->user?->name ?? 'System' }}
                                                </p>
                                                @if ($activity->action === 'updated' && is_array($activity->properties) && isset($activity->properties['before'], $activity->properties['after']))
                                                    <div class="mt-2 rounded-lg bg-gray-50 p-3 text-xs">
                                                        @foreach ($activity->properties['after'] as $field => $newValue)
                                                            @if (array_key_exists($field, $activity->properties['before']) && $activity->properties['before'][$field] != $newValue)
                                                                @php
                                                                    $label = ucwords(str_replace('_', ' ', (string) $field));
                                                                    $old = $activity->properties['before'][$field];
                                                                    $old = is_scalar($old) ? (string) $old : json_encode($old);
                                                                    $new = is_scalar($newValue) ? (string) $newValue : json_encode($newValue);
                                                                @endphp
                                                                <div class="flex justify-between gap-4 border-b border-gray-200 py-1 last:border-0 last:pb-0">
                                                                    <span class="text-gray-500">{{ $label }}</span>
                                                                    <span class="text-right">
                                                                        <span class="text-gray-400 line-through">{{ $old !== '' ? $old : '—' }}</span>
                                                                        <span class="mx-1 text-gray-400">→</span>
                                                                        <span class="font-medium text-gray-800">{{ $new !== '' ? $new : '—' }}</span>
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                            <time class="shrink-0 text-xs text-gray-400">{{ $activity->created_at->format('H:i') }}</time>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-gray-100 px-6 py-4">
                    <x-pagination-footer :paginator="$this->activities" />
                </div>
            @endif
        </div>
    </div>
</div>