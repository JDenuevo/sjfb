<?php
header('Content-Type: application/json');

try {
    // Get the input data
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = $input['message'] ?? '';
    
    if (empty($userMessage)) {
        throw new Exception('No message provided');
    }

    // Prepare the prompt with context about your business
    $systemMessage = "You are a helpful assistant for St. Joseph Fish Brokerage Inc., a seafood company. 
                     Provide friendly, professional responses about seafood products, services, and the company.
                     If asked about something unrelated, politely guide the conversation back to seafood topics.";
    
    $data = [
        'model' => 'gpt-3.5-turbo', // Using 3.5 as it's more cost-effective
        'messages' => [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $userMessage]
        ],
        'temperature' => 0.7,
        'max_tokens' => 500
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30-second timeout
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('API request failed: ' . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
        throw new Exception('API returned HTTP code ' . $httpCode);
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from API');
    }
    
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Unexpected API response format');
    }
    
    // Return the successful response
    echo json_encode([
        'reply' => $result['choices'][0]['message']['content']
    ]);
    
} catch (Exception $e) {
    // Return error information (for debugging)
    error_log('Chatbot error: ' . $e->getMessage());
    
    // Fallback responses
    $fallbackResponses = [
        "I'm having trouble understanding. Could you ask about our seafood products or services?",
        "Our team is currently unavailable. Please try again later or contact us directly.",
        "I specialize in seafood information. How can I help you with fish products today?"
    ];
    
    echo json_encode([
        'error' => $e->getMessage(),
        'reply' => $fallbackResponses[array_rand($fallbackResponses)]
    ]);
}