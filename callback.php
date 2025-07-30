<?php
#error_reporting(E_ALL);
#ini_set('display_errors', 1);


session_start();

$provider = require 'providerConfig.php';

try {
    if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
        throw new Exception('Invalid state');
    }

    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    $resourceOwner = $provider->getResourceOwner($token);
    $userData = $resourceOwner->toArray();


    // Save user data and token in session or database here
    $_SESSION['user'] = $userData["name"];
    $_SESSION['preferred_username'] = $userData["preferred_username"];
    $_SESSION['token'] = $token->getToken();

    // Redirect to index.php
    header('Location: index.php');
    exit();

} catch (Exception $e) {
    // Log the error message and display a generic error message to the user
    error_log($e->getMessage());
    echo '<p>An error occurred during the login process. Please try again.</p>';
    echo '<p><a href="index.php">Try again</a></p>';
}
