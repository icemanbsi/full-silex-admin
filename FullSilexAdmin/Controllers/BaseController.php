<?php
/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/13/16
 * Time: 3:26 PM
 */

namespace FullSilexAdmin\Controllers;

use \App\Models\Admin;


class BaseController extends \FullSilex\Controllers\BaseController
{
    protected $user;

    protected function getUser(){
        $adminId = $this->app->getSession()->get("adminId");
        if($adminId) {
            $this->user = Admin::find_by_id($adminId);
        }
        return $this->user;
    }

    protected function beforeAction(){
        return $this->isLogin();
    }

    protected function isLogin(){
        if($this->app->getSession()->get("adminId") == null){
            return $this->app->redirect($this->app->url("admin/admins", array("method" => "login")));
        }
        else{
            return "";
        }
    }

    protected function setAdditionalAssign(){
        $admin = $this->getUser();
        return array(
            'adminUsername' => $admin ? $admin->name : "",
            'adminImage' => '',
            'admin' => $admin
        );
    }
}