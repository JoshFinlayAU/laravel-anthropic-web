<?php

namespace JoshFinlayAU\LaravelAnthropicWeb\Tests;

use JoshFinlayAU\LaravelAnthropicWeb\AnthropicWebClient;
use Orchestra\Testbench\TestCase;

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

    public function test_has_web_tools_detection()
    {
        $client = new AnthropicWebClient('test-key');
        
        // Use reflection to test the private method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('hasWebTools');
        $method->setAccessible(true);
        
        // Test with web search tool
        $paramsWithWebSearch = [
            'tools' => [
                ['type' => 'web_search_20250305', 'name' => 'web_search'],
            ],
        ];
        $this->assertTrue($method->invokeArgs($client, [$paramsWithWebSearch]));
        
        // Test with web fetch tool
        $paramsWithWebFetch = [
            'tools' => [
                ['type' => 'web_fetch_20250910', 'name' => 'web_fetch'],
            ],
        ];
        $this->assertTrue($method->invokeArgs($client, [$paramsWithWebFetch]));
        
        // Test without web tools
        $paramsWithoutWebTools = [
            'tools' => [
                ['type' => 'other_tool', 'name' => 'other'],
            ],
        ];
        $this->assertFalse($method->invokeArgs($client, [$paramsWithoutWebTools]));
        
        // Test with no tools
        $paramsNoTools = [];
        $this->assertFalse($method->invokeArgs($client, [$paramsNoTools]));
    }

    public function test_message_request_with_format()
    {
        $client = new AnthropicWebClient('test-key');
        
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
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
