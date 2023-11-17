<?php

namespace GGInnovative\Larafirebase\Services;

use Google\Client;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Google\Service\FirebaseCloudMessaging;

class Larafirebase
{
    private $title;

    private $body;

    private $image;

    private $additionalData;

    private $token;

    private $topic;

    private $fromRaw;

    public const API_URI = 'https://fcm.googleapis.com/v1/projects/:projectId/messages:send';

    public function withTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function withBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function withImage($image)
    {
        $this->image = $image;

        return $this;
    }

    public function withAdditionalData($additionalData)
    {
        $this->additionalData = $additionalData;

        return $this;
    }

    public function withToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function withTopic($topic)
    {
        $this->topic = $topic;

        return $this;
    }

    public function fromRaw($fromRaw)
    {
        $this->fromRaw = $fromRaw;

        return $this;
    }

    public function sendNotification()
    {
        if ($this->fromRaw) {
            return $this->callApi($this->fromRaw);
        }

        $payload = [
            'message' => [
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                    'image' => $this->image,
                ],
            ],
        ];

        if ($this->token) {
            $payload['message']['token'] = $this->token;
        }

        if ($this->topic) {
            $payload['message']['topic'] = $this->topic;
        }

        if ($this->additionalData) {
            $payload['message']['data'] = $this->additionalData;
        }

        return $this->callApi($payload);
    }

    public function sendNotificationAll()
    {
        if ($this->fromRaw) {
            return $this->callApi($this->fromRaw);
        }

        $payload = [
            'message' => [
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                    'image' => $this->image,
                ],
                'topic' => 'all',
            ],

        ];

        if ($this->additionalData) {
            $payload['message']['data'] = $this->additionalData;
        }

        return $this->callApi($payload);
    }

    public function sendNotificationUser(int $id)
    {
        if ($this->fromRaw) {
            return $this->callApi($this->fromRaw);
        }

        $payload = [
            'message' => [
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                    'image' => $this->image,
                ],
                'topic' => "user_$id",
            ],

        ];

        if ($this->additionalData) {
            $payload['message']['data'] = $this->additionalData;
        }

        return $this->callApi($payload);
    }

    public function sendNotificationUsers(array $ids)
    {
        if ($this->fromRaw) {
            return $this->callApi($this->fromRaw);
        }

        $conditions = [];
        foreach ($ids as $id) {
            $conditions[] = "'user_$id' in topics";
        }

        $payload = [
            'message' => [
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                    'image' => $this->image,
                ],
                "condition" => implode(" || ", $conditions), // Объединяем условия через "||"
            ],
        ];

        if ($this->additionalData) {
            $payload['message']['data'] = $this->additionalData;
        }

        return $this->callApi($payload);
    }
    
    private function getBearerToken()
    {
        $client = new Client();
        $client->setAuthConfig(config('larafirebase.firebase_credentials'));
        $client->addScope(FirebaseCloudMessaging::CLOUD_PLATFORM);

        $savedToken = Cache::get('LARAFIREBASE_AUTH_TOKEN');

        if (!$savedToken) {
            $accessToken = $this->generateNewBearerToken($client);
            $client->setAccessToken($accessToken);

            return $accessToken;
        }

        $client->setAccessToken($savedToken);

        if (!$client->isAccessTokenExpired()) {
            return json_decode($savedToken)->access_token;
        }

        $newAccessToken = $this->generateNewBearerToken($client);
        $client->setAccessToken($newAccessToken);
        return $newAccessToken['access_token'];

    }

    private function generateNewBearerToken($client)
    {
        $client->fetchAccessTokenWithAssertion();
        $accessToken = $client->getAccessToken();

        $tokenJson = json_encode($accessToken);
        Cache::add('LARAFIREBASE_AUTH_TOKEN', $tokenJson);

        return $accessToken;
    }

    private function callApi($fields): Response
    {
        $apiURL = str_replace(':projectId', config('larafirebase.project_id'), self::API_URI);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getBearerToken()
        ])->post($apiURL, $fields);

        return $response;
    }
}
