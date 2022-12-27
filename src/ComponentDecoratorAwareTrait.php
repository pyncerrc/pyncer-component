<?php
namespace Pyncer\Component;

use Pyncer\Component\ComponentDecoratorInterface;

trait ComponentDecoratorAwareTrait
{
    private ?ComponentDecoratorInterface $componentDecorator = null;

    protected function getComponentDecorator(): ?ComponentDecoratorInterface
    {
        return $this->componentDecorator;
    }
    public function setComponentDecorator(
        ?ComponentDecoratorInterface $value
    ): static
    {
        $this->componentDecorator = $value;

        return $this;
    }
}
