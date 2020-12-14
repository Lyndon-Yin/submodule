<?php
namespace Lyndon\PublicDB\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BaseModel
 * @package Lyndon\PublicDB\Models
 */
class BaseModel extends Model
{
    use SoftDeletes;

    protected $connection = 'public-mysql';

    protected $guarded = ['id'];

    /**
     * 重写，使时间格式正确展示
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date) : string
    {
        return $date->format($this->getDateFormat());
    }
}
