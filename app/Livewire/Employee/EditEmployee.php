<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class EditEmployee extends Component
{
    use WireUiActions;
    public Employee $employee;

    public $employeeId = null;
    public $name = null;

    public function mount(): void
    {
        $this->fill([
            'employeeId' => $this->employee->employee_id,
            'name' => $this->employee->name,
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'employeeId' => ['required', 'string'],
            'name' => ['required', 'string'],
        ]);

        $this->employee->update([
            'employee_id' => $this->employeeId,
            'name' => $this->name,
        ]);

        $this->redirectRoute('employees.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.employee.edit');
    }
}
