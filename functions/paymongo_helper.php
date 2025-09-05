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

    public function createCheckoutSession($amount, $description, $options = []) {
        $amountInCents = $amount * 100;
        
        $data = [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'description' => $description,
                    'line_items' => [
                        [
                            'amount' => $amountInCents,
                            'currency' => 'PHP',
                            'name' => 'Order Payment',
                            'quantity' => 1
                        ]
                    ],
                    'payment_method_types' => $options['payment_method_types'] ?? ['card', 'gcash', 'grab_pay', 'paymaya', 'qrph'],
                    'success_url' => $options['success_url'] ?? ($_ENV['APP_URL'] . '/order_receipt.php'),
                    'cancel_url' => $options['cancel_url'] ?? ($_ENV['APP_URL'] . '/order_receipt.php'),
                    'metadata' => $options['metadata'] ?? []
                ]
            ]
        ];

        // Add customer information if provided
        if (isset($options['customer_info'])) {
            $customerInfo = $options['customer_info'];
            
            // Initialize customer_info array
            $data['data']['attributes']['customer_info'] = [];
            
            // Add individual customer fields
            if (isset($customerInfo['first_name'])) {
                $data['data']['attributes']['customer_info']['first_name'] = $customerInfo['first_name'];
            }
            
            if (isset($customerInfo['last_name'])) {
                $data['data']['attributes']['customer_info']['last_name'] = $customerInfo['last_name'];
            }
            
            if (isset($customerInfo['email'])) {
                $data['data']['attributes']['customer_info']['email'] = $customerInfo['email'];
                $data['data']['attributes']['send_email_receipt'] = true;
                $data['data']['attributes']['receipt_email'] = $customerInfo['email'];
            }
            
            if (isset($customerInfo['phone'])) {
                $data['data']['attributes']['customer_info']['phone'] = $customerInfo['phone'];
            }
        }

        // Add billing information if provided
        if (isset($options['billing'])) {
            $billing = $options['billing'];
            $data['data']['attributes']['billing'] = [];
            
            if (isset($billing['name'])) {
                $data['data']['attributes']['billing']['name'] = $billing['name'];
            }
            
            if (isset($billing['email'])) {
                $data['data']['attributes']['billing']['email'] = $billing['email'];
            }
            
            if (isset($billing['phone'])) {
                $data['data']['attributes']['billing']['phone'] = $billing['phone'];
            }
            
            if (isset($billing['address'])) {
                $data['data']['attributes']['billing']['address'] = $billing['address'];
            }
        }

        try {
            $response = $this->client->post('checkout_sessions', [
                'json' => $data
            ]);

            $body = json_decode($response->getBody(), true);
            
            if ($response->getStatusCode() !== 200) {
                error_log("Checkout Session Error - Status: " . $response->getStatusCode());
                error_log("Checkout Session Error - Body: " . print_r($body, true));
                throw new Exception($body['errors'][0]['detail'] ?? 'Checkout session creation failed');
            }

            // Debug: Check if checkout_url contains the placeholder
            if (isset($body['data']['attributes']['checkout_url'])) {
                error_log("Checkout URL: " . $body['data']['attributes']['checkout_url']);
                if (strpos($body['data']['attributes']['checkout_url'], '{CHECKOUT_SESSION_ID}') !== false) {
                    error_log("WARNING: Checkout URL still contains placeholder!");
                }
            }

            return $body;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $error = json_decode($response->getBody(), true);
            error_log("Checkout Session Exception - Status: " . $response->getStatusCode());
            error_log("Checkout Session Exception - Body: " . print_r($error, true));
            throw new Exception($error['errors'][0]['detail'] ?? 'Checkout API Request Failed');
        }
    }

    public function retrievePayment($paymentId) {
        try {
            $response = $this->client->get("payments/{$paymentId}");
            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            error_log("PayMongo API Error: HTTP " . $response->getStatusCode() . " - " . $response->getBody());
            return null;
        }
    }
    
    public function retrieveCheckoutSession($sessionId) {
        try {
            $response = $this->client->get("checkout_sessions/{$sessionId}");
            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            error_log("PayMongo API Error: HTTP " . $response->getStatusCode() . " - " . $response->getBody());
            return null;
        }
    }
}