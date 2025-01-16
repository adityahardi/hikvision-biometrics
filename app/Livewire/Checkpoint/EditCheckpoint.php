<?php

namespace App\Livewire\Checkpoint;

use App\Models\Checkpoint;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class EditCheckpoint extends Component
{
    use WireUiActions;
    public Checkpoint $checkpoint;

    public $name = null;
    public $ip = null;
    public $mac = null;
    public $username = null;
    public $password = null;

    public function mount(): void
    {
        $this->fill([
            'name' => $this->checkpoint->name,
            'ip' => $this->checkpoint->ip,
            'mac' => $this->checkpoint->mac,
            'username' => $this->checkpoint->username,
            'password' => $this->checkpoint->password,
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'name' => ['required', 'string'],
            'ip' => ['required', 'string'],
            'mac' => ['nullable', 'string'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $this->checkpoint->update([
            'name' => $this->name,
            'ip' => $this->ip,
            'mac' => $this->mac,
            'username' => $this->username,
            'password' => $this->password,
        ]);

        $this->redirectRoute('checkpoints.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.checkpoint.edit');
    }
}
