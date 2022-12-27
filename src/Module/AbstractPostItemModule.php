<?php
namespace Pyncer\Component\Module;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\Validation\ValidatorInterface;
use Pyncer\Database\Exception\QueryException;
use Pyncer\Http\Message\JsonResponse;
use Pyncer\Http\Message\Status;

use function array_merge;
use function Pyncer\Array\intersect_keys as pyncer_array_intersect_keys;
use function strval;

abstract class AbstractPostItemModule extends AbstractModule
{
    protected function getPrimaryResponse(): PsrResponseInterface
    {
        $model = $this->forgeModel();

        $data = array_merge(
            $model::getDefaultData(),
            $this->getRequestItemData(),
            $this->getRequiredItemData()
        );

        list ($data, $errors) = $this->validateItemData($data);

        if ($errors) {
            return new JsonResponse(
                Status::CLIENT_ERROR_422_UNPROCESSABLE_ENTITY,
                ['errors' => $errors]
            );
        }

        $model->setData($data);

        $errors = $this->insertItem($model);

        if ($errors) {
            return new JsonResponse(
                Status::CLIENT_ERROR_422_UNPROCESSABLE_ENTITY,
                ['errors' => $errors]
            );
        }

        return (new JsonResponse(
            Status::SUCCESS_201_CREATED,
            $this->getResponseItemData($model)
        ))->withAddedHeader(
            'Location',
            $this->getResourceUrl($model)
        );
    }

    protected function getResponseItemData(ModelInterface $model): array
    {
        $data = $model->getAllData();

        $keys = $this->getResponseItemKeys();
        if ($keys !== null) {
            $data = pyncer_array_intersect_keys($data, $keys);
        }

        return $data;
    }
    protected function getResponseItemKeys(): ?array
    {
        return null;
    }

    protected function getRequestItemData(): array
    {
        $data = $this->parsedBody->getData();

        $keys = $this->getRequestItemKeys();
        if ($keys !== null) {
            $data = pyncer_array_intersect_keys($data, $keys);
        }

        return $data;
    }
    protected function getRequestItemKeys(): ?array
    {
        return null;
    }

    protected function getRequiredItemData(): array
    {
        return ['id' => 0];
    }

    /**
    * @return \Pyncer\Data\Validation\ValidatorInterface
    */
    abstract protected function forgeValidator(): ?ValidatorInterface;

    /**
    * @return \Pyncer\Data\Mapper\MapperInterface
    */
    abstract protected function forgeMapper(): MapperInterface;

    protected function forgeModel(): ModelInterface
    {
        $mapper = $this->forgeMapper();
        return $mapper->forgeModel();
    }

    protected function validateItemData(array $data): array
    {
        $validator = $this->forgeValidator();
        return $validator->validateData($data);
    }

    protected function getResourceUrl(ModelInterface $model): string
    {
        $url = $this->request->getUri();
        return strval($url->withPath($url->getPath() . '/' . $model->getId()));
    }

    protected function insertItem(ModelInterface $model): array
    {
        $errors = [];

        try {
            $mapper = $this->forgeMapper();
            $mapper->insert($model);
        } catch (QueryException) {
            $errors['general'] = 'insert';
        }

        return $errors;
    }
}
