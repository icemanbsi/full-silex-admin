<?php
use FullSilex\Helpers\UtilitiesHelper;

/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/24/16
 * Time: 10:41 AM
 */
class AdminRepository extends \FullSilex\Models\Repositories\BaseRepository
{

    public function login($login, $password)
    {
        $admin = Admin::first(array(
            "conditions" => array("email=? AND status=?", Admin::STATUS_ACTIVE, $login)
        ));
        if (!empty($admin)) {
            if ($admin->password_hash == UtilitiesHelper::toHash($password, $admin->salt, $this->app->config('globalSalt'))) {
                AdminSession::table()->delete(array('admin_id' => array($admin->id)));

                // when everything ok, regenerate session
                session_regenerate_id(true);	// change session ID for the current session and invalidate old session ID
                $adminId = $admin->id;
                $sessionId = session_id();

                $adminsession = $this->app->createModel('adminsession', array("admin_id" => $adminId, "session_id" => $sessionId));
                $adminsession->save();
                $this->app->getSession()->set('adminId', $admin->id);

                return $admin;
            }
        }
        return null;
    }
    public function logout() {
        $adminId = $this->app->getSession()->get('adminId');
        $admin = Admin::find_by_id($adminId);
        if($admin){
            $admin->delete();
        }
        $this->app->getSession()->remove('adminId');
    }
}