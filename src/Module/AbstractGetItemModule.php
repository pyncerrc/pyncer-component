<?php
namespace Pyncer\Component\Module;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Http\Message\JsonResponse;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;

use function Pyncer\Array\ensure_keys as pyncer_array_ensure_keys;
use function Pyncer\Array\intersect_keys as pyncer_array_intersect_keys;

abstract class AbstractGetItemModule extends AbstractModule
{
    protected function getPrimaryResponse(): PsrResponseInterface
    {
        $response = parent::getBeforeResponse();
        if ($response !== null) {
            return $response;
        }

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

        return new JsonResponse(
            Status::SUCCESS_200_OK,
            $this->getResponseItemData($model)
        );
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

    protected function forgeModel(int $id): ?ModelInterface
    {
        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        return $mapper->selectById($id, $mapperQuery);
    }

    protected function isAuthorizedItem(ModelInterface $model): bool
    {
        return true;
    }
}
