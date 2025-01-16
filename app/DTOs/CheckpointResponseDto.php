<?php

namespace App\DTOs;

use Illuminate\Http\Client\Response;

class CheckpointResponseDto
{
    public bool $success;

    public mixed $data;

    public Response $response;

    /**
     * Constructor for the class.
     *
     * @param Response $response The response object.
     * @param bool $success The success flag. Default is false.
     * @param mixed $data The data. Default is null.
     */
    public function __construct(Response $response, bool $success = false, mixed $data = null)
    {
        $this->success = $success;
        $this->data = $data;
        $this->response = $response;
    }
}
