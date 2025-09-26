<?php

require_once 'vendor/autoload.php';

use JoshFinlayAU\LaravelAnthropicWeb\AnthropicWebClient;

echo "Simple Anthropic Web Client Test\n";
echo "=================================\n\n";

// Check for API key
$apiKey = getenv('ANTHROPIC_API_KEY') ?: null;
if (! $apiKey) {
    echo "Error: No ANTHROPIC_API_KEY environment variable found.\n";
    echo "Set it with: export ANTHROPIC_API_KEY=your-key-here\n";
    exit(1);
}

echo 'API Key found: '.substr($apiKey, 0, 10)."...\n";

try {
    $client = new AnthropicWebClient($apiKey);
    echo "Client initialized successfully\n\n";

    // Test basic message creation
    echo "Testing basic message creation...\n";

    $request = $client->buildMessageRequest(
        'claude-3-5-haiku-latest',
        [['role' => 'user', 'content' => 'What is 2+2? Answer with just the number.']],
        50
    );

    echo "Message request built:\n";
    echo json_encode($request, JSON_PRETTY_PRINT)."\n\n";

    // Test tool creation
    echo "Testing tool creation...\n";

    $searchTool = AnthropicWebClient::webSearchTool(['max_uses' => 3]);
    echo "Web search tool:\n";
    echo json_encode($searchTool, JSON_PRETTY_PRINT)."\n\n";

    $fetchTool = AnthropicWebClient::webFetchTool(['max_uses' => 5]);
    echo "Web fetch tool:\n";
    echo json_encode($fetchTool, JSON_PRETTY_PRINT)."\n\n";

    $location = AnthropicWebClient::userLocation('Brisbane', 'Queensland', 'AU', 'Australia/Brisbane');
    echo "User location:\n";
    echo json_encode($location, JSON_PRETTY_PRINT)."\n\n";

    // Test JSON request building
    echo "Testing JSON format request...\n";

    $schema = [
        'type' => 'object',
        'properties' => [
            'answer' => ['type' => 'integer'],
            'explanation' => ['type' => 'string'],
        ],
    ];

    $jsonRequest = $client->buildMessageRequestWithFormat(
        'claude-3-5-haiku-latest',
        [['role' => 'user', 'content' => 'What is 5+7? Return as JSON.']],
        'json',
        $schema,
        200
    );

    echo "JSON format request:\n";
    echo json_encode($jsonRequest, JSON_PRETTY_PRINT)."\n\n";

    echo "All tests passed! Package structure is working correctly.\n";
    echo "Note: Actual API calls require Laravel's Http facade or manual HTTP implementation.\n";

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    exit(1);
}
