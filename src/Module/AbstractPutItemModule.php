<?php
namespace Pyncer\Component\Module;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\Validation\ValidatorInterface;
use Pyncer\Database\Exception\QueryException;
use Pyncer\Http\Message\JsonResponse;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;

use function array_merge;
use function Pyncer\Array\intersect_keys as pyncer_array_intersect_keys;

abstract class AbstractPutItemModule extends AbstractModule
{
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

        $data = array_merge(
            $model->getData(),
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

        if ($model->getId()) {
            $model->addData($data);
            $insert = false;
        } else {
            $model->setData($data);
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

    protected function validateItemData(array $data): array
    {
        $validator = $this->forgeValidator();
        return $validator->validateData($data);
    }

    protected function forgeModel(int $id): ?ModelInterface
    {
        $mapper = $this->forgeMapper();
        $model = $mapper->selectById($id);

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
