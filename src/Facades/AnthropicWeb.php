<?php

namespace JoshFinlayAU\LaravelAnthropicWeb\Facades;

use Illuminate\Support\Facades\Facade;
use JoshFinlayAU\LaravelAnthropicWeb\AnthropicWebClient;

/**
 * @method static array createMessage(array $params)
 * @method static \Generator createStreamedMessage(array $params)
 * @method static array webSearchTool(array $options = [])
 * @method static array webFetchTool(array $options = [])
 * @method static array userLocation(string $city, string $region, string $country, string $timezone)
 * @method static array buildMessageRequest(string $model, array $messages, int $maxTokens = 4000, array $tools = [], array $options = [])
 * @method static array buildMessageRequestWithFormat(string $model, array $messages, string $format = 'text', ?array $schema = null, int $maxTokens = 4000, array $tools = [], array $options = [])
 * @method static string complete(string $prompt, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $tools = [])
 * @method static array completeWithWebSearch(string $prompt, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $searchOptions = [])
 * @method static array completeWithWebFetch(string $prompt, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $fetchOptions = [])
 * @method static array completeWithWebTools(string $prompt, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $searchOptions = [], array $fetchOptions = [])
 * @method static array completeJson(string $prompt, ?array $schema = null, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $tools = [])
 * @method static array completeJsonWithWebSearch(string $prompt, ?array $schema = null, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $searchOptions = [])
 * @method static array completeJsonWithWebTools(string $prompt, ?array $schema = null, string $model = 'claude-sonnet-4-20250514', int $maxTokens = 4000, array $searchOptions = [], array $fetchOptions = [])
 * @method static array getAvailableModels()
 *
 * @see \JoshFinlayAU\LaravelAnthropicWeb\AnthropicWebClient
 */
class AnthropicWeb extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AnthropicWebClient::class;
    }
}
