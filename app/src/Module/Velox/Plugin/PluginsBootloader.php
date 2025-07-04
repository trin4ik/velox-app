<?php

declare(strict_types=1);

namespace App\Module\Velox\Plugin;

use App\Module\Velox\Plugin\DTO\Plugin;
use App\Module\Velox\Plugin\DTO\PluginCategory;
use App\Module\Velox\Plugin\DTO\PluginRepository;
use App\Module\Velox\Plugin\DTO\PluginSource;
use App\Module\Velox\Plugin\Service\CompositePluginProvider;
use App\Module\Velox\Plugin\Service\ConfigPluginProvider;
use App\Module\Velox\Plugin\Service\PluginProviderInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;

final class PluginsBootloader extends Bootloader
{
    #[\Override]
    public function defineSingletons(): array
    {
        return [
            PluginProviderInterface::class => fn(
                EnvironmentInterface $env,
            ) => new CompositePluginProvider(
                providers: [
                    new ConfigPluginProvider([
                        ...$this->initCorePlugins($env),
                        ...$this->initCommonPlugins($env),
                    ]),
                ],
            ),
        ];
    }

    private function initCommonPlugins(EnvironmentInterface $env): array
    {
        return [
            new Plugin(
                name: '/circuit-breaker',
                ref: $env->get('RR_PLUGIN_CIRCUIT_BREAKER', 'master'),
                owner: 'roadrunner-server',
                repository: 'circuit-breaker',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Community,
                dependencies: ['http'],
                description: <<<DESCRIPTION
                    A middleware plugin that implements the Circuit Breaker pattern to prevent cascading failures in 
                    distributed systems by monitoring error rates and temporarily blocking requests when services 
                    become unhealthy.
                    DESCRIPTION,
                category: PluginCategory::Http,
                docsUrl: 'https://github.com/roadrunner-server/circuit-breaker',
            ),
            new Plugin(
                name: 'sendremotefile',
                ref: $env->get('RR_PLUGIN_SEND_REMOTE_FILE', 'master'),
                owner: 'roadrunner-server',
                repository: 'sendremotefile',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Community,
                dependencies: ['http'],
                description: <<<DESCRIPTION
                    The Sendremotefile HTTP middleware and the X-Sendremotefile HTTP response headers are used to 
                    stream large files using the RoadRunner. Unlike X-Sendfile middleware Sendremotefile allows passing 
                    a URL as header value. While the file is being streamed with the help of the RoadRunner, the PHP
                    worker may be accepting the next request.
                    DESCRIPTION,
                category: PluginCategory::Http,
                docsUrl: 'https://docs.roadrunner.dev/docs/community-plugins/sendremotefile',
            ),
            new Plugin(
                name: 'http-caching',
                ref: $env->get('RR_PLUGIN_HTTP_CACHING', 'master'),
                owner: 'darkweak',
                repository: 'souin',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Community,
                folder: '/plugins/roadrunner',
                dependencies: ['http'],
                description: 'Cache middleware implements http-caching RFC 7234. It\'s based on the Souin HTTP cache library.',
                category: PluginCategory::Http,
                docsUrl: 'https://docs.roadrunner.dev/docs/community-plugins/cache',
            ),
            new Plugin(
                name: 'sentry-collector',
                ref: $env->get('RR_PLUGIN_SENTRY_COLLECTOR', 'master'),
                owner: 'butschster',
                repository: 'rr-sentry-transport',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Community, // Added logger dependency
                dependencies: ['logger'],
                description: 'Sentry collector plugin allowing delegation of Sentry events delivery to the Sentry server through the RoadRunner server.',
                category: PluginCategory::Monitoring,
            ),
            new Plugin(
                name: 'redis-queue',
                ref: $env->get('RR_PLUGIN_REDIS_QUEUE', 'master'),
                owner: 'ASG-Digital',
                repository: 'rr-redis-queue',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Community,
                dependencies: ['logger'],
                description: 'This is a plugin for roadrunner adding support for a redis backed queue. This plugin is still under development and should not be considered production ready.',
                category: PluginCategory::Jobs,
            ),
        ];
    }

    private function initCorePlugins(EnvironmentInterface $env): array
    {
        return [
            new Plugin(
                name: 'logger',
                ref: $env->get('RR_PLUGIN_LOGGER', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'logger',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: [], // No dependencies (foundational)
                description: 'Core logging functionality',
                category: PluginCategory::Logging,
                docsUrl: 'https://docs.roadrunner.dev/docs/logging-and-observability/logger',
            ),

            // SERVER & CORE PLUGINS
            new Plugin(
                name: 'server',
                ref: $env->get('RR_PLUGIN_SERVER', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'server',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger'], // Added logger dependency
                description: 'Core server functionality',
                category: PluginCategory::Core,
                docsUrl: 'https://docs.roadrunner.dev/docs/plugins/server',
            ),

            new Plugin(
                name: 'rpc',
                ref: $env->get('RR_PLUGIN_RPC', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'rpc',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger'], // Added logger dependency
                description: 'RPC communication plugin',
                category: PluginCategory::Core,
                docsUrl: 'https://docs.roadrunner.dev/docs/php-worker/rpc',
            ),

            new Plugin(
                name: 'service',
                ref: $env->get('RR_PLUGIN_SERVICE', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'service',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger'], // Added logger dependency
                description: 'Lightweight systemd-like service manager',
                category: PluginCategory::Core,
                docsUrl: 'https://docs.roadrunner.dev/docs/plugins/service',
            ),

            new Plugin(
                name: 'lock',
                ref: $env->get('RR_PLUGIN_LOCK', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'lock',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'rpc'], // Added logger and rpc dependencies
                description: 'Distributed locking mechanism',
                category: PluginCategory::Core,
                docsUrl: 'https://docs.roadrunner.dev/docs/plugins/locks',
            ),

            // COMMUNICATION LAYER
            new Plugin(
                name: 'http',
                ref: $env->get('RR_PLUGIN_HTTP', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'http',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'server'], // Added logger dependency
                description: 'HTTP server plugin',
                category: PluginCategory::Http,
                docsUrl: 'https://docs.roadrunner.dev/docs/http/http',
            ),

            new Plugin(
                name: 'grpc',
                ref: $env->get('RR_PLUGIN_GRPC', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'grpc',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'server'], // Added logger dependency
                description: 'gRPC server plugin',
                category: PluginCategory::Grpc,
                docsUrl: 'https://docs.roadrunner.dev/docs/plugins/grpc',
            ),

            new Plugin(
                name: 'tcp',
                ref: $env->get('RR_PLUGIN_TCP', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'tcp',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'server'], // Added logger dependency
                description: 'Raw TCP payload handling',
                category: PluginCategory::Network,
                docsUrl: 'https://docs.roadrunner.dev/docs/plugins/tcp',
            ),

            // HTTP MIDDLEWARE - All need logger + http
            new Plugin(
                name: 'gzip',
                ref: $env->get('RR_PLUGIN_GZIP', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'gzip',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'http'], // Added logger dependency
                description: 'GZIP compression middleware',
                category: PluginCategory::Http,
                docsUrl: 'https://docs.roadrunner.dev/docs/http/gzip',
            ),

            new Plugin(
                name: 'headers',
                ref: $env->get('RR_PLUGIN_HEADERS', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'headers',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'http'], // Added logger dependency
                description: 'HTTP headers middleware',
                category: PluginCategory::Http,
                docsUrl: 'https://docs.roadrunner.dev/docs/http/headers',
            ),

            new Plugin(
                name: 'static',
                ref: $env->get('RR_PLUGIN_STATIC', 'v5.0.1'),
                owner: 'roadrunner-server',
                repository: 'static',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'http'], // Added logger dependency
                description: 'Static file serving middleware',
                category: PluginCategory::Http,
                docsUrl: 'https://docs.roadrunner.dev/docs/http/static',
            ),

            new Plugin(
                name: 'fileserver',
                ref: $env->get('RR_PLUGIN_FILESERVER', 'v5.0.1'),
                owner: 'roadrunner-server',
                repository: 'fileserver',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'http'], // Added logger dependency
                description: 'Static file server',
                category: PluginCategory::Http,
                docsUrl: 'https://docs.roadrunner.dev/docs/http/static',
            ),

            new Plugin(
                name: 'proxy',
                ref: $env->get('RR_PLUGIN_PROXY', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'proxy_ip_parser',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'http'], // Added logger dependency
                description: 'Proxy IP parser middleware',
                category: PluginCategory::Http,
                docsUrl: 'https://docs.roadrunner.dev/docs/http/proxy',
            ),

            new Plugin(
                name: 'send',
                ref: $env->get('RR_PLUGIN_SEND', 'v5.0.1'),
                owner: 'roadrunner-server',
                repository: 'send',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'http'], // Added logger dependency
                description: 'Send file response middleware',
                category: PluginCategory::Http,
                docsUrl: 'https://docs.roadrunner.dev/docs/http/sendfile',
            ),

            new Plugin(
                name: 'prometheus',
                ref: $env->get('RR_PLUGIN_PROMETHEUS', 'v5.0.1'),
                owner: 'roadrunner-server',
                repository: 'prometheus',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'metrics'], // Added logger dependency
                description: 'Prometheus metrics HTTP middleware',
                category: PluginCategory::Metrics,
                docsUrl: 'https://docs.roadrunner.dev/docs/logging-and-observability/metrics#http-metrics',
            ),

            // JOBS LAYER
            new Plugin(
                name: 'jobs',
                ref: $env->get('RR_PLUGIN_JOBS', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'jobs',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'server', 'rpc'], // Added logger dependency
                description: 'Job queue management',
                category: PluginCategory::Jobs,
                docsUrl: 'https://docs.roadrunner.dev/docs/queues-and-jobs/overview-queues',
            ),

            // JOB DRIVERS - All need logger + jobs
            new Plugin(
                name: 'amqp',
                ref: $env->get('RR_PLUGIN_AMQP', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'amqp',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'jobs'], // Added logger dependency
                description: 'AMQP job driver',
                category: PluginCategory::Jobs,
                docsUrl: 'https://docs.roadrunner.dev/docs/queues-and-jobs/amqp',
            ),

            new Plugin(
                name: 'sqs',
                ref: $env->get('RR_PLUGIN_SQS', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'sqs',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'jobs'], // Added logger dependency
                description: 'AWS SQS job driver',
                category: PluginCategory::Jobs,
                docsUrl: 'https://docs.roadrunner.dev/docs/queues-and-jobs/sqs',
            ),

            new Plugin(
                name: 'beanstalk',
                ref: $env->get('RR_PLUGIN_BEANSTALK', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'beanstalk',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'jobs'], // Added logger dependency
                description: 'Beanstalk job driver',
                category: PluginCategory::Jobs,
                docsUrl: 'https://docs.roadrunner.dev/docs/queues-and-jobs/beanstalk',
            ),

            new Plugin(
                name: 'nats',
                ref: $env->get('RR_PLUGIN_NATS', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'nats',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'jobs'], // Added logger dependency
                description: 'NATS job driver',
                category: PluginCategory::Jobs,
                docsUrl: 'https://docs.roadrunner.dev/docs/queues-and-jobs/nats',
            ),

            new Plugin(
                name: 'kafka',
                ref: $env->get('RR_PLUGIN_KAFKA', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'kafka',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'jobs'], // Added logger dependency
                description: 'Apache Kafka job driver',
                category: PluginCategory::Jobs,
                docsUrl: 'https://docs.roadrunner.dev/docs/queues-and-jobs/kafka',
            ),

            new Plugin(
                name: 'googlepubsub',
                ref: $env->get('RR_PLUGIN_GOOGLEPUBSUB', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'google-pub-sub',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'jobs'], // Added logger dependency
                description: 'Google Pub/Sub job driver',
                category: PluginCategory::Jobs,
                docsUrl: 'https://docs.roadrunner.dev/docs/queues-and-jobs/google-pub-sub',
            ),

            // KEY-VALUE LAYER
            new Plugin(
                name: 'kv',
                ref: $env->get('RR_PLUGIN_KV', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'kv',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'rpc'], // Changed from ['server'] to ['logger']
                description: 'Key-value storage interface',
                category: PluginCategory::Kv,
                docsUrl: 'https://docs.roadrunner.dev/docs/key-value/overview-kv',
            ),

            // KV DRIVERS - All need logger + kv
            new Plugin(
                name: 'redis',
                ref: $env->get('RR_PLUGIN_REDIS', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'redis',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'kv'], // Added logger dependency
                description: 'Redis key-value storage',
                category: PluginCategory::Kv,
                docsUrl: 'https://docs.roadrunner.dev/docs/key-value/redis',
            ),

            new Plugin(
                name: 'memory',
                ref: $env->get('RR_PLUGIN_MEMORY', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'memory',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'kv'], // Added logger dependency
                description: 'In-memory key-value storage',
                category: PluginCategory::Kv,
                docsUrl: 'https://docs.roadrunner.dev/docs/key-value/memory',
            ),

            new Plugin(
                name: 'boltdb',
                ref: $env->get('RR_PLUGIN_BOLTDB', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'boltdb',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'kv'], // Added logger dependency
                description: 'BoltDB key-value storage',
                category: PluginCategory::Kv,
                docsUrl: 'https://docs.roadrunner.dev/docs/key-value/boltdb',
            ),

            new Plugin(
                name: 'memcached',
                ref: $env->get('RR_PLUGIN_MEMCACHED', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'memcached',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'kv'], // Added logger dependency
                description: 'Memcached key-value storage',
                category: PluginCategory::Kv,
                docsUrl: 'https://docs.roadrunner.dev/docs/key-value/memcached',
            ),

            // METRICS LAYER
            new Plugin(
                name: 'metrics',
                ref: $env->get('RR_PLUGIN_METRICS', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'metrics',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'rpc'], // Added logger dependency
                description: 'Metrics collection and reporting',
                category: PluginCategory::Metrics,
                docsUrl: 'https://docs.roadrunner.dev/docs/logging-and-observability/metrics',
            ),

            // MONITORING & OBSERVABILITY
            new Plugin(
                name: 'status',
                ref: $env->get('RR_PLUGIN_STATUS', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'status',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger'], // Added logger dependency
                description: 'Health checks and readiness probes',
                category: PluginCategory::Monitoring,
                docsUrl: 'https://docs.roadrunner.dev/docs/logging-and-observability/health',
            ),

            new Plugin(
                name: 'otel',
                ref: $env->get('RR_PLUGIN_OTEL', 'v5.0.1'),
                owner: 'roadrunner-server',
                repository: 'otel',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger'], // Added logger dependency
                description: 'OpenTelemetry tracing',
                category: PluginCategory::Observability,
                docsUrl: 'https://docs.roadrunner.dev/docs/logging-and-observability/otel',
            ),

            new Plugin(
                name: 'appLogger',
                ref: $env->get('RR_PLUGIN_APP_LOGGER', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'app-logger',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'rpc'], // Keep existing
                description: 'Application logger plugin for RoadRunner',
                category: PluginCategory::Logging,
                docsUrl: 'https://docs.roadrunner.dev/docs/logging-and-observability/applogger',
            ),

            // BROADCASTING & WORKFLOW
            new Plugin(
                name: 'centrifuge',
                ref: $env->get('RR_PLUGIN_CENTRIFUGE', 'v5.0.2'),
                owner: 'roadrunner-server',
                repository: 'centrifuge',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'server'], // Added logger dependency
                description: 'Centrifuge broadcasting platform',
                category: PluginCategory::Broadcasting,
                docsUrl: 'https://docs.roadrunner.dev/docs/plugins/centrifuge',
            ),

            new Plugin(
                name: 'temporal',
                ref: $env->get('RR_PLUGIN_TEMPORAL', 'v5.1.0'),
                owner: 'temporalio',
                repository: 'roadrunner-temporal',
                repositoryType: PluginRepository::Github,
                source: PluginSource::Official,
                dependencies: ['logger', 'server'], // Added logger dependency
                description: 'Temporal workflow engine',
                category: PluginCategory::Workflow,
                docsUrl: 'https://docs.roadrunner.dev/docs/workflow-engine/temporal',
            ),
        ];
    }
}
