<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Traits\WithPaginationAndSearch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class IndexEmployee extends Component
{
    use WithPaginationAndSearch;

    #[Url]
    public $hasBiometric = false;

    public function render(): View
    {
        $employees = Employee::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('employee_id', 'like', '%'.$this->search.'%');
            })->when($this->hasBiometric, function ($query) {
                $query->whereHas('biometrics');
            })
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('livewire.employee.index', compact('employees'))->title('Employees');
    }
}
