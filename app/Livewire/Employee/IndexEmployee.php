<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class IndexEmployee extends Component
{
    public function render(): View
    {
        $employees = Employee::orderBy('name')->paginate(10);

        return view('livewire.employee.index', compact('employees'))->title('Employees');
    }
}
