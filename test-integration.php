<?php

require_once 'vendor/autoload.php';

use JoshFinlayAU\LaravelAnthropicWeb\AnthropicWebClient;

echo "Laravel Anthropic Web - Integration Testing\n";
echo "==========================================\n\n";

// Check for API key
$apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? null;
if (!$apiKey) {
    echo "Error: No ANTHROPIC_API_KEY environment variable found.\n";
    echo "Set it with: export ANTHROPIC_API_KEY=your-key-here\n";
    exit(1);
}

$client = new AnthropicWebClient($apiKey);

echo "Client initialized successfully\n\n";

// Test 1: Simple completion
echo "Test 1: Simple text completion\n";
echo "-------------------------------\n";
try {
    $response = $client->complete("What is 2+2? Answer briefly.", 'claude-3-5-haiku-latest', 100);
    echo "Response: " . trim($response) . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 2: JSON response
echo "Test 2: JSON response format\n";
echo "-----------------------------\n";
try {
    $schema = [
        'type' => 'object',
        'properties' => [
            'answer' => ['type' => 'integer'],
            'explanation' => ['type' => 'string']
        ]
    ];
    
    $response = $client->completeJson(
        "What is 5+7? Return as JSON with 'answer' and 'explanation' fields.",
        $schema,
        'claude-3-5-haiku-latest',
        200
    );
    
    echo "JSON Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Web search
echo "Test 3: Web search capabilities\n";
echo "--------------------------------\n";
try {
    $response = $client->completeWithWebSearch(
        "What's the current weather in Brisbane, Australia? Be brief.",
        'claude-3-5-haiku-latest',
        500,
        ['max_uses' => 2]
    );
    
    $content = $response['content'][0]['text'] ?? 'No content';
    echo "Web search response: " . trim($content) . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 4: JSON with web search
echo "Test 4: JSON response with web search\n";
echo "--------------------------------------\n";
try {
    $schema = [
        'type' => 'object',
        'properties' => [
            'city' => ['type' => 'string'],
            'temperature' => ['type' => 'string'],
            'conditions' => ['type' => 'string']
        ]
    ];
    
    $response = $client->completeJsonWithWebSearch(
        "Find current weather in Sydney, Australia and return as JSON",
        $schema,
        'claude-3-5-haiku-latest',
        800,
        ['max_uses' => 2]
    );
    
    echo "JSON + Web Search Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 5: Tool configuration
echo "Test 5: Tool configuration\n";
echo "---------------------------\n";
try {
    $searchTool = AnthropicWebClient::webSearchTool([
        'max_uses' => 1,
        'user_location' => AnthropicWebClient::userLocation(
            'Brisbane', 'Queensland', 'AU', 'Australia/Brisbane'
        )
    ]);
    
    $request = $client->buildMessageRequest(
        'claude-3-5-haiku-latest',
        [['role' => 'user', 'content' => 'What time is it in Brisbane right now?']],
        300,
        [$searchTool]
    );
    
    $response = $client->createMessage($request);
    $content = $response['content'][0]['text'] ?? 'No content';
    echo "Custom tool response: " . trim($content) . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 6: Available models
echo "Test 6: Available models\n";
echo "------------------------\n";
$models = AnthropicWebClient::getAvailableModels();
echo "Available models:\n";
foreach ($models as $model) {
    echo "  - $model\n";
}
echo "\n";

echo "Integration testing completed.\n";
