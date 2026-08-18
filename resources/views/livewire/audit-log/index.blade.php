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