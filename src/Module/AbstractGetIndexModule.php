<?php
namespace Pyncer\Component\Module;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Http\Message\DataResponse;
use Pyncer\Http\Message\Status;

use function max;
use function Pyncer\Array\intersect_keys as pyncer_array_intersect_keys;

abstract class AbstractGetIndexModule extends AbstractModule
{
    protected function getPrimaryResponse(): PsrResponseInterface
    {
        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();

        $data = [];

        if ($this->getItemTotal()) {
            $totalItems = $mapper->selectNumRows($mapperQuery);
            $data['total_items'] = $totalItems;
        } else {
            $totalItems = 1; // Dummy value to force querying index
        }

        $data['items'] = [];

        if ($totalItems > 0) {
            $limit = $this->getIndexLimit();

            if ($limit[0]) {
                $result = $mapper->selectIndexed(
                    $limit[0],
                    $limit[1],
                    $mapperQuery
                );

                foreach ($result as $model) {
                    $data['items'][] = $this->getResponseItemData($model);
                }
            } else {
                $result = $mapper->selectAll($mapperQuery);

                $count = 0;
                foreach ($result as $model) {
                    ++$count;

                    if ($count <= $limit[1]) {
                        continue;
                    }

                    $item = $this->getResponseItemData($model);
                    if (!$item) {
                        --$count;
                    }

                    $data['items'][] = $item;
                }
            }
        }

        $data = $this->getResponseIndexData($data);

        return new DataResponse(
            Status::SUCCESS_200_OK,
            $data
        );
    }

    protected function getResponseIndexData(array $data): array
    {
        return $data;
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

    private function getIndexLimit(): array
    {
        $count = $this->getItemCount();
        if ($count) {
            $offset = $this->getItemOffset();
        } else {
            $count = $this->getIndexCount();
            $offset = ($this->getIndexOffset() * $count);
        }

        return [$count, $offset];
    }

    /**
    * Gets whether or not the request should return the total number of items.
    */
    protected function getItemTotal(): bool
    {
        return $this->queryParams->getBool('$itemTotal');
    }
    /**
    * Gets the number of items the request should return from the
    * specified item offset.
    *
    * If zero, the getIndexCount will be used instead.
    */
    protected function getItemCount(): int
    {
        $count = $this->queryParams->getInt('$itemCount');
        $count = max(0, $count);

        return $count;
    }
    /**
    * Gets the item offset the query limit should use.
    */
    protected function getItemOffset(): int
    {
        $offset = $this->queryParams->getInt('$itemOffset');
        $offset = max(0, $offset);

        return $offset;
    }

    /**
    * Gets the number of items the request should return from the
    * specified index offset multiplier.
    */
    protected function getIndexCount(): int
    {
        $count = $this->queryParams->getInt('$indexCount');
        $count = max(0, $count);

        return $count;
    }
    /**
    * Gets the index offset to be multipled by getIndexCount.
    *
    * ie. Page number starting from zero.
    */
    protected function getIndexOffset(): int
    {
        $offset = $this->queryParams->getInt('$indexOffset');
        $offset = max(0, $offset);

        return $offset;
    }
}
