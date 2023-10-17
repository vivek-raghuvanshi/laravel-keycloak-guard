<?php

namespace KeycloakGuard\Exceptions;

class ResourceAccessNotAllowedException extends KeycloakGuardException
{

	protected $message = 'JWT token has not a valid.';

    /**
     * @throws StatusUnavailableException
     */
    public function render()
    {
        return response()->json(['status' => 422, 'code' =>$this->message, 'title' => $this->message],422);
    }
}
