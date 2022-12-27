<?php
namespace Pyncer\Component;

use Pyncer\Component\ComponentInterface;

interface ComponentDecoratorInterface
{
    public function apply(ComponentInterface $component): ?ComponentInterface;
}
