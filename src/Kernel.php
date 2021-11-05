<?php

namespace SaaSFormation\Vendor\API;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SaaSFormation\Vendor\CommandBus\CommandBusManager;
use SaaSFormation\Vendor\Container\Container;

abstract class Kernel
{
    public const CONFIG_PATH = "/config";

    private Container $container;
    private Router $router;

    protected function __construct()
    {
    }

    public static function create(): static
    {
        return new static();
    }

    public function boot(string $basePath): static
    {
        $this->container = Container::createEmpty();
        $commandBus = CommandBusManager::createEmpty($this->container);
        $commandBus->addPairsFromConfig($basePath . self::CONFIG_PATH . '/command_bus');
        $this->container->addService(CommandBusManager::class, $commandBus);
        $this->container->addServicesFromConfig($basePath . self::CONFIG_PATH . '/di');
        $this->router = Router::createFromConfig($this->container, $basePath . self::CONFIG_PATH . '/routing');

        return $this;
    }

    public function run(ServerRequestInterface $serverRequest): ResponseInterface
    {
        return $this->router->route($serverRequest);
    }
}
