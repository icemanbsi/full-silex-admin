<?php
/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/25/16
 * Time: 9:47 AM
 */

namespace FullSilexAdmin\Controllers;


use FullSilex\Helpers\ModelHelper;

class AdminsController extends CRUDController
{
    protected $instanceName = 'admins'; // Instance name used in parameter prefix i.e. 'instance' of $this->params['instance']['attributeName']
    protected $title = 'admin'; // Page Title

    // Form tpl files
    protected $addFormTpl = '_form';
    protected $editFormTpl = '_form';
    protected $deleteFormTpl = '/admin/widgets/crud/delete/_deleteForm';
    protected $indexTpl = '/admin/widgets/crud/_index';

    // For redirect when success / error happens
    protected $indexPath = array('route' => 'admin/admins', 'method' => 'index');
    protected $addPath = array('route' => 'admin/admins', 'method' => 'add');
    protected $editPath = array('route' => 'admin/admins', 'method' => 'edit');
    protected $deletePath = array('route' => 'admin/admins', 'method' => 'delete');

    protected $successTarget = 'edit'; // index or edit, where to redirect after success

    // If you don't want to create deleteForm.tpl. define this instead.
    // Sample value: instances/destroy
    protected $destroyPath = array('route' => 'admin/admins', 'method' => 'destroy');

    // -- SORTABLE -- //
    // If you need sortable feature to be set up automatically, set $setupSortable variable to 'true'.
    // This will basically run method setupSortability() in __construct() AND some additions to listData().
    // Inherit & modify these methods when required.
    protected $setupSortable = false;
    // If dataTable items are sortable, set this to field name in database corresponds with dragging
    protected $dragField = null;
    // Path to do reorder after dragging (e.g. instances/reorder)
    protected $reorderPath = null;
    // Id column's index number. No need to set this unless you require to setup sortable manually
    // (i.e. not by simply setting $setupSortable to true. Usually for older projects).
    // protected $sortableIdColumnIndex = 0;
    // -- END - SORTABLE -- //

    public $columns = array('column', 'names');
    public $thAttributes = array(); // Class sort_asc or sort_desc can be used to set default sorting.
    public $columnDefs = '[]'; // Use this to handle columns' behaviours, doc: http://www.datatables.net/usage/columns

    /**
     * Override this with model linked with this controller.
     * Use lowercase.
     */
    protected function model() {
        return 'admin';
    }

    /**
     * Data used in index listing.
     * @return array
     */
    protected function listData() {
        $sql = "SELECT * FROM " . call_user_func(array($this->model(), "table_name"));
        $instances = ModelHelper::objectsToArray( call_user_func(array($this->model(), "find_by_sql"), $sql) );
        $instanceRows = array();
        if (!empty($instances)) {
            foreach ($instances as $instanceArray) {
                $instanceRow = array(
                    // List your field names here
                    $instanceArray['field_name'],

                    $this->listActions($instanceArray)
                );
                if ($this->setupSortable) {
                    $instanceRow[] = $instanceArray['id'];
                    array_unshift($instanceRow, $instanceArray[$this->dragField]);
                }
                $instanceRows[] = $instanceRow;
            }
        }
        return $instanceRows;
    }

    /**
     * Override as needed
     * @param $instance
     */
    protected function afterCreateSuccess($instance)
    {

    }

    /**
     * Override as needed
     * @param $instance
     */
    protected function afterUpdateSuccess($instance)
    {

    }

    protected function beforeAction(){
        if(in_array($this->currentAction, array("login", "loginProcess", "forgetPassword", "logout"))){
            return "";
        }
        else{
            return parent::beforeAction();
        }
    }

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