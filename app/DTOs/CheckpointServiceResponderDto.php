<?php

namespace App\DTOs;

class CheckpointServiceResponderDto
{
    public bool $success;

    public ?string $error;

    /**
     * A constructor for the class.
     *
     * @param bool $success The success status of the function.
     * @param string|null $errorType The type of error, if any.
     */
    public function __construct(bool $success = false, ?string $errorType = null)
    {
        $this->success = $success;
        $this->error = $errorType;
    }
}
