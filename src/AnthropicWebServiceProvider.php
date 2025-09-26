<?php

namespace JoshFinlayAU\LaravelAnthropicWeb;

use Illuminate\Support\ServiceProvider;

class AnthropicWebServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/anthropic-web.php', 'anthropic-web');

        $this->app->singleton(AnthropicWebClient::class, function ($app) {
            return new AnthropicWebClient(
                config('anthropic-web.api_key'),
                config('anthropic-web.base_url', 'https://api.anthropic.com/v1'),
                config('anthropic-web.timeout', 60)
            );
        });

        $this->app->alias(AnthropicWebClient::class, 'anthropic-web');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/anthropic-web.php' => config_path('anthropic-web.php'),
            ], 'anthropic-web-config');
        }
    }
}
