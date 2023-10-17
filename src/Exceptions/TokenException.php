<?php

namespace KeycloakGuard\Exceptions;


class TokenException extends KeycloakGuardException
{
	protected $message = 'Unauthorized';

    /**
     * @throws StatusUnavailableException
     */
    public function render()
    {
        return response()->json(['status' => 401, 'code' =>$this->message, 'title' => $this->message],401);
    }
}




