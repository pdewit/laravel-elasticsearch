<?php

namespace MailerLite\LaravelElasticsearch;

use MailerLite\LaravelElasticsearch\Console\Command\AliasCreateCommand;
use MailerLite\LaravelElasticsearch\Console\Command\AliasRemoveIndexCommand;
use MailerLite\LaravelElasticsearch\Console\Command\AliasSwitchIndexCommand;
use MailerLite\LaravelElasticsearch\Console\Command\IndexCreateCommand;
use MailerLite\LaravelElasticsearch\Console\Command\IndexCreateOrUpdateMappingCommand;
use MailerLite\LaravelElasticsearch\Console\Command\IndexDeleteCommand;
use MailerLite\LaravelElasticsearch\Console\Command\IndexExistsCommand;
use Elastic\Elasticsearch\Client;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Class ServiceProvider
 *
 * @package MailerLite\LaravelElasticsearch
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->setUpConfig();
        $this->setUpConsoleCommands();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;

        $app->singleton('elasticsearch.factory', function($app) {
            return new Factory();
        });

        $app->singleton('elasticsearch', function($app) {
            return new Manager($app, $app['elasticsearch.factory']);
        });

        $app->alias('elasticsearch', Manager::class);

        $app->singleton(Client::class, function(Container $app) {
            return $app->make('elasticsearch')->connection();
        });
    }

    protected function setUpConfig(): void
    {
        $source = dirname(__DIR__) . '/config/elasticsearch.php';

        if ($this->app instanceof LaravelApplication) {
            $this->publishes([$source => config_path('elasticsearch.php')], 'config');
        }

        $this->mergeConfigFrom($source, 'elasticsearch');
    }

    private function setUpConsoleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AliasCreateCommand::class,
                AliasRemoveIndexCommand::class,
                AliasSwitchIndexCommand::class,
                IndexCreateCommand::class,
                IndexCreateOrUpdateMappingCommand::class,
                IndexDeleteCommand::class,
                IndexExistsCommand::class,
            ]);
        }
    }
}
