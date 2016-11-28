<?php
/**
 * Created by Bobby Stenly Irawan (http://bobbystenly.com)
 * Date: 11/2/16
 * Time: 2:05 PM
 */

namespace FullSilexAdmin\Models;

use FullSilex\Models\BaseModel;
use FullSilex\Libraries\EncryptionClass\EncryptionClass;
use FullSilex\Models\Traits\HasImages;

class Setting extends BaseModel
{
    use HasImages;

    static $table_name = "setting";

    static $before_save = array('beforeSave');
    static $before_create = array('beforeCreate');

    public function beforeCreate(){
        $lastSetting = Setting::first(array(
            "order" => 'position desc'
        ));
        if(!empty($lastSetting)){
            $pos = $lastSetting->position + 1;
        }
        else{
            $pos = 1;
        }
        $this->position = $pos;
    }

    public function beforeSave(){
        $crypt = new EncryptionClass();
        if ($this->input_type == "password") {
            $this->value = $crypt->encrypt($this->app->config('globalSalt'), $this->value);
        }
    }
}