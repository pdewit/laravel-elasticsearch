<?php

namespace MailerLite\LaravelElasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Transport\NodePool\SimpleNodePool;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class Factory
{
    /**
     * Map configuration array keys with ES ClientBuilder setters
     *
     * @var array
     */
    protected $configMappings = [
        'sslVerification'    => 'setSSLVerification',
        'retries'            => 'setRetries',
        'httpHandler'        => 'setHandler',
        'serializer'         => 'setSerializer',
        'endpoint'           => 'setEndpoint',
        'namespaces'         => 'registerNamespace',
    ];

    /**
     * Make the Elasticsearch client for the given named configuration, or
     * the default client.
     *
     * @param array $config
     *
     * @return Client
     */
    public function make(array $config): Client
    {
        return $this->buildClient($config);
    }

    /**
     * Build and configure an Elasticsearch client.
     *
     * @param array $config
     *
     * @return Client
     */
    protected function buildClient(array $config): Client
    {
        $guzzle = new \GuzzleHttp\Client(['connect_timeout' => 1]);

        $clientBuilder = ClientBuilder::create()
            ->setHttpClient($guzzle);

        // Configure hosts
        $clientBuilder
            ->setHosts($config['hosts'])
            ->setBasicAuthentication($config['username'], $config['password']);

        // Configure logging
        if (Arr::get($config, 'logging')) {
            $logObject = Arr::get($config, 'logObject');
            $logPath = Arr::get($config, 'logPath');
            $logLevel = Arr::get($config, 'logLevel');
            if ($logObject && $logObject instanceof LoggerInterface) {
                $clientBuilder->setLogger($logObject);
            } elseif ($logPath && $logLevel) {
                $handler = new StreamHandler($logPath, $logLevel);
                $logObject = new Logger('log');
                $logObject->pushHandler($handler);
                $clientBuilder->setLogger($logObject);
            }
        }

        // Set additional client configuration
        foreach ($this->configMappings as $key => $method) {
            $value = Arr::get($config, $key);
            if (is_array($value)) {
                foreach ($value as $vItem) {
                    $clientBuilder->$method($vItem);
                }
            } elseif ($value !== null) {
                $clientBuilder->$method($value);
            }
        }

        // Build and return the client
        if (
            !empty($host['api_id']) && $host['api_id'] !== null &&
            !empty($host['api_key']) && $host['api_key'] !== null
        ) {
            $clientBuilder->setApiKey($host['api_id'], $host['api_key']);
        }

        return $clientBuilder->build();
    }
}
