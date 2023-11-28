<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\LoginToken;

use Abeta\PunchOut\Api\LoginToken\DataInterface;
use Abeta\PunchOut\Api\LoginToken\DataInterfaceFactory;
use Abeta\PunchOut\Api\LoginToken\RepositoryInterface;
use Abeta\PunchOut\Api\LoginToken\SearchResultsInterface;
use Abeta\PunchOut\Api\LoginToken\SearchResultsInterfaceFactory;
use Exception;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * LoginToken repository class
 */
class Repository implements RepositoryInterface
{

    /**
     * @var ResourceModel
     */
    private $resourceModel;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;
    /**
     * @var DataInterfaceFactory
     */
    private $dataFactory;

    /**
     * Repository constructor.
     * @param CollectionFactory $collectionFactory
     * @param ResourceModel $resourceModel
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param DataInterfaceFactory $dataFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ResourceModel $resourceModel,
        SearchResultsInterfaceFactory $searchResultsFactory,
        DataInterfaceFactory $dataFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel = $resourceModel;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataFactory = $dataFactory;
    }

    /**
     * @inheritDoc
     */
    public function getList($searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        return $this->searchResultsFactory->create()
            ->setSearchCriteria($searchCriteria)
            ->setItems($collection->getItems())
            ->setTotalCount($collection->getSize());
    }

    /**
     * @inheritDoc
     */
    public function create(): DataInterface
    {
        return $this->dataFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $entityId): bool
    {
        $entity = $this->get($entityId);
        return $this->delete($entity);
    }

    /**
     * @inheritDoc
     */
    public function get(int $entityId): DataInterface
    {
        if (!$entityId) {
            $exceptionMsg = static::INPUT_EXCEPTION;
            throw new InputException(__($exceptionMsg));
        } elseif (!$this->resourceModel->isExists($entityId)) {
            $exceptionMsg = self::NO_SUCH_ENTITY_EXCEPTION;
            throw new NoSuchEntityException(__($exceptionMsg, $entityId));
        }
        return $this->dataFactory->create()
            ->load($entityId);
    }

    /**
     * @inheritDoc
     */
    public function delete(DataInterface $entity): bool
    {
        try {
            $this->resourceModel->delete($entity);
        } catch (Exception $exception) {
            $exceptionMsg = self::COULD_NOT_DELETE_EXCEPTION;
            throw new CouldNotDeleteException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function save(DataInterface $entity): DataInterface
    {
        try {
            $this->resourceModel->save($entity);
        } catch (Exception $exception) {
            $exceptionMsg = self::COULD_NOT_SAVE_EXCEPTION;
            throw new CouldNotSaveException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function getByToken(string $token, bool $autoRemove = true): DataInterface
    {
        if (!$this->resourceModel->isTokenExists($token)) {
            $exceptionMsg = self::NO_SUCH_TOKEN_EXCEPTION;
            throw new NoSuchEntityException(__($exceptionMsg, $token));
        }

        $tokenData = $this->dataFactory->create()->load($token, DataInterface::TOKEN);
        if ($autoRemove) {
            $this->delete($tokenData);
        }

        return $tokenData;
    }
}
