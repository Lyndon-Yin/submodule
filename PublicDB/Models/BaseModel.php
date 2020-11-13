<?php
namespace Lyndon\PublicDB\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseModel
 * @package Lyndon\PublicDB\Models
 */
class BaseModel extends Model
{
    protected $connection = 'public-mysql';

    protected $dateFormat = 'Y-m-d H:i:s';

    public $timestamps = false;

    protected $guarded = ['id'];
}
