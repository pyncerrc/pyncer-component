<?php
namespace Pyncer\Component;

use Pyncer\Container\ContainerInterface;
use Pyncer\Http\Server\RequestResponseInterface;

interface ComponentInterface extends
    ContainerInterface,
    RequestResponseInterface
{}
