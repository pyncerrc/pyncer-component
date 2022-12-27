<?php
namespace Pyncer\Component;

interface ComponentDecoratorAwareInterface
{
    public function setComponentDecorator(
        ?ComponentDecoratorInterface $value
    ): static;
}
