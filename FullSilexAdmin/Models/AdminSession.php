<?php
use FullSilex\Models\BaseModel;

/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/24/16
 * Time: 10:42 AM
 */
abstract class AdminSession extends BaseModel
{
    public static $hiddenFields = array();
    public static $jsonFields = array();
    public static $imageFields = array();

    static $table_name = "adminsession";
}