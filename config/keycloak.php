<?php

return [
    'realm_public_key' => env('KEYCLOAK_REALM_PUBLIC_KEY', false),

    'load_user_from_database' => env('KEYCLOAK_LOAD_USER_FROM_DATABASE', true),

    'user_provider_custom_retrieve_method' => null,

    'user_provider_credential' => env('KEYCLOAK_USER_PROVIDER_CREDENTIAL', 'username'),

    'token_principal_attribute' => env('KEYCLOAK_TOKEN_PRINCIPAL_ATTRIBUTE', 'preferred_username'),

    'append_decoded_token' => env('KEYCLOAK_APPEND_DECODED_TOKEN', false),

    'allowed_resources' => env('KEYCLOAK_ALLOWED_RESOURCES', null),

    'ignore_resources_validation' => env('KEYCLOAK_IGNORE_RESOURCES_VALIDATION', false),

    'leeway' => env('KEYCLOAK_LEEWAY', 0),

    'input_key' => env('KEYCLOAK_TOKEN_INPUT_KEY', null),

    'provide_user' => env('KEYCLOAK_PROVIDE_LOCAL_USER', true),

    'user_id_claim' => env('KEYCLOAK_USER_ID_CLAIM', 'sub'),

    'user_mail_claim' => env('KEYCLOAK_USER_MAIL_CLAIM', 'email'),

    'user_firstname_claim' => env('KEYCLOAK_USER_FIRSTNAME_CLAIM', 'given_name'),

    'user_lastname_claim' => env('KEYCLOAK_USER_LASTNAME_CLAIM', 'family_name'),

    'user_scope_claim' => env('KEYCLOAK_USER_LASTNAME_CLAIM', 'scope'),
  
];
