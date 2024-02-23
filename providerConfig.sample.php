<?php

require 'vendor/autoload.php';

use League\OAuth2\Client\Provider\GenericProvider;

$provider = new GenericProvider([
    'clientId'                => 'padlist',
    'clientSecret'            => 'xxxxxxxxxxx',
    'redirectUri'             => 'https://list.pad.ifsr.de/callback.php',
    'urlAuthorize'            => 'https://auth.ifsr.de/dex/auth',
    'urlAccessToken'          => 'https://auth.ifsr.de/dex/token',
    'urlResourceOwnerDetails' => 'https://auth.ifsr.de/dex/userinfo',
    'scopes'                  => ['openid', 'profile', 'email'],
    'scopeSeparator'          => ' '
]);

return $provider;
