<?php
class PayMongoHelper {
    private $secretKey;
    private $publicKey;
    private $apiUrl = 'https://api.paymongo.com/v1/';
    private $client;

    public function __construct($secretKey, $publicKey) {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':')
            ]
        ]);
    }

    public function createPaymentIntent($amount, $description, $metadata = []) {
        $amountInCents = $amount * 100;
        
        try {
            $payload = [
                'data' => [
                    'attributes' => [
                        'amount' => $amountInCents,
                        'payment_method_allowed' => ['card', 'gcash', 'grab_pay'],
                        'payment_method_options' => [
                            'card' => ['request_three_d_secure' => 'any']
                        ],
                        'currency' => 'PHP',
                        'description' => $description,
                        'capture_type' => 'automatic'
                    ]
                ]
            ];

            // Add metadata as flat key-value pairs
            if (!empty($metadata)) {
                $payload['data']['attributes']['metadata'] = [];
                foreach ($metadata as $key => $value) {
                    // Convert all values to strings (PayMongo requirement)
                    $payload['data']['attributes']['metadata'][$key] = (string)$value;
                }
            }

            $response = $this->client->post('payment_intents', [
                'json' => $payload
            ]);

            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $error = json_decode($response->getBody(), true);
            throw new Exception($error['errors'][0]['detail'] ?? 'Payment intent creation failed');
        }
    }

    public function attachPaymentMethod($paymentIntentId, $paymentMethodId, $returnUrl = null) {
        try {
            $response = $this->client->post("payment_intents/$paymentIntentId/attach", [
                'json' => [
                    'data' => [
                        'attributes' => [
                            'payment_method' => $paymentMethodId,
                            'return_url' => $returnUrl
                        ]
                    ]
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $error = json_decode($response->getBody(), true);
            throw new Exception($error['errors'][0]['detail'] ?? 'Payment method attachment failed');
        }
    }

    public function createPaymentMethod($type, $billingDetails = [], $metadata = []) {
        $data = [
            'data' => [
                'attributes' => [
                    'type' => $type
                ]
            ]
        ];

        // Add billing details if provided
        if (!empty($billingDetails)) {
            $data['data']['attributes']['billing'] = $billingDetails;
        }

        // Add metadata if provided
        if (!empty($metadata)) {
            $data['data']['attributes']['metadata'] = $metadata;
        }

        try {
            $response = $this->client->post('payment_methods', [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':')
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $error = json_decode($response->getBody(), true);
            throw new Exception($error['errors'][0]['detail'] ?? 'Payment method creation failed');
        }
    }
}