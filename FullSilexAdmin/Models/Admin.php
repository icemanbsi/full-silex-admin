<?php
use App\Models\Traits\Authorizable;
use FullSilex\Models\BaseModel;
use FullSilex\Models\Traits\HasTimestamp;

/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/24/16
 * Time: 10:41 AM
 */
abstract class Admin extends BaseModel
{
    use Authorizable, HasTimestamp;

    const STATUS_ACTIVE = "active";
    const STATUS_INACTIVE = "inactive";

    const TYPE_ADMIN = "admin";

    static $before_save = array('auth_beforeSave', 'time_beforeSave');
    static $before_create = array('time_beforeCreate');

    public static $hiddenFields = array("password_hash", "activation_key", "salt", "created_at", "updated_at");
    public static $jsonFields = array();
    public static $imageFields = array();

    static $table_name = "admin";

    static $validates_presence_of = array(
        array('name'),
        array('email')
    );

//    static $validates_uniqueness_of = array(
//        array('email')
//    );

    static $validates_format_of = array(
        array('email', 'with' => '/^[^0-9][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/'),
        //array('password', 'with' => '/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', 'message' => 'is too weak')
    );

    public function validate(){
        //somehow validate uniqueness of from phpActiveRecord has some errors. So we need to manually check the email for uniqueness.
        $admins = Admin::all(array(
            "conditions" => array("email=? AND id<>?", $this->email, $this->id)
        ));
        if(!empty($admins)){
            $this->errors->add("email", "has been used by another user.");
        }
    }
}