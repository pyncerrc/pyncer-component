<?php
namespace Pyncer\Component\Module;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\Exception\QueryException;
use Pyncer\Http\Message\JsonResponse;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;

abstract class AbstractDeleteItemModule extends AbstractModule
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

        if (!$model) {
            return new Response(
                Status::CLIENT_ERROR_404_NOT_FOUND
            );
        }

        if (!$this->isAuthorizedItem($model)) {
            return new Response(
                Status::CLIENT_ERROR_403_FORBIDDEN
            );
        }

        $errors = $this->deleteItem($model);

        if ($errors) {
            return new JsonResponse(
                Status::CLIENT_ERROR_422_UNPROCESSABLE_ENTITY,
                ['errors' => $errors]
            );
        }

        return new Response(
            Status::SUCCESS_204_NO_CONTENT
        );
    }

    protected function getItemId(): int
    {
        return $this->queryParams->getInt('id');
    }

    protected function deleteItem(ModelInterface $model): array
    {
        $errors = [];

        try {
            $mapper = $this->forgeMapper();
            $mapper->delete($model);
        } catch (QueryException) {
            $errors['general'] = 'delete';
        }

        return $errors;
    }

    /**
    * @return \Pyncer\Data\Mapper\MapperInterface
    */
    abstract protected function forgeMapper(): MapperInterface;

    protected function forgeModel(int $id): ?ModelInterface
    {
        $mapper = $this->forgeMapper();
        return $mapper->selectById($id);
    }

    protected function isAuthorizedItem(ModelInterface $model): bool
    {
        return true;
    }
}
