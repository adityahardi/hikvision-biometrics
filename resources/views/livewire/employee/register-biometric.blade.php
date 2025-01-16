<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Register') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <x-card title="{{ __('Biometric Data') }}" class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2">
                <div class="col-span-2 md:col-span-1 w-full h-full">
                    <div class="p-3 pt-0">
                        <label class="block mb-2 text-lg font-medium text-gray-700 dark:text-gray-400">Employees</label>

                        <div>
                            <x-select
                                placeholder="Select one employee"
                                wire:model.live="employee"
                                :options="$this->employees"
                                option-label="name"
                                option-value="id"
                            />
                            <x-button type="button" size="xs" wire:click="saveToCheckpoint" wire:loading.attr="disabled" wire:target="saveToCheckpoint" :label="__('Save')"
                                      class="mt-3 mx-auto w-full" positive />
                        </div>
                    </div>
                </div>
                <div class="col-span-2 md:col-span-1 w-full h-full mt-10 md:mt-0">
                    <x-errors only="faceBiometric" />
                    <div class="max-w-xs p-3 pt-0">
                        <label class="block text-lg font-medium text-gray-700 dark:text-gray-400">Face Biometric</label>
                        @if ($faceBiometric)
                            <img src="{{ 'data:image/' . 'jpeg' . ';base64,' . $faceBiometric }}" alt="Face" class="rounded-md">
                        @else
                            <div
                                class="flex items-center rounded-md aspect-[22/27] justify-center bg-gray-300 dark:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 text-gray-200 dark:text-gray-400"
                                     width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                     fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M15 8h.01"></path>
                                    <path d="M13 21h-7a3 3 0 0 1 -3 -3v-12a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v7"></path>
                                    <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l3 3"></path>
                                    <path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0"></path>
                                    <path d="M22 22l-5 -5"></path>
                                    <path d="M17 22l5 -5"></path>
                                </svg>
                            </div>
                        @endif
                        <div class="flex items-center">
                            <x-button type="button"
                                      label="{{ $faceBiometric ? __('Change Face Biometric') : __('Add Face Biometric') }}"
                                      class="mt-3 mx-auto w-full" wire:click="$set('faceCaptureModal', true)" warning />
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
{{--    <div class="py-12">--}}
{{--        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">--}}
{{--            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-md sm:rounded-lg p-6">--}}
{{--                <div class="relative overflow-x-auto">--}}
{{--                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">--}}
{{--                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 whitespace-nowrap">--}}
{{--                        <tr>--}}
{{--                            <th scope="col" class="px-6 py-3">#</th>--}}
{{--                            <th scope="col" class="px-6 py-3">Employee ID</th>--}}
{{--                            <th scope="col" class="px-6 py-3">Name</th>--}}
{{--                            <th scope="col" class="px-6 py-3">Action</th>--}}
{{--                        </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody>--}}
{{--                        @php--}}
{{--                            $no = $employees->firstItem();--}}
{{--                        @endphp--}}
{{--                        @forelse ($employees as $item)--}}
{{--                            <tr wire:loading.class="invisible" wire:key="{{ $item }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 whitespace-nowrap">--}}
{{--                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $no++ }}</td>--}}
{{--                                <th scope="row" class="px-6 py-4 ">{{ $item->employee_id }}</th>--}}
{{--                                <th scope="row" class="px-6 py-4 ">{{ $item->name }}</th>--}}
{{--                                <td class="px-6 py-4 flex flex-nowrap gap-2">--}}
{{--                                    <x-button label="Edit" info size="sm" />--}}
{{--                                </td>--}}
{{--                            </tr>--}}
{{--                        @empty--}}
{{--                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">--}}
{{--                                <td colspan="7" class="px-6 py-4 text-center">--}}
{{--                                    Tidak ada data.--}}
{{--                                </td>--}}
{{--                            </tr>--}}
{{--                        @endforelse--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--                <div class="mt-3">--}}
{{--                    {{ $employees->links() }}--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

    <x-modal-card title="Face Capture" blur wire:model.defer="faceCaptureModal"
                  x-on:close="() => document.getElementById('cancelFaceBiometric').click()">
        <x-errors only="newFaceData" />
        <div>
            <x-button wire:target="scanFace" wire:loading.class="hidden" wire:click="scanFace" green class="w-full mt-5"
                      :label="$newFaceData ? __('Rescan Face') : __('Scan Face')" />
            <x-button wire:target="scanFace" wire:loading.class.remove="hidden" disabled green
                      class="w-full mt-5 !cursor-wait hidden" :label="__('Loading...')" />
        </div>

        <div class="mt-4">
            <div class="flex items-center mb-2">
                <hr class="w-1/2 h-0.5 my-2 bg-gray-200 border-0 rounded dark:bg-gray-700">
                <span class="mx-2 text-gray-900 dark:text-white">or</span>
                <hr class="w-1/2 h-0.5 my-2 bg-gray-200 border-0 rounded dark:bg-gray-700">
            </div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-400 text-center"
                   for="newFaceData">Upload Picture</label>
            <div x-data="{ uploading: false, progress: 0 }" x-on:livewire-upload-start="uploading = true"
                 x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false"
                 x-on:livewire-upload-progress="progress = $event.detail.progress">
                <x-input wire:model="newFaceData"
                       id="newFaceData" type="file"/>

                <div x-show="uploading">
                    <progress class="w-full" max="100" x-bind:value="progress"></progress>
                </div>
            </div>
        </div>

        @if ($newFaceData)
            @if (!is_string($newFaceData))
                <div class="mt-5 items-center">
                    <img src="{{ $newFaceData->temporaryUrl() }}" alt="Face" class="mx-auto rounded-md">
                </div>
            @else
                <div class="mt-5 items-center">
                    <img src="{{ Storage::url($newFaceData) }}" alt="Face" class="mx-auto rounded-md">
                </div>
            @endif
        @endif

        <x-slot name="footer">
            <div class="flex justify-end gap-x-4">
                <div class="flex">
                    <button id="cancelFaceBiometric" hidden wire:click="cancelFaceBiometric"></button>
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="Save" wire:click="saveFaceBiometric" />
                </div>
            </div>
        </x-slot>
    </x-modal-card>
</div>
