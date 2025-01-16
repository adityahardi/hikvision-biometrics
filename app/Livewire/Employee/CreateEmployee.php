<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class CreateEmployee extends Component
{
    use WireUiActions;

    public $employeeId = null;
    public $name = null;

    public function store(): void
    {
        $this->validate([
            'employeeId' => ['required', 'string'],
            'name' => ['required', 'string'],
        ]);

        Employee::create([
            'employee_id' => $this->employeeId,
            'name' => $this->name,
        ]);

        $this->redirectRoute('employees.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.employee.create');
    }
}
