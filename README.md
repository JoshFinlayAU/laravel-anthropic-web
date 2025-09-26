# Laravel Anthropic Web

[![Tests](https://github.com/JoshFinlayAU/laravel-anthropic-web/workflows/Tests/badge.svg)](https://github.com/JoshFinlayAU/laravel-anthropic-web/actions)
[![Latest Stable Version](https://poser.pugx.org/joshfinlayau/laravel-anthropic-web/v/stable)](https://packagist.org/packages/joshfinlayau/laravel-anthropic-web)
[![License](https://poser.pugx.org/joshfinlayau/laravel-anthropic-web/license)](https://packagist.org/packages/joshfinlayau/laravel-anthropic-web)

A Laravel package for Anthropic's Claude API with web search and fetch capabilities.

## Features

This package supports Claude's web tools that other packages don't:
- Web search for current information
- Web fetch to analyze content from URLs  
- Latest Claude models (Sonnet 4, Opus 4.1)
- JSON response formatting with schema validation
- Proper beta headers for new API features

Useful for applications requiring AI with real-time web data access.

## Installation

```bash
composer require joshfinlayau/laravel-anthropic-web
```

Publish the config file:

```bash
php artisan vendor:publish --tag=anthropic-web-config
```

Add your API key to `.env`:

```env
ANTHROPIC_API_KEY=your-api-key-here
```

## Basic Usage

### Simple Text Completion

```php
use JoshFinlayAU\LaravelAnthropicWeb\Facades\AnthropicWeb;

$response = AnthropicWeb::complete('What are the latest developments in AI?');
echo $response;
```

### Web Search

```php
$result = AnthropicWeb::completeWithWebSearch(
    'What are the current interest rates in Australia?',
    searchOptions: ['max_uses' => 3]
);
```

### Web Fetch

```php
$analysis = AnthropicWeb::completeWithWebFetch(
    'Analyze the content at https://example.com/article',
    fetchOptions: [
        'citations' => ['enabled' => true],
        'allowed_domains' => ['example.com']
    ]
);
```

### Combined Web Tools

```php
$research = AnthropicWeb::completeWithWebTools(
    'Research the latest trends in renewable energy and analyze the top 3 articles you find',
    searchOptions: ['max_uses' => 5],
    fetchOptions: ['citations' => ['enabled' => true]]
);
```

### JSON Response Format

```php
// Simple JSON response
$data = AnthropicWeb::completeJson('Return information about Paris as JSON');

// JSON with schema validation
$schema = [
    'type' => 'object',
    'properties' => [
        'name' => ['type' => 'string'],
        'population' => ['type' => 'integer'],
        'country' => ['type' => 'string']
    ]
];

$cityData = AnthropicWeb::completeJson(
    'Return information about Tokyo',
    $schema
);

// JSON response with web search
$currentData = AnthropicWeb::completeJsonWithWebSearch(
    'Find current stock price for Apple and return as JSON',
    ['type' => 'object', 'properties' => ['symbol' => ['type' => 'string'], 'price' => ['type' => 'number']]]
);
```

## Advanced Usage

### Custom Tool Configuration

```php
$searchTool = AnthropicWeb::webSearchTool([
    'max_uses' => 5,
    'allowed_domains' => ['reuters.com', 'bbc.com'],
    'user_location' => AnthropicWeb::userLocation('Sydney', 'NSW', 'AU', 'Australia/Sydney')
]);

$request = AnthropicWeb::buildMessageRequest(
    'claude-sonnet-4-20250514',
    [['role' => 'user', 'content' => 'Latest news about climate change']],
    4000,
    [$searchTool]
);

$response = AnthropicWeb::createMessage($request);
```

### Streaming Responses

```php
$stream = AnthropicWeb::createStreamedMessage([
    'model' => 'claude-sonnet-4-20250514',
    'max_tokens' => 4000,
    'messages' => [['role' => 'user', 'content' => 'Tell me about quantum computing']],
    'tools' => [AnthropicWeb::webSearchTool()]
]);

foreach ($stream as $chunk) {
    echo $chunk['content'][0]['text'] ?? '';
}
```

## Configuration

The config file allows you to set defaults:

```php
return [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'models' => [
        'default' => 'claude-sonnet-4-20250514',
        'fast' => 'claude-3-5-haiku-latest',
        'powerful' => 'claude-opus-4-1-20250805',
    ],
    'web_tools' => [
        'search' => ['max_uses' => 5],
        'fetch' => ['max_uses' => 10, 'citations_enabled' => true],
    ],
];
```

## Available Models

- `claude-opus-4-1-20250805` (Most capable)
- `claude-opus-4-20250514`
- `claude-sonnet-4-20250514` (Recommended default)
- `claude-3-7-sonnet-20250219`
- `claude-3-5-haiku-latest` (Fastest)

## Web Tools

### Web Search Tool

Searches the internet for current information:

```php
AnthropicWeb::webSearchTool([
    'max_uses' => 5,
    'allowed_domains' => ['example.com'],
    'blocked_domains' => ['spam.com'],
    'user_location' => [
        'city' => 'Brisbane',
        'region' => 'Queensland',
        'country' => 'AU',
        'timezone' => 'Australia/Brisbane'
    ]
])
```

### Web Fetch Tool

Fetches and analyzes specific web pages:

```php
AnthropicWeb::webFetchTool([
    'max_uses' => 10,
    'allowed_domains' => ['trusted-site.com'],
    'citations' => ['enabled' => true],
    'max_content_tokens' => 100000
])
```

## Error Handling

```php
try {
    $response = AnthropicWeb::complete('Your prompt here');
} catch (\Exception $e) {
    Log::error('Anthropic API failed: ' . $e->getMessage());
}
```

## Requirements

- PHP 8.1+
- Laravel 9.0+

## License

MIT

## Contributing

Pull requests welcome. Please include tests for new features.
