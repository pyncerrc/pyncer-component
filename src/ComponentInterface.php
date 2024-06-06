<?php
namespace Pyncer\Component;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Container\ContainerInterface;
use Pyncer\Http\Message\RequestData;
use Pyncer\Http\Server\RequestResponseInterface;

interface ComponentInterface extends
    ContainerInterface,
    RequestResponseInterface
{
    public function getRequest(): PsrServerRequestInterface;

    public function getQueryParams(): RequestData;

    public function getParsedBody(): RequestData;
}
