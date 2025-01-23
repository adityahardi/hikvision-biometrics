<?php

namespace App\Livewire\Checkpoint;

use App\Models\Checkpoint;
use App\Services\Checkpoint\CheckpointService;
use App\Traits\WithPaginationAndSearch;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class IndexCheckpoint extends Component
{
    use WireUiActions, WithPaginationAndSearch;

    public function deleteEmployee(Checkpoint $checkpoint, $confirmed = false): void
    {
        if (!$confirmed) {
            $this->dialog()->confirm([
                'title' => 'Delete All User',
                'description' => 'Are you sure you want to delete all user in ' . $checkpoint->name . '?, This action cannot be undone.',
                'icon' => 'error',
                'accept' => [
                    'label' => 'Yes, delete',
                    'method' => 'deleteEmployee',
                    'params' => [$checkpoint->id, true],
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
    }

    public function reboot(Checkpoint $checkpoint, $confirmed = false): void
    {
        if (!$confirmed) {
            $this->dialog()->confirm([
                'title' => 'Reboot device?',
                'description' => 'Are you sure you want to reboot this device ' . $checkpoint->name,
                'icon' => 'warning',
                'accept' => [
                    'label' => 'Reboot now',
                    'method' => 'reboot',
                    'params' => [$checkpoint->id, true],
                ],
            ]);

            return;
        }

        $rebootDevice = (new CheckpointService())->rebootCheckpoint($checkpoint);

        if (!$rebootDevice->success) {
            $this->notification()->error(
                $title = __('Failed To Reboot device ') . $checkpoint->name,
                $description = __('This device could not be rebooted. Please check the checkpoint data and try again!'),
            );

            return;
        }

        $this->notification()->success(
            $title = __('Rebooting device ') . $checkpoint->name,
            $description = __('The device is rebooting. Please wait a moment!'),
        );
    }

    public function render(): View
    {
        $checkpoints = Checkpoint::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('ip', 'like', '%'.$this->search.'%')
                    ->orWhere('mac', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('livewire.checkpoint.index', compact('checkpoints'))->title('Checkpoints');
    }
}
