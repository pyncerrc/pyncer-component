<?php
namespace Pyncer\Component;

use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Pyncer\Container\ContainerInterface;
use Pyncer\Http\Message\RequestData;
use Pyncer\Http\Server\RequestResponseInterface;

interface ComponentInterface extends
    ContainerInterface,
    RequestResponseInterface
{
    public function getRequest(): PsrRequestInterface;

    public function getQueryParams(): RequestData;

    public function getParsedBody(): RequestData;
}
