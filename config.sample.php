<?php

# Example oauth2 config for authentik
$providerConfig = [
    'clientId'                => 'CLIENT_ID',
    'clientSecret'            => 'CLIENT_SECRET',
    'redirectUri'             => 'https://padlist.your-domain.com/callback.php',
    'urlAuthorize'            => 'https://auth.your-domain.com/application/o/authorize/',
    'urlAccessToken'          => 'https://auth.your-domain.com/application/o/token/',
    'urlResourceOwnerDetails' => 'https://auth.your-domain.com/application/o/userinfo/',
    'scopes'                  => ['openid', 'profile', 'email'],
    'scopeSeparator'          => ' '
];

$dbHost = '/run/postgresql';
$dbName = 'hedgedoc';
$dbUser = 'hedgedoc';
$dbPass = null;

$hedgedocUrl = 'https://pad.your-domain.com';

$showLogout = false;