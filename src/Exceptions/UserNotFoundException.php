<?php

namespace KeycloakGuard\Exceptions;

class UserNotFoundException extends KeycloakGuardException
{
	protected $message = 'User not found. Credentials';

    /**
     * @throws StatusUnavailableException
     */
    public function render()
    {
        return response()->json(['status' => 404, 'code' =>$this->message, 'title' => $this->message],404);
    }

}
