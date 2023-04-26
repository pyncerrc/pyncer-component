<?php
namespace Pyncer\Component\Authorizer;

use Pyncer\Component\ComponentInterface;

interface AuthorizerInterface
{
    public function isAuthorized(ComponentInterface $component): bool;
}
