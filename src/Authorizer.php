<?php
namespace Pyncer\Component;

use Pyncer\Component\AuthorizerInterface;
use Pyncer\Component\ComponentInterface;

class Authorizer implements AuthorizerInterface
{
    /**
     * @var array<AuthorizerInterface>
     */
    protected array $authorizers;

    /**
     * @param AuthorizerInterface ...$authorizers
     * @return static
     */
    public function addAuthorizers(AuthorizerInterface ...$authorizers): static
    {
        foreach ($authorizers as $authorizer) {
            if (!in_array($authorizer, $this->authorizers)) {
                $this->authorizers[] = $authorizer;
            }
        }

        return $this;
    }

    /**
     * @param AuthorizerInterface ...$authorizers
     * @return static
     */
    public function deleteAuthorizers(AuthorizerInterface ...$authorizers): static
    {
        foreach ($authorizers as $authorizer) {
            $index = array_search($authorizer, $this->authorizers);
            if ($index !== false) {
                unset($this->authorizers[$index]);
            }
        }

        $this->authorizers = array_values($this->authorizers);

        return $this;
    }

    /**
     * @param AuthorizerInterface ...$authorizers
     * @return static
     */
    public function hasAuthorizers(AuthorizerInterface ...$authorizers): bool
    {
        foreach ($authorizers as $authorizer) {
            if (!array_key_exists($authorizer, $this->authorizers)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return static
     */
    public function clearAuthorizers(): static
    {
        $this->authorizers = [];

        return $this;
    }

    public function isAuthorized(ComponentInterface $component): bool
    {
        foreach ($authorizers as $authorizer) {
            if (!$authorizer->isAuthroized($component)) {
                return false;
            }
        }

        return true;
    }
}
