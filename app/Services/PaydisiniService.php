<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PaydisiniService
{
    private $apiUrl;
    private $apiKey;
    private $client;

    /**
     * Constructor to initialize API URL and key.
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiUrl = 'https://api.paydisini.co.id/v1/';
        $this->apiKey = $apiKey;
        $this->client = new Client();
    }

    /**
     * Generate signature for API requests.
     *
     * @param string $uniqueCode
     * @param string $suffix
     * @return string
     */
    private function generateSignature(string $uniqueCode, string $suffix): string
    {
        return md5($this->apiKey . $uniqueCode . $suffix);
    }

    /**
     * Create a new transaction.
     *
     * @param array $data
     * @return array
     */
    public function createNewTransaction(array $data): array
    {
        $signature = md5(
            $this->apiKey . $data['unique_code'] . $data['service'] . $data['amount'] . $data['valid_time'] . 'NewTransaction'
        );

        $postData = array_merge($data, [
            'key' => $this->apiKey,
            'request' => 'new',
            'signature' => $signature,
        ]);

        return $this->sendRequest($postData);
    }

    /**
     * Check the status of a transaction.
     *
     * @param string $uniqueCode
     * @return array
     */
    public function checkTransactionStatus(string $uniqueCode): array
    {
        $postData = [
            'key' => $this->apiKey,
            'request' => 'status',
            'unique_code' => $uniqueCode,
            'signature' => $this->generateSignature($uniqueCode, 'StatusTransaction'),
        ];

        return $this->sendRequest($postData);
    }

    /**
     * Cancel a transaction.
     *
     * @param string $uniqueCode
     * @return array
     */
    public function cancelTransaction(string $uniqueCode): array
    {
        $postData = [
            'key' => $this->apiKey,
            'request' => 'cancel',
            'unique_code' => $uniqueCode,
            'signature' => $this->generateSignature($uniqueCode, 'CancelTransaction'),
        ];

        return $this->sendRequest($postData);
    }

    /**
     * Send request to the API and handle response.
     *
     * @param array $postData
     * @return array
     */
    private function sendRequest(array $postData): array
    {
        try {
            $response = $this->client->post($this->apiUrl, [
                'form_params' => $postData,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'error' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ];
        }
    }
}