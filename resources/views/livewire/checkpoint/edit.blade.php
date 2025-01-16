<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-errors />
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Edit') }}
                            </h2>
                        </header>

                        <form wire:submit="update" class="mt-6 space-y-6">
                            <div>
                                <x-input label="Name*" wire:model="name" />
                            </div>
                            <div>
                                <x-input label="Ip Address*" wire:model="ip" />
                            </div>
                            <div>
                                <x-input label="Mac Address" wire:model="mac" />
                            </div>
                            <div>
                                <x-input label="Username" wire:model="username" />
                            </div>
                            <div>
                                <x-password label="Password" wire:model="password" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-button type="submit" primary>
                                    {{ __('Save') }}
                                </x-button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
