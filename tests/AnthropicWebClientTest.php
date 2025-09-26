<?php

namespace JoshFinlayAU\LaravelAnthropicWeb\Tests;

use JoshFinlayAU\LaravelAnthropicWeb\AnthropicWebClient;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Http;

class AnthropicWebClientTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['JoshFinlayAU\LaravelAnthropicWeb\AnthropicWebServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('anthropic-web.api_key', 'test-key');
    }

    public function test_can_create_client()
    {
        $client = new AnthropicWebClient('test-key');
        $this->assertInstanceOf(AnthropicWebClient::class, $client);
    }

    public function test_web_search_tool_creation()
    {
        $tool = AnthropicWebClient::webSearchTool(['max_uses' => 5]);
        
        $this->assertEquals('web_search_20250305', $tool['type']);
        $this->assertEquals('web_search', $tool['name']);
        $this->assertEquals(5, $tool['max_uses']);
    }

    public function test_web_fetch_tool_creation()
    {
        $tool = AnthropicWebClient::webFetchTool(['max_uses' => 10]);
        
        $this->assertEquals('web_fetch_20250910', $tool['type']);
        $this->assertEquals('web_fetch', $tool['name']);
        $this->assertEquals(10, $tool['max_uses']);
    }

    public function test_user_location_creation()
    {
        $location = AnthropicWebClient::userLocation('Sydney', 'NSW', 'AU', 'Australia/Sydney');
        
        $this->assertEquals('approximate', $location['type']);
        $this->assertEquals('Sydney', $location['city']);
        $this->assertEquals('NSW', $location['region']);
        $this->assertEquals('AU', $location['country']);
        $this->assertEquals('Australia/Sydney', $location['timezone']);
    }

    public function test_message_request_building()
    {
        $client = new AnthropicWebClient('test-key');
        
        $request = $client->buildMessageRequest(
            'claude-sonnet-4-20250514',
            [['role' => 'user', 'content' => 'Hello']],
            1000,
            [AnthropicWebClient::webSearchTool()]
        );
        
        $this->assertEquals('claude-sonnet-4-20250514', $request['model']);
        $this->assertEquals(1000, $request['max_tokens']);
        $this->assertCount(1, $request['messages']);
        $this->assertCount(1, $request['tools']);
    }

    public function test_available_models()
    {
        $models = AnthropicWebClient::getAvailableModels();
        
        $this->assertIsArray($models);
        $this->assertContains('claude-sonnet-4-20250514', $models);
        $this->assertContains('claude-opus-4-1-20250805', $models);
    }

    public function test_api_call_with_mocked_response()
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'Hello! How can I help you?']],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 20]
            ])
        ]);

        $client = new AnthropicWebClient('test-key');
        $response = $client->complete('Hello');
        
        $this->assertEquals('Hello! How can I help you?', $response);
    }

    public function test_json_response_format()
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => '{"name": "John", "age": 30}']],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 20]
            ])
        ]);

        $client = new AnthropicWebClient('test-key');
        $response = $client->completeJson('Return user data as JSON');
        
        $this->assertEquals(['name' => 'John', 'age' => 30], $response);
    }

    public function test_message_request_with_format()
    {
        $client = new AnthropicWebClient('test-key');
        
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer']
            ]
        ];
        
        $request = $client->buildMessageRequestWithFormat(
            'claude-sonnet-4-20250514',
            [['role' => 'user', 'content' => 'Hello']],
            'json',
            $schema,
            1000
        );
        
        $this->assertEquals('claude-sonnet-4-20250514', $request['model']);
        $this->assertEquals('json', $request['response_format']['type']);
        $this->assertEquals($schema, $request['response_format']['schema']);
    }
}
