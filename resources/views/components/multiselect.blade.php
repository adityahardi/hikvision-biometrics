@props(['options', 'label' => null, 'wireModel', 'placeholder' => '', 'optionLabel' => 'label', "optionValue" => 'value', 'optionDescription' => 'description', 'selectAllLabel' => 'Select All' ])

<div x-data="{
        customSelectedOptions: [],
        customDisplayOptions: @js($options),
        selectAll() { this.customSelectedOptions = this.customDisplayOptions, this.$wire.{{ $wireModel }} = this.customDisplayOptions.map(g => g.value) },
        removeEach(v) { this.customSelectedOptions = this.customSelectedOptions.filter(g => g.value !== v), this.$wire.{{ $wireModel }} = this.$wire.{{ $wireModel }}.filter(g => g !== v) }
    }">
    <div class="flex gap-1 justify-between">
        <x-select
            :label="$label"
            :placeholder="$placeholder"
            multiselect
            :options="$options"
            :option-label="$optionLabel"
            :option-value="$optionValue"
            :option-description="$optionDescription"
            wire:model="{{ $wireModel }}"
            x-init="$watch('selectedOptions', v => customSelectedOptions = v), customSelectedOptions = selectedOptions"
        />
        <div class="whitespace-nowrap {{ $label ? 'pt-[25px]' : '0px' }}">
            <x-button x-on:click="selectAll" :label="$selectAllLabel" cyan class="items-start justify-normal" />
        </div>
    </div>
    <div class="flex flex-wrap gap-1 mt-2">
        <template x-for="customSelectedOpt in customSelectedOptions">
            <div>
                <x-badge flat gray>
                    <span class="text-sm" x-text="customSelectedOpt.label"></span>
                    <x-slot name="append" class="relative flex items-center w-2 h-2">
                        <button type="button" x-on:click="removeEach(customSelectedOpt.value)">
                            <x-icon name="x-mark" class="w-4 h-4" />
                        </button>
                    </x-slot>
                </x-badge>
            </div>
        </template>
    </div>
</div>
