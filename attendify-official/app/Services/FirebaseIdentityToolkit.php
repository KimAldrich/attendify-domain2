<?php

namespace App\Services;

use GuzzleHttp\Client;
use RuntimeException;

class FirebaseIdentityToolkit
{
    private Client $http;
    private string $apiKey;

    public function __construct()
    {
        // From config/services.php
        $this->apiKey = (string) config('services.firebase.web_api_key', '');

        if ($this->apiKey === '') {
            // fallback to env if config not set
            $this->apiKey = (string) env('FIREBASE_WEB_API_KEY', '');
        }

        if ($this->apiKey === '') {
            throw new RuntimeException('Firebase web_api_key is missing (services.firebase.web_api_key).');
        }

        $this->http = new Client([
            'base_uri'        => 'https://identitytoolkit.googleapis.com/v1/',
            'timeout'         => 10,
            'connect_timeout' => 5,
        ]);
    }

    /** Verify that the oobCode is valid, returns ['email' => ...] on success */
    public function verifyPasswordResetCode(string $oobCode): array
    {
        $resp = $this->http->post('accounts:resetPassword', [
            'query' => ['key' => $this->apiKey],
            'json'  => ['oobCode' => $oobCode],
        ]);
        return json_decode((string) $resp->getBody(), true);
    }

    /** Confirm password reset with new password */
    public function confirmPasswordReset(string $oobCode, string $newPassword): array
    {
        $resp = $this->http->post('accounts:resetPassword', [
            'query' => ['key' => $this->apiKey],
            'json'  => [
                'oobCode'     => $oobCode,
                'newPassword' => $newPassword,
            ],
        ]);
        return json_decode((string) $resp->getBody(), true);
    }
}
