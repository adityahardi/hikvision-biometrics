<?php

namespace App\Livewire\Employee;

use App\Helpers\CheckpointHelper;
use App\Models\Checkpoint;
use App\Models\Employee;
use App\Models\EmployeeBiometric;
use App\Services\Checkpoint\CheckpointService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class RegisterBiometric extends Component
{
    use WireUiActions, WithFileUploads;
    public Checkpoint $checkpoint;

    #[Url]
    public $employee = null;
    public ?string $faceBiometric = null;

    public ?bool $faceCaptureModal = null;

    protected CheckpointService $checkpointService;

    public function boot(): void
    {
        $this->checkpointService = new CheckpointService;
    }

    public function mount(): void
    {
        $employee = Employee::find($this->employee);

        $this->faceBiometric = $employee->faceBiometric?->data ?? null;
    }

    public function updated(): void
    {
        $employee = Employee::find($this->employee);

        $this->faceBiometric = $employee->faceBiometric?->data ?? null;
    }

    public $newFaceData = null;

    public function scanFace(): void
    {
        if (!$this->employee) {
            $this->notification()->error(__("Employee not found"), __('Please select an employee and try again!'));
            return;
        }

        try {
            $newFaceData = CheckpointHelper::captureFace($this->checkpoint);
        } catch (\Throwable $th) {
            $this->dialog()->error(__("Cannot connect to the checkpoint"), __('Please check the checkpoint data and try again!'));
            return;
        }

        if (!$newFaceData->success) {
            $this->dialog()->error(__('Failed to capture face data'), __('Please check the checkpoint data and try again!'));
        } else {
            $this->newFaceData = $newFaceData->data;
        }
    }

    public function saveFaceBiometric(): void
    {
        if (!$this->newFaceData) {
            throw ValidationException::withMessages([
                'newFaceData' => __('Please capture the face data first!'),
            ]);
        }

        if (!is_string($this->newFaceData)) {
            $this->validate([
                'newFaceData' => 'required|file|image|max:2024',
            ]);

            $this->newFaceData = $this->newFaceData->store(CheckpointHelper::STORAGE_PATH);
        }

        if ($this->faceBiometric) {
            Storage::delete($this->faceBiometric);
        }

        $faceData = Storage::get($this->newFaceData);

        $base64 = base64_encode($faceData);

        $employee = Employee::find($this->employee);

        if (!$employee) {
            $this->notification()->error(
                $title = __('Employee not found'),
                $description = __('Please select an employee and try again!'),
            );

            return;
        }

        $employee->faceBiometric()->updateOrCreate(
            [
                'type' => EmployeeBiometric::TYPE_FACE,
            ],
            [
                'type' => EmployeeBiometric::TYPE_FACE,
                'data' => $base64,
            ],
        );

        Storage::delete($this->newFaceData);

        $this->faceBiometric = $this->newFaceData;
        $this->faceCaptureModal = null;
        $this->newFaceData = null;

        $this->notification()->success(
            $title = __('Face Biometric'),
            $description = __('Face Biometric saved successfully.'),
        );

        $employee = Employee::find($this->employee);

        $syncUserFaceBiometric = (new CheckpointService())->syncUserFaceBiometric($this->checkpoint, $employee);

        if (!$syncUserFaceBiometric->success) {
            $this->notification()->error(
                $title = __('Failed Store Face Biometric ') . $this->checkpoint->name,
                $description = __('Face Biometric could not be stored. Please check the checkpoint data and try again!'),
            );
        }
    }

    public function cancelFaceBiometric(): void
    {
        $this->resetValidation();

        $this->faceCaptureModal = null;
        $this->newFaceData = null;

        if ($this->newFaceData) {
            Storage::delete($this->newFaceData);
        }
    }

    public function cancelled(): void
    {
        $this->notification()->error(
            title:  __('The action has been cancelled'),
        );
    }

    public function confirmDeleteFaceBiometric(): void
    {
        $this->dialog()->confirm([
            'title' => "Are you sure want to delete?",
            'description' => "Are you sure want to delete face biometric data?",
            'icon' => 'question',
            'accept' => [
                'label' => 'Yes, delete',
                'method' => 'doDeleteFaceBiometric',
            ],
            'reject' => [
                'label' => 'No, cancel',
                'method' => 'cancelled',
            ],
        ]);
    }

    public function doDeleteFaceBiometric(): void
    {
        dd('delete face');
        try {
            $this->employee->faceBiometric()->delete();

            if ($this->faceBiometric) {
                Storage::delete($this->faceBiometric);
            }

            $this->faceBiometric = null;
        } catch (\Throwable $th) {
            Log::error($th);
        }

        if (!$this->employee->faceBiometric) {
            $this->notification()->success(
                $title = __('Face Deleted'),
                $description = __('Face Biometric has been deleted successfully.'),
            );
        }
    }

    public function saveToCheckpoint(): void
    {
        $checkpoint = $this->checkpoint;
        $employee = Employee::find($this->employee);

        if (!$employee) {
            $this->notification()->error(
                $title = __('Employee not found'),
                $description = __('Please select an employee and try again!'),
            );

            return;
        }

        $syncStatus = $this->checkpointService->syncUserCheckpoint($checkpoint, $employee);


        if ($syncStatus?->success) {
            $this->notification()->success(
                $title = __('Success'),
                $description = __("Assign to checkpoint {$checkpoint->name} successfully."),
            );
        } else {
            $title = __('Failed');
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
    }

    #[Computed]
    public function employees(): \Illuminate\Support\Collection
    {
        return Employee::orderBy('name')->get(['id', 'name']);
    }

    public function render(): View
    {
        $employees = Employee::orderBy('name')->paginate(20);

        return view('livewire.employee.register-biometric', compact('employees'))->title('Register Biometric');
    }
}
