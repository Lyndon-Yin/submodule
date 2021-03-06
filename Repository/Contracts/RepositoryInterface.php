<?php
namespace Lyndon\Repository\Contracts;

/**
 * Interface RepositoryInterface
 * @package Lyndon\Repository\Contracts
 */
interface RepositoryInterface
{
    /**
     * Get Searchable Fields
     *
     * @return array
     */
    public function getFieldsSearchable();

    /**
     * 获取默认查询字段
     *
     * @return array
     */
    public function getDefaultSearch();

    /**
     * 获取默认查询字段搜索条件
     *
     * @return array
     */
    public function getDefaultSearchFields();

    /**
     * 获取可搜索字段的别名
     * key是别名，value是fieldSearchable属性元素
     *
     * @return array
     */
    public function getAliasFieldsSearchable();

    /**
     * 获取可搜索字段的类型转换
     *
     * @return mixed
     */
    public function getFieldSearchableCast();

    /**
     * 获取排序字段的别名
     *
     * @return array
     */
    public function getAliasOrderByFields();

    /**
     * 默认排序列表，在未传orderBy参数时启用
     *
     * @return array
     */
    public function defaultOrderByFields();
}
