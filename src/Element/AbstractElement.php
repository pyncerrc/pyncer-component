<?php
namespace Pyncer\Component\Element;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Component\AbstractComponent;
use Pyncer\Component\ComponentInterface;
use Pyncer\Component\Element\ElementComponentInterface;
use Pyncer\Http\Message\JsonResponse;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;

use function is_array;

abstract class AbstractElement extends AbstractComponent implements
    ElementComponentInterface
{
    protected function getResponseData(): mixed
    {
        return null;
    }

    final protected function getPrimaryResponse(): PsrResponseInterface
    {
        $data = $this->getResponseData();

        $data = $this->serialize($data);

        if ($data instanceof PsrResponseInterface) {
            return $data;
        }

        if ($data === null || $data === '' || $data === []) {
            return new Response(
                Status::SUCCESS_204_NO_CONTENT
            );
        }

        return new JsonResponse(
            Status::SUCCESS_200_OK,
            $data
        );
    }

    protected function serialize(mixed $data): mixed
    {
        $serialized = [];

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $serialized[$key] = $this->serialize($value);
            }
        } elseif ($data instanceof ComponentInterface) {
            $serialized = $data->getResponse($this->handler);
        } elseif ($data instanceof JsonResponseInterface) {
            $parsedBody = $this->serialize($data->getParsedBody());
            $serialized = $data->withParsedBody($parsedBody);
        } else {
            $serialized = $data;
        }

        return $serialized;
    }
}
