<?php
namespace Pyncer\Component\Module;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\Exception\QueryException;
use Pyncer\Database\Record\DeleteQueryInterface;
use Pyncer\Http\Message\JsonResponse;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;

abstract class AbstractDeleteIndexModule extends AbstractModule
{
    protected function getPrimaryResponse(): PsrResponseInterface
    {
        $connection = $this->get(ID::DATABASE);

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();

        $query = $mapper->forgeSelectQuery($mapperQuery)
            ->columns('id')
            ->limit(500);

        $success = 0;
        $errors = [];

        while (true) {
            // Keep executing the query grabbing 500 rows at a time
            // until there are no more rows
            $result = $query->execute();

            if (!$connection->numRows($result)) {
                break;
            }

            while ($id = $connection->fetchValue($result)) {
                $model = $mapper->selectById($id);

                $deleteErrors = $this->deleteItem($model);
                if (!$deleteErrors) {
                    ++$success;
                    continue;
                }

                foreach ($deleteErrors as $key => $value) {
                    if (!array_key_exists($key, $errors)) {
                        $errors[$key] = [];
                    }

                    if (!array_key_exists($value, $errors[$key])) {
                        $errors[$key][$value] = 0;
                    }

                    ++$errors[$key][$value];
                }
            }
        }

        if ($errors) {
            return new JsonResponse(
                Status::CLIENT_ERROR_422_UNPROCESSABLE_ENTITY,
                [
                    'success' => $success,
                    'errors' => $errors,
                ]
            );
        }

        return new Response(
            Status::SUCCESS_204_NO_CONTENT,
        );
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

    /**
    * @return \Pyncer\Data\MapperQuery\MapperQueryInterface
    */
    abstract protected function forgeMapperQuery(): ?MapperQueryInterface;
}
