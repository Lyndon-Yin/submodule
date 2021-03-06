<?php
namespace Lyndon\Repository\Contracts;


use Illuminate\Support\Collection;

/**
 * Interface RepositoryCriteriaInterface
 * @package Lyndon\Repository\Contracts
 */
interface RepositoryCriteriaInterface
{
    /**
     * Push Criteria for filter the query
     *
     * @param $criteria
     * @return $this
     */
    public function pushCriteria($criteria);

    /**
     * Pop Criteria
     *
     * @param $criteria
     * @return $this
     */
    public function popCriteria($criteria);

    /**
     * Get Collection of Criteria
     *
     * @return Collection
     */
    public function getCriteria();

    /**
     * Skip Criteria
     *
     * @param bool $status
     * @return $this
     */
    public function skipCriteria($status = true);

    /**
     * Reset all Criteria
     *
     * @return $this
     */
    public function resetCriteria();
}
