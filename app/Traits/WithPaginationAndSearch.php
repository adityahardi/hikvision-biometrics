<?php

namespace App\Traits;

use Livewire\Attributes\Url;
use Livewire\WithPagination;

trait WithPaginationAndSearch
{
    use WithPagination;

    #[Url]
    public ?string $search = '';

    public function updating($property, $value): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }
}
