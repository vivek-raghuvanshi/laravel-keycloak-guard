<?php

namespace KeycloakGuard;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use KeycloakGuard\Exceptions\ResourceAccessNotAllowedException;
use KeycloakGuard\Exceptions\TokenException;
use KeycloakGuard\Exceptions\UserNotFoundException;

class KeycloakGuard implements Guard
{
    protected $config;
    protected $user;
    protected $provider;
    protected $decodedToken;
    protected Request $request;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->config = config('keycloak');
        $this->user = null;
        $this->provider = $provider;
        $this->decodedToken = null;
        $this->request = $request;

        $this->authenticate();
    }

    /**
     * Decode token, validate and authenticate user
     *
     * @return mixed
     */
    protected function authenticate()
    {

        try {
             $this->decodedToken = Token::decode($this->getTokenForRequest(), $this->config['realm_public_key'], $this->config['leeway']);
        } catch (\Exception $e) {
            throw new TokenException($e->getMessage());
        }

        if ($this->decodedToken) {
            $this->validate([
                $this->config['user_provider_credential'] => $this->decodedToken->{$this->config['token_principal_attribute']}
            ]);
        }
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    public function getTokenForRequest()
    {
        $inputKey = $this->config['input_key'] ?? "";

        return $this->request->bearerToken() ?? $this->request->input($inputKey);
    }

    /**
       * Determine if the current user is authenticated.
       *
       * @return bool
       */
    public function check()
    {
        return !is_null($this->user());
    }

    /**
     * Determine if the guard has a user instance.
     *
     * @return bool
     */
    public function hasUser()
    {
        return !is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->check();
    }

     /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (is_null($this->user)) {
            return null;
        }

        if ($this->config['append_decoded_token']) {
            $this->user->token = $this->decodedToken;
        }

        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        if ($user = $this->user()) {
            return $this->user()->id;
        }
    }

     /**
     * Returns full decoded JWT token from athenticated user
     *
     * @return mixed|null
     */
    public function token()
    {
        return json_encode($this->decodedToken);
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $this->validateResources();

        $this->upsertUser();
        if ($this->config['load_user_from_database']) {
            $methodOnProvider = $this->config['user_provider_custom_retrieve_method'] ?? null;

            if ($methodOnProvider) {
                $user = $this->provider->{$methodOnProvider}($this->decodedToken, $credentials);
            } else {
                $user = $this->provider->retrieveByCredentials($credentials);
            }

            if (!$user) {
                throw new UserNotFoundException("User not found. Credentials: ".json_encode($credentials));
            }
        } else {
            $class = $this->provider->getModel();
            $user = new $class();
        }

        $this->setUser($user);

        return true;
    }

    /**
     * Validate if authenticated user has a valid resource
     *
     * @return void
     */
    protected function validateResources()
    {
        if ($this->config['ignore_resources_validation']) {
            return;
        }
        return true;

        $token_resource_access = array_keys((array)($this->decodedToken->resource_access ?? []));
        $allowed_resources = explode(',', $this->config['allowed_resources']);

        if (count(array_intersect($token_resource_access, $allowed_resources)) == 0) {
            throw new ResourceAccessNotAllowedException("The decoded JWT token has not a valid `resource_access` allowed by API. Allowed resources by API: ".$this->config['allowed_resources']);
        }
    }

    /**
     * Check if authenticated user has a especific role into resource
     * @param string $resource
     * @param string $role
     * @return bool
     */
    public function hasRole($resource, $role)
    {
        $token_resource_access = (array)$this->decodedToken->resource_access;

        if (array_key_exists($resource, $token_resource_access)) {
            $token_resource_values = (array)$token_resource_access[$resource];

            if (array_key_exists('roles', $token_resource_values) &&
              in_array($role, $token_resource_values['roles'])) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Check if authenticated user has a any role into resource
     * @param string $resource
     * @param string $role
     * @return bool
     */
    public function hasAnyRole($resource, array $roles)
    {
        $token_resource_access = (array)$this->decodedToken->resource_access;

        if (array_key_exists($resource, $token_resource_access)) {
            $token_resource_values = (array)$token_resource_access[$resource];

            if (array_key_exists('roles', $token_resource_values)) {
                foreach ($roles as $role) {
                    if (in_array($role, $token_resource_values['roles'])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
    public function scopes(): array
    {
        $scopes = $this->decodedToken->scope ?? null;

        if($scopes) {
            return explode(' ', $scopes);
        }

        return [];
    }

    public function hasScope(string|array $scope): bool
    {
        return count(array_intersect(
                         $this->scopes(),
                         is_string($scope) ? [$scope] : $scope
                     )) > 0;
    }

     public function roles(bool $useGlobal = true): array
    {
        $global_roles = [];
        $client_roles = $this->decodedToken?->resource_access?->{$this->getClientName()}?->roles ?? [];

        if($useGlobal) {
            $global_roles = $this->decodedToken?->realm_access?->roles ?? [];
        }

       //$global_roles = $useGlobal === true ? $this->decodedToken?->realm_access?->roles ?? [] : [];

        return array_unique(
            array_merge(
                $global_roles,
                $client_roles
            )
        );
    }


    public function getRoles(): array
    {
        return $this->roles(false);
    }

    private function upsertUser(): void
    {
            
        
        if($this->config['provide_user'] === false) {
            return;
        }

       $userId = $this->decodedToken->{$this->config['user_id_claim']};
       $name=explode(' ',$this->decodedToken->{$this->config['user_firstname_claim']});
       $role = $this->roles();

        $this->user = \App\Models\User::updateOrCreate(
            ['keycloak_id' => $userId],
            [
                'keycloak_id' => $userId,
                'UserName' => $this->decodedToken->{$this->config['token_principal_attribute']},
                'OfficialEmail' => $this->decodedToken->{$this->config['user_mail_claim']},
                'FirstName' => $name[0]??null,
                'LastName' => $name[1]??null,
                'role_fk' => $role[0],
                'scope' => $this->decodedToken->{$this->config['user_scope_claim']},
                'user_data' => json_encode($this->decodedToken),
            ]
        );
    }

    private function getClientName(): string|null
    {
        return $this->decodedToken->{$this->config['token_principal_attribute']};
    }
}
