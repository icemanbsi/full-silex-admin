<?php
/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/25/16
 * Time: 9:47 AM
 */

namespace FullSilexAdmin\Controllers;


class AdminsController extends CRUDController
{

    public function login($error = null) {
        $email = $this->request->get("email");
        return $this->render("login", array(
            "email" => $email,
            "error" => $error
        ));
    }

    public function loginProcess() {
        $email = $this->request->get("email");
        $password = $this->request->get("password");
        if (!empty($email)) {
            /** @var \FullSilexAdmin\Models\Repositories\AdminRepository $adminRepository */
            $adminRepository = $this->app->getRepository("admin");
            $admin = $adminRepository->login($email, $password);
            if( !empty($admin) ){
                return $this->app->redirect($this->app->url("admin/home", array("method" => "index")));
            }
            else{
                return $this->login($this->app->trans('invalidUser'));
            }

        }
        else {
            return $this->login($this->app->trans('invalidUser'));
        }
    }

    public function logout(){
        /** @var \FullSilexAdmin\Models\Repositories\AdminRepository $adminRepository */
        $adminRepository = $this->app->getRepository("admin");
        $adminRepository->logout();
        return $this->app->redirect($this->app->url("admins", array("method" => "login")));
    }

    public function forgetPassword(){

    }
}