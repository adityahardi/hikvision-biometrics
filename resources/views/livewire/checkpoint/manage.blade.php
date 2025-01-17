<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manage') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <x-card title="{{ $checkpoint->name }}" class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2">
                <div class="col-span-2 md:col-span-1 w-full h-full">
                    <form class="space-y-5 p-1 pt-0 gap-2" method="POST" wire:submit="saveAllEmployeeBiometric">
                        @csrf
                        <div>
                            <x-multiselect
                                label="Select employee*"
                                placeholder="Select one or many employee"
                                :options="$this->employeeData"
                                option-label="label"
                                option-value="value"
                                wire-model="employees"
                            />
                        </div>
                        <x-button spinner="deleteAllEmployee" wire:loading.attr="disabled" wire:target="deleteAllEmployee" label="{{ __('Delete All Employee') }}" wire:click="deleteAllEmployee" class="float-left" negative />
                        <x-button type="submit" label="{{ __('Save To Checkpoint') }}" class="float-right" primary />
                    </form>
                </div>
            </div>
        </x-card>
    </div>
</div>
