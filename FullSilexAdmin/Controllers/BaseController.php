<?php
/**
 * Created by Bobby Stenly Irawan (http://bobbystenly.com)
 * Date: 10/13/16
 * Time: 3:26 PM
 */

namespace FullSilexAdmin\Controllers;

use \App\Models\Admin;


class BaseController extends \FullSilex\Controllers\BaseController
{
    protected $user;
    protected $breadcrumbs = array();

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

    protected function setDefaultAssign(){
        $admin = $this->getUser();
        $assign = array(
            "adminUsername" => $admin ? $admin->name : "",
            "adminImage" => "",
            "admin" => $admin ? $admin->to_array() : array(),
            "numDisplayedRows" => $this->getNumDisplayedRows()
        );

        if (!empty($this->breadcrumbs)) {
            $assign["breadcrumbs"] = $this->breadcrumbs;
        }

        return array_merge(parent::setDefaultAssign(), $assign);
    }

    protected function successAction($message, $url) {
        if ($this->app->isAjax()) {
            return json_encode(array('message' => $message));
        }
        else {
            $this->setMessage($message);
            return $this->app->redirect( $url );
        }
    }

    protected function setNumDisplayedRows($numRows){
        setcookie("numDisplayedRows", $numRows, time() + 86400, "/");
    }

    protected function getNumDisplayedRows(){
        return !empty($_COOKIE["numDisplayedRows"]) ? $_COOKIE["numDisplayedRows"] : 10;
    }
}