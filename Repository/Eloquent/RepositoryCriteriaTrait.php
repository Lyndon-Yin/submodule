<?php
namespace Lyndon\Repository\Eloquent;


use Lyndon\Exceptions\RepositoryException;
use Lyndon\Repository\Contracts\CriteriaInterface;

/**
 * Trait RepositoryCriteriaTrait
 * @package Lyndon\Repository\Eloquent
 */
trait RepositoryCriteriaTrait
{
    /**
     * criteria查询对象列表
     *
     * @var array
     */
    protected $criteria = [];

    /**
     * 是否跳过criteria查询
     *
     * @var bool
     */
    protected $skipCriteria = false;

    /**
     * Push Criteria for filter the query
     *
     * @param $criteria
     *
     * @return $this
     * @throws RepositoryException
     */
    public function pushCriteria($criteria)
    {
        if (is_string($criteria)) {
            $criteria = new $criteria;
        }
        if (!$criteria instanceof CriteriaInterface) {
            throw new RepositoryException("Class " . get_class($criteria) . " must be an instance of Lyndon\\Repository\\Contracts\\CriteriaInterface");
        }

        array_push($this->criteria, $criteria);

        return $this;
    }

    /**
     * Pop Criteria
     *
     * @param $criteria
     *
     * @return $this
     */
    public function popCriteria($criteria)
    {
        $this->criteria = array_filter($this->criteria, function ($item) use ($criteria) {
            if (is_object($item) && is_string($criteria)) {
                return get_class($item) !== $criteria;
            }

            if (is_string($item) && is_object($criteria)) {
                return $item !== get_class($criteria);
            }

            return get_class($item) !== get_class($criteria);
        });
        $this->criteria = array_values($this->criteria);

        return $this;
    }

    /**
     * Get Array of Criteria
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Skip Criteria
     *
     * @param bool $status
     *
     * @return $this
     */
    public function skipCriteria($status = true)
    {
        $this->skipCriteria = $status;

        return $this;
    }

    /**
     * Reset all Criteria
     *
     * @return $this
     */
    public function resetCriteria()
    {
        $this->criteria = [];

        return $this;
    }
}
