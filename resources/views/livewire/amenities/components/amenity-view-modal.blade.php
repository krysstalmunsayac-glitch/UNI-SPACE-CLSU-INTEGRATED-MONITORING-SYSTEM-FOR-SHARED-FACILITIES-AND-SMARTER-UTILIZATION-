<x-ui::modal wire:model.self="showViewModal" class="w-[95vw] max-w-2xl">
    @if ($this->viewingAmenity)
        @php($amenity = $this->viewingAmenity)
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">Amenity details</x-ui::heading>
                <x-ui::subheading>Review the amenity, its facilities, and creator.</x-ui::subheading>
            </div>

            <dl class="grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-bold text-zinc-500">Name</dt>
                    <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $amenity->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-zinc-500">Status</dt>
                    <dd class="mt-1"><x-ui::badge :color="$amenity->Status === 'Available' ? 'green' : 'red'">{{ $amenity->Status }}</x-ui::badge></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-zinc-500">Description</dt>
                    <dd class="mt-1 whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $amenity->Description ?? 'No description provided.' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-zinc-500">Available quantity</dt>
                    <dd class="mt-1 text-zinc-700 dark:text-zinc-300">{{ number_format($amenity->inventory_quantity) }} units</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-zinc-500">Created</dt>
                    <dd class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $amenity->Created_at?->format('M d, Y h:i A') ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-zinc-500">Assigned facilities</dt>
                    <dd class="mt-2 max-h-72 overflow-y-auto rounded-xl border border-zinc-200 bg-zinc-100 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        @if ($amenity->facilities->isNotEmpty())
                            <ul class="list-disc space-y-2 pl-5 text-sm text-zinc-700 marker:text-zinc-500 dark:text-zinc-300 dark:marker:text-zinc-500">
                                @foreach ($amenity->facilities as $facility)
                                    <li class="pl-1 leading-5">
                                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $facility->Facility_Name }}</span>
                                        @if ($facility->Office)
                                            <span class="text-zinc-500 dark:text-zinc-400"> — {{ $facility->Office }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <span class="text-sm text-zinc-500">No facilities assigned.</span>
                        @endif
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-zinc-500">Created by</dt>
                    <dd class="mt-1 text-zinc-700 dark:text-zinc-300">
                        <span class="font-semibold">{{ $amenity->creator?->name ?? 'Legacy/System' }}</span>
                        @if ($amenity->creator)
                            <span class="text-zinc-500">· {{ $amenity->creator->roleLabel() }} · {{ $amenity->creator->email }}</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="flex justify-end">
                <x-ui::button type="button" wire:click="closeView" variant="primary">Close</x-ui::button>
            </div>
        </div>
    @endif
</x-ui::modal>
