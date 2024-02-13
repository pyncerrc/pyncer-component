<?php
namespace Pyncer\Component\Module;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\Validation\ValidatorInterface;
use Pyncer\Database\Exception\QueryException;
use Pyncer\Http\Message\JsonResponse;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;

use function array_merge;
use function Pyncer\Array\ensure_keys as pyncer_array_ensure_keys;
use function Pyncer\Array\intersect_keys as pyncer_array_intersect_keys;
use function Pyncer\Array\unset_keys as pyncer_array_unset_keys;

abstract class AbstractPutItemModule extends AbstractModule
{
    protected ?array $modelData = null;

    protected function getPrimaryResponse(): PsrResponseInterface
    {
        $id = $this->getItemId();
        if (!$id) {
            return new Response(
                Status::CLIENT_ERROR_404_NOT_FOUND
            );
        }

        $model = $this->forgeModel($id);

        if (!$this->isAuthorizedItem($model)) {
            return new Response(
                Status::CLIENT_ERROR_403_FORBIDDEN
            );
        }

        $this->modelData = $model->getData();

        $data = array_merge(
            $this->modelData,
            $this->getRequestItemData(),
            $this->getRequiredItemData()
        );

        [$data, $errors] = $this->validateItemData($data);

        if ($errors) {
            return new JsonResponse(
                Status::CLIENT_ERROR_422_UNPROCESSABLE_ENTITY,
                ['errors' => $errors]
            );
        }

        if ($model->getId()) {
            $model->addData($data);

            $data = pyncer_array_unset_keys($data, $model->getKeys());
            $model->addExtraData($data);

            $insert = false;
        } else {
            $model->setData($data);

            $data = pyncer_array_unset_keys($data, $model->getKeys());
            $model->setExtraData($data);

            $insert = true;
        }

        $errors = $this->replaceItem($model);

        if ($errors) {
            return new JsonResponse(
                Status::CLIENT_ERROR_422_UNPROCESSABLE_ENTITY,
                ['errors' => $errors]
            );
        }

        if ($insert) {
            return new JsonResponse(
                Status::SUCCESS_201_CREATED,
                $this->getResponseItemData($model)
            );
        } else {
            return new JsonResponse(
                Status::SUCCESS_200_OK,
                $this->getResponseItemData($model)
            );
        }
    }

    protected function getItemId(): int
    {
        return $this->queryParams->getInt('id');
    }

    protected function getResponseItemData(ModelInterface $model): array
    {
        $data = $model->getAllData();

        $keys = $this->getResponseItemKeys();
        if ($keys !== null) {
            $data = pyncer_array_intersect_keys($data, $keys);
            $data = pyncer_array_ensure_keys($data, $keys);
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
            $data = pyncer_array_ensure_keys($data, $keys);
        }

        return $data;
    }
    protected function getRequestItemKeys(): ?array
    {
        return null;
    }
    protected function getRequiredItemData(): array
    {
        return ['id' => $this->getItemId()];
    }

    /**
    * @return \Pyncer\Data\Validation\ValidatorInterface
    */
    abstract protected function forgeValidator(): ?ValidatorInterface;

    /**
    * @return \Pyncer\Data\Mapper\MapperInterface
    */
    abstract protected function forgeMapper(): MapperInterface;

    /**
    * @return \Pyncer\Data\MapperQuery\MapperQueryInterface
    */
    protected function forgeMapperQuery(): ?MapperQueryInterface
    {
        return null;
    }

    protected function validateItemData(array $data): array
    {
        $validator = $this->forgeValidator();
        return $validator->validateData($data);
    }

    protected function forgeModel(int $id): ?ModelInterface
    {
        $mapper = $this->forgeMapper();

        // Mapper query is used to ensure any existing non matching model is
        // cleared instead of merged
        $mapperQuery = $this->forgeMapperQuery();
        $model = $mapper->selectById($id, $mapperQuery);

        if (!$model) {
            $model = $mapper->forgeModel();
        }

        return $model;
    }

    protected function isAuthorizedItem(ModelInterface $model): bool
    {
        return true;
    }

    protected function replaceItem(ModelInterface $model): array
    {
        $errors = [];

        if ($model->getId()) {
            $error = 'update';
        } else {
            $error = 'insert';
        }

        try {
            $mapper = $this->forgeMapper();
            $mapper->replace($model);
        } catch (QueryException) {
            $errors['general'] = $error;
        }

        return $errors;
    }
}
