<?php
namespace Pyncer\Component;

use Pyncer\Component\ComponentInterface;

interface AuthorizerInterface
{
    public function isAuthorized(ComponentInterface $component): bool;
}
