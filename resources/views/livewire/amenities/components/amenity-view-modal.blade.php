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
                    <dt class="text-xs font-bold text-zinc-500">Concurrent usage limit</dt>
                    <dd class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $amenity->reservation_limit ? number_format($amenity->reservation_limit) : 'Unlimited' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-zinc-500">Created</dt>
                    <dd class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $amenity->Created_at?->format('M d, Y h:i A') ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-zinc-500">Assigned facilities</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        @forelse ($amenity->facilities as $facility)
                            <x-ui::badge color="blue">{{ $facility->Facility_Name }}{{ $facility->Office ? ' · '.$facility->Office : '' }}</x-ui::badge>
                        @empty
                            <span class="text-sm text-zinc-500">No facilities assigned.</span>
                        @endforelse
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
