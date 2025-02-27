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

        $this->redirectRoute('checkpoints.register-biometric', parameters: ['checkpoint' => $this->checkpoint, 'employee' => $this->employee], navigate: true);
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

    public function employees(): \Illuminate\Support\Collection
    {

        $data = Employee::orderBy('name')->get();

        $icon = <<<HTML
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33" />
            </svg>
        HTML;


        return $data->map(function ($item) use ($icon) {
            $hasBiometric = $item->biometrics->isNotEmpty();

            return [
                'id' => $item->id,
                'name' => $item->name.($hasBiometric ? $icon : ''),
            ];
        });
    }

    public function render(): View
    {
        $employees = Employee::orderBy('name')->paginate(20);

        return view('livewire.employee.register-biometric', compact('employees'))->title('Register Biometric');
    }
}
