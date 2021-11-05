<?php

namespace SaaSFormation\Vendor\API;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SaaSFormation\Vendor\CommandBus\CommandBusManager;
use SaaSFormation\Vendor\HTTP\Controller;
use SaaSFormation\Vendor\HTTP\JSONResponder;
use SaaSFormation\Vendor\HTTP\XMLResponder;

class Router
{
    private ContainerInterface $container;
    private array $routeCollection;

    private function __construct(ContainerInterface $container, array $routeCollection)
    {
        $this->container = $container;
        $this->routeCollection = $routeCollection;
    }

    public static function createFromConfig(ContainerInterface $container, string $path): static
    {
        $routeCollection = [];

        if (is_dir($path)) {
            $directory = new \RecursiveDirectoryIterator($path);
            $iterator = new \RecursiveIteratorIterator($directory);
            $files = array();
            foreach ($iterator as $info) {
                if(!in_array($info->getFilename(), ['.', '..'])) {
                    $files[] = $info->getPathname();
                }
            }
        } else {
            $files = [$path];
        }

        foreach($files as $file) {
            $json = json_decode(file_get_contents($file), true);
            foreach($json["routes"] as $route) {
                $routeCollection[$route["method"]][$route["path"]] = $route["controller"];
            }
        }

        return new static($container, $routeCollection);
    }

    public function route(ServerRequestInterface $serverRequest): ResponseInterface
    {
        $controller = $this->routeCollection[$serverRequest->getMethod()][$serverRequest->getUri()->getPath()];

        $requestContentType = $serverRequest->getHeader('Content-Type')[0];

        $responder = $this->container->get(JSONResponder::class);

        if ($requestContentType ===  'application/xml') {
            $responder = $this->container->get(XMLResponder::class);
        }

        /** @var Controller $service */
        $service = new $controller($responder, $this->container->get(CommandBusManager::class));
        $service->setRequest($serverRequest);

        return $service->route();
    }
}
