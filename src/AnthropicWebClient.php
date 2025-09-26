<?php

namespace JoshFinlayAU\LaravelAnthropicWeb;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Exception;

class AnthropicWebClient
{
    protected string $apiKey;
    protected string $baseUrl;
    protected array $defaultHeaders;
    protected int $timeout;

    public function __construct(?string $apiKey = null, string $baseUrl = 'https://api.anthropic.com/v1', int $timeout = 60)
    {
        $this->apiKey = $apiKey ?? config('anthropic-web.api_key');
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
        
        if (!$this->apiKey) {
            throw new Exception('Anthropic API key is required');
        }

        $this->defaultHeaders = [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ];
    }

    public function createMessage(array $params): array
    {
        $headers = $this->defaultHeaders;
        
        if ($this->hasWebTools($params)) {
            $headers['anthropic-beta'] = 'web-fetch-2025-09-10';
        }

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/messages", $params);

        return $this->handleResponse($response);
    }

    public function createStreamedMessage(array $params): \Generator
    {
        $params['stream'] = true;
        $headers = $this->defaultHeaders;
        
        if ($this->hasWebTools($params)) {
            $headers['anthropic-beta'] = 'web-fetch-2025-09-10';
        }

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/messages", $params);

        if (!$response->successful()) {
            throw new Exception("Anthropic API error: {$response->status()} - {$response->body()}");
        }

        $lines = explode("\n", $response->body());
        foreach ($lines as $line) {
            if (str_starts_with($line, 'data: ')) {
                $data = substr($line, 6);
                if ($data !== '[DONE]') {
                    yield json_decode($data, true);
                }
            }
        }
    }

    public static function webSearchTool(array $options = []): array
    {
        return array_merge([
            'type' => 'web_search_20250305',
            'name' => 'web_search',
        ], $options);
    }

    public static function webFetchTool(array $options = []): array
    {
        return array_merge([
            'type' => 'web_fetch_20250910',
            'name' => 'web_fetch',
        ], $options);
    }

    public static function userLocation(string $city, string $region, string $country, string $timezone): array
    {
        return [
            'type' => 'approximate',
            'city' => $city,
            'region' => $region,
            'country' => $country,
            'timezone' => $timezone,
        ];
    }

    public function buildMessageRequest(string $model, array $messages, int $maxTokens = 4000, array $tools = [], array $options = []): array
    {
        return array_merge([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ], $tools ? ['tools' => $tools] : [], $options);
    }

    public function buildMessageRequestWithFormat(string $model, array $messages, string $format = 'text', ?array $schema = null, int $maxTokens = 4000, array $tools = [], array $options = []): array
    {
        $request = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ];

        // Add response format
        if ($format === 'json' || $schema) {
            $request['response_format'] = ['type' => 'json'];
            
            if ($schema) {
                $request['response_format']['schema'] = $schema;
            }
        }

        return array_merge($request, $tools ? ['tools' => $tools] : [], $options);
    }

    public function complete(string $prompt, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $tools = []): string
    {
        $request = $this->buildMessageRequest($model, [['role' => 'user', 'content' => $prompt]], $maxTokens, $tools);
        $response = $this->createMessage($request);
        
        return $response['content'][0]['text'] ?? '';
    }

    public function completeWithWebSearch(string $prompt, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $searchOptions = []): array
    {
        $tools = [self::webSearchTool(array_merge(['max_uses' => 5], $searchOptions))];
        $request = $this->buildMessageRequest($model, [['role' => 'user', 'content' => $prompt]], $maxTokens, $tools);

        return $this->createMessage($request);
    }

    public function completeWithWebFetch(string $prompt, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $fetchOptions = []): array
    {
        $tools = [self::webFetchTool(array_merge(['max_uses' => 5, 'citations' => ['enabled' => true]], $fetchOptions))];
        $request = $this->buildMessageRequest($model, [['role' => 'user', 'content' => $prompt]], $maxTokens, $tools);

        return $this->createMessage($request);
    }

    public function completeWithWebTools(string $prompt, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $searchOptions = [], array $fetchOptions = []): array
    {
        $tools = [
            self::webSearchTool(array_merge(['max_uses' => 3], $searchOptions)),
            self::webFetchTool(array_merge(['max_uses' => 5, 'citations' => ['enabled' => true]], $fetchOptions))
        ];

        $request = $this->buildMessageRequest($model, [['role' => 'user', 'content' => $prompt]], $maxTokens, $tools);

        return $this->createMessage($request);
    }

    public function completeJson(string $prompt, ?array $schema = null, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $tools = []): array
    {
        $request = $this->buildMessageRequestWithFormat($model, [['role' => 'user', 'content' => $prompt]], 'json', $schema, $maxTokens, $tools);
        $response = $this->createMessage($request);
        
        $content = $response['content'][0]['text'] ?? '';
        return json_decode($content, true) ?? [];
    }

    public function completeJsonWithWebSearch(string $prompt, ?array $schema = null, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $searchOptions = []): array
    {
        $tools = [self::webSearchTool(array_merge(['max_uses' => 5], $searchOptions))];
        $request = $this->buildMessageRequestWithFormat($model, [['role' => 'user', 'content' => $prompt]], 'json', $schema, $maxTokens, $tools);
        
        $response = $this->createMessage($request);
        $content = $response['content'][0]['text'] ?? '';
        return json_decode($content, true) ?? [];
    }

    public function completeJsonWithWebTools(string $prompt, ?array $schema = null, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $searchOptions = [], array $fetchOptions = []): array
    {
        $tools = [
            self::webSearchTool(array_merge(['max_uses' => 3], $searchOptions)),
            self::webFetchTool(array_merge(['max_uses' => 5, 'citations' => ['enabled' => true]], $fetchOptions))
        ];

        $request = $this->buildMessageRequestWithFormat($model, [['role' => 'user', 'content' => $prompt]], 'json', $schema, $maxTokens, $tools);
        
        $response = $this->createMessage($request);
        $content = $response['content'][0]['text'] ?? '';
        return json_decode($content, true) ?? [];
    }

    protected function hasWebTools(array $params): bool
    {
        if (!isset($params['tools'])) {
            return false;
        }

        foreach ($params['tools'] as $tool) {
            if (in_array($tool['type'] ?? '', ['web_search_20250305', 'web_fetch_20250910'])) {
                return true;
            }
        }

        return false;
    }

    protected function handleResponse(Response $response): array
    {
        if (!$response->successful()) {
            $error = $response->json();
            $message = $error['error']['message'] ?? 'Unknown API error';
            $type = $error['error']['type'] ?? 'api_error';
            
            Log::error("Anthropic API Error", [
                'status' => $response->status(),
                'type' => $type,
                'message' => $message,
                'response' => $response->body()
            ]);

            throw new Exception("Anthropic API error ({$type}): {$message}");
        }

        $data = $response->json();
        
        if (!$data) {
            throw new Exception('Invalid JSON response from Anthropic API');
        }

        return $data;
    }

    public static function getAvailableModels(): array
    {
        return [
            'claude-opus-4-1-20250805',
            'claude-opus-4-20250514', 
            'claude-sonnet-4-20250514',
            'claude-3-7-sonnet-20250219',
            'claude-3-5-sonnet-latest',
            'claude-3-5-haiku-latest',
        ];
    }
}
