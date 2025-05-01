<?php
namespace Pyncer\Component;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Log\LoggerAwareInterface as PsrLoggerAwareInterface;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Pyncer\Component\Authorizer\Authorizer;
use Pyncer\Component\Authorizer\AuthorizerInterface;
use Pyncer\Component\ComponentInterface;
use Pyncer\Container\Exception\ContainerException;
use Pyncer\Http\Message\RequestData;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\RequestHandlerInterface;

use function array_keys;
use function in_array;

abstract class AbstractComponent implements
    ComponentInterface,
    PsrLoggerAwareInterface
{
    use PsrLoggerAwareTrait;

    protected PsrServerRequestInterface $request;
    protected ?PsrResponseInterface $response;
    protected RequestData $parsedBody;
    protected RequestData $queryParams;
    protected RequestHandlerInterface $handler;
    protected AuthorizerInterface $authorizer;

    public function __construct(
        PsrServerRequestInterface $request
    ) {
        // Remove all attributes
        foreach (array_keys($request->getAttributes()) as $attribute) {
            $request = $request->withoutAttribute($attribute);
        }
        $this->request = $request;

        $this->response = null;

        if (in_array($request->getMethod(), ['PATCH', 'POST', 'PUT'])) {
            $this->parsedBody = RequestData::fromParsedBody($this->request);
        } else {
            $this->parsedBody = new RequestData();
        }

        $this->queryParams = RequestData::fromQueryParams($this->request);

        $this->initializeAuthorizer();
    }

    protected function initializeAuthorizer(): void
    {
        $this->authorizer = new Authorizer();
    }

    public function getAuthorizer(): AuthorizerInterface
    {
        return $this->authorizer;
    }

    public function getRequestHandler(): ?RequestHandlerInterface
    {
        return $this->handler;
    }

    public final function get(string $id): mixed
    {
        if (!$this->handler) {
            throw new ContainerException('Container not initialized.');
        }

        return $this->handler->get($id);
    }

    public final function set(string $id, mixed $value): static
    {
        if (!$this->handler) {
            throw new ContainerException('Container not initialized.');
        }

        $this->handler->set($id, $value);
        return $this;
    }

    public final function has(string $id): bool
    {
        if (!$this->handler) {
            throw new ContainerException('Container not initialized.');
        }

        return $this->handler->has($id);
    }

    public function getRequest(): PsrServerRequestInterface
    {
        return $this->request;
    }

    public function getQueryParams(): RequestData
    {
        return $this->queryParams;
    }

    public function getParsedBody(): RequestData
    {
        return $this->parsedBody;
    }

    final public function getResponse(
        RequestHandlerInterface $handler
    ): ?PsrResponseInterface
    {
        if ($this->response !== null) {
            return $this->response;
        }

        $this->handler = $handler;

        $this->initializeResponse();

        $response = $this->getBeforeResponse();
        if ($response !== null) {
            $this->response = $response;
            return $this->response;
        }

        $primaryResponse = $this->getPrimaryResponse();
        $status = Status::from($primaryResponse->getStatusCode());

        if (!$status->isSuccess()) {
            return $primaryResponse;
        }

        $response = $this->getAfterResponse();
        if ($response !== null) {
            $this->response = $response;
            return $this->response;
        }

        $this->response = $primaryResponse;
        return $this->response;
    }

    /**
     * Determines if the current request is valid.
     *
     * If it is not, a 404 status will be returned.
     */
    protected function isValidRequest(): bool
    {
        return true;
    }

    /**
     * Determines if the the current request is authorized.
     *
     * If it is not, a 403 status will be returned.
     */
    protected function isAuthorizedRequest(): bool
    {
        return $this->getAuthorizer()->isAuthorized($this);
    }

    protected function initializeResponse(): void
    {}

    protected function getBeforeResponse(): ?PsrResponseInterface
    {
        if (!$this->isValidRequest()) {
            return new Response(
                Status::CLIENT_ERROR_404_NOT_FOUND
            );
        }

        if (!$this->isAuthorizedRequest()) {
            return new Response(
                Status::CLIENT_ERROR_403_FORBIDDEN
            );
        }

        return null;
    }

    protected function getAfterResponse(): ?PsrResponseInterface
    {
        return null;
    }

    abstract protected function getPrimaryResponse(): PsrResponseInterface;
}
