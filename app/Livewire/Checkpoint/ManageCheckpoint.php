<?php

namespace App\Livewire\Checkpoint;

use App\Helpers\CheckpointHelper;
use App\Models\Checkpoint;
use App\Models\Employee;
use App\Services\Checkpoint\CheckpointService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class ManageCheckpoint extends Component
{
    use WireUiActions;

    public Checkpoint $checkpoint;
    public $employees = null;

    protected CheckpointService $checkpointService;

    public function boot(): void
    {
        $this->checkpointService = new CheckpointService;
    }

    public function saveAllEmployeeBiometric(): void
    {
        $this->validate([
            'employees' => ['required'],
        ]);

        $employees = Employee::whereIn('id', $this->employees)->get();
        $checkpoint = $this->checkpoint;

        $employees->each(function (Employee $employee) use ($checkpoint) {
            $syncStatus = $this->checkpointService->syncUserCheckpoint($checkpoint, $employee);

            if ($syncStatus?->success) {
                $this->notification()->success(
                    title: __('Success on '. $employee->name),
                    description:  __("Assign to checkpoint {$checkpoint->name} successfully."),
                );
            } else {
                $title = __('Failed on '. $employee->name);
                $syncStatusError = $syncStatus?->error;

                $description = match ($syncStatusError) {
                    CheckpointHelper::ERROR_USER_DELETE => __("Assign to checkpoint {$checkpoint->name} failed. checkpoint offline."),
                    CheckpointHelper::ERROR_USER_CREATE => __("Assign to checkpoint {$checkpoint->name} failed. failed to create user checkpoint."),
                    CheckpointHelper::ERROR_FACE => __("Assign to checkpoint {$checkpoint->name} failed. failed to create user face checkpoint."),
                    CheckpointHelper::ERROR_FINGERPRINT => __("Assign to checkpoint {$checkpoint->name} failed. failed to create user fingerprint checkpoint."),
                    CheckpointHelper::ERROR_CARD => __("Assign to checkpoint {$checkpoint->name} failed. failed to create user card checkpoint."),
                    default => __("Assign to checkpoint {$checkpoint->name} failed."),
                };

                $this->notification()->error($title, $description);
            }
        });
    }

    public function deleteAllEmployee($confirmed = false): void
    {
        $checkpoint = $this->checkpoint;

        if (!$confirmed) {
            $this->dialog()->confirm([
                'title' => 'Delete All User',
                'description' => 'Are you sure you want to delete all user in ' . $checkpoint->name . '?, This action cannot be undone.',
                'icon' => 'error',
                'accept' => [
                    'label' => 'Yes, delete',
                    'method' => 'deleteAllEmployee',
                    'params' => [true],
                ],
            ]);

            return;
        }

        $deleteAllUser = (new CheckpointService())->deleteAllUserCheckpoint($checkpoint);

        if (!$deleteAllUser->success) {
            $this->notification()->error(
                $title = __('Failed To Delete All User in ') . $checkpoint->name,
                $description = __('All User could not be deleted. Please check the checkpoint data and try again!'),
            );
        }

        $this->notification()->success(
            title: __('Success delete all user'),
            description:  __("All user in {$checkpoint->name} has been deleted successfully."),
        );
    }

    #[Computed]
    public function employeeData(): Collection
    {
        return Employee::orderBy('name')->whereHas('biometrics')
            ->get(['id as value', 'name as label']);
    }

    public function render(): View
    {
        return view('livewire.checkpoint.manage');
    }
}
