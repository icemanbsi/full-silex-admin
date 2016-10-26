<?php
namespace FullSilexAdmin\Models;

use FullSilex\Models\BaseModel;
use FullSilex\Models\Traits\HasTimestamp;

/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/24/16
 * Time: 10:42 AM
 */
abstract class AdminSession extends BaseModel
{
    use HasTimestamp;

    static $before_create = array('time_beforeCreate');
    static $before_save = array('time_beforeSave');

    public static $hiddenFields = array();
    public static $jsonFields = array();
    public static $imageFields = array();

    static $table_name = "adminsession";
}