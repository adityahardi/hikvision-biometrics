<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Select Checkpoint') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            <div class="mb-2 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                <div></div>
                <div class="flex items-center">
                    <x-input placeholder="Search" wire:model="search"/>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-md sm:rounded-lg p-6">
                <div class="relative overflow-x-auto">
                    <table wire:target="search" class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 whitespace-nowrap">
                        <tr>
                            <th scope="col" class="px-6 py-3">#</th>
                            <th scope="col" class="px-6 py-3">Name</th>
                            <th scope="col" class="px-6 py-3">IP</th>
                            <th scope="col" class="px-6 py-3">Mac Address</th>
                            <th scope="col" class="px-6 py-3">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $no = $checkpoints->firstItem();
                        @endphp
                        @forelse ($checkpoints as $item)
                            <tr wire:loading.class="invisible" wire:key="{{ $item }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 whitespace-nowrap">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $no++ }}</td>
                                <th scope="row" class="px-6 py-4 ">{{ $item->name }}</th>
                                <th scope="row" class="px-6 py-4 ">{{ $item->ip }}</th>
                                <th scope="row" class="px-6 py-4 ">{{ $item?->mac }}</th>
                                <td class="px-6 py-4 flex flex-nowrap gap-2">
                                    <x-button label="Select" wire:navigate :href="route('checkpoints.register-biometric', $item->id)" info size="xs" />
                                    <x-button positive wire:navigate :href="route('checkpoints.edit', $item->id)" label="Edit" size="xs" />
                                    <x-button warning spinner="reboot" wire:click="reboot({{ $item->id }})" label="Reboot" size="xs" />
                                    <x-button negative spinner="deleteEmployee" wire:click="deleteEmployee({{ $item->id }})" label="Delete" size="xs" />
                                </td>
                            </tr>
                        @empty
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td colspan="7" class="px-6 py-4 text-center">
                                    Tidak ada data.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $checkpoints->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
