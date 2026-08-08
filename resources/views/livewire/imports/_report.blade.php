@if (! empty($this->report))
    @if (! empty($this->report['fatal']))
        <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Import failed</p>
            <p class="mt-1">{{ $this->report['fatal'] }}</p>
        </div>
    @else
        <div class="mt-6 rounded-lg bg-green-50 p-4 text-sm text-green-700">
            <p><span class="font-semibold">{{ $this->report['success'] }}</span> rows imported successfully.</p>
        </div>

        @if (! empty($this->report['failures']))
            <div class="mt-4 overflow-hidden rounded-lg bg-amber-50">
                <div class="px-4 py-3">
                    <p class="font-semibold text-amber-800">{{ count($this->report['failures']) }} rows failed:</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-amber-200 text-sm">
                        <thead class="bg-amber-100/60 text-left text-xs font-semibold uppercase tracking-wider text-amber-800">
                            <tr>
                                <th class="px-4 py-2">Row</th>
                                <th class="px-4 py-2">Alasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-200 bg-white/60">
                            @foreach ($this->report['failures'] as $failure)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $failure['row'] }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $failure['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
@endif