<?php declare(strict_types=1);

namespace Abeta\PunchOut\Api\LoginToken;

use Magento\Framework\Api\SearchResultsInterface as FrameworkSearchResultsInterface;

/**
 * @api
 */
interface SearchResultsInterface extends FrameworkSearchResultsInterface
{

    /**
     * Gets sample items
     *
     * @return DataInterface[]
     */
    public function getItems(): array;

    /**
     * Sets sample items
     *
     * @param DataInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;
}
