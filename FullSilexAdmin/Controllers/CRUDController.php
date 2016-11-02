<?php

/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/13/16
 * Time: 3:13 PM
 */

namespace FullSilexAdmin\Controllers;

use FullSilex\Helpers\ModelHelper;

class CRUDController extends BaseController
{
// These variables MUST be overridden in inherited class!
    // --START-- //
    protected $instanceName = 'instance'; // Instance name used in parameter prefix i.e. 'instance' of $this->params['instance']['attributeName']
    protected $title = 'instance'; // Page Title

    // Form tpl files
    protected $addFormTpl = '/admin/widgets/crud/add/_addForm';
    protected $editFormTpl = '/admin/widgets/crud/add/_addForm';
    protected $deleteFormTpl = '/admin/widgets/crud/delete/_deleteForm';
    protected $indexTpl = '/admin/widgets/crud/_index';

    // For redirect when success / error happens
    protected $indexPath = array('route' => 'instances', 'method' => 'index');
    protected $addPath = array('route' => 'instances', 'method' => 'add');
    protected $editPath = array('route' => 'instances', 'method' => 'edit');
    protected $deletePath = array('route' => 'instances', 'method' => 'delete');

    protected $successTarget = 'edit'; // index or edit, where to redirect after success

    // If you don't want to create deleteForm.tpl. define this instead.
    // Sample value: instances/destroy
    protected $destroyPath = array('route' => 'instances', 'method' => 'destroy');

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

    // --END-- //
    // Form wrapper tpl files. You do not always need to update this, but when you do, override these vars.
    protected $addAjaxTpl = '/admin/widgets/crud/add/_addAjax';
    protected $addNoAjaxTpl = '/admin/widgets/crud/add/_addNoAjax';
    protected $editAjaxTpl = '/admin/widgets/crud/edit/_editAjax';
    protected $editNoAjaxTpl = '/admin/widgets/crud/edit/_editNoAjax';
    protected $deleteAjaxTpl = '/admin/widgets/crud/delete/_deleteAjax';
    protected $deleteNoAjaxTpl = '/admin/widgets/crud/delete/_deleteNoAjax';


    // Minimum overriding requirements //
    /**
     * Override this with model linked with this controller.
     * Use lowercase.
     */
    protected function model() {
        return 'App\Models\Model';
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

    // END - Minimum overriding requirements //


    // Override these functions if needed

    protected function setupAdditionalAssigns($instance) {
        return array();
    }

    public function index() {
        if($this->app->isAjax()){
            return json_encode(array('aaData' => $this->listData()));
        }
        else{
            return $this->render($this->indexTpl, array(
                'indexPath' => $this->indexPath,
                'addPath' => $this->addPath,
                'columns' => $this->columns,
                'thAttributes' => $this->thAttributes,
                'columnDefs' => $this->columnDefs,
                'instanceName' => $this->instanceName,
                'title' => $this->title
            ));
        }
    }

    protected function listActions($instanceArray)
    {
        $actions = '<div class="text-right">
                    <a title="Edit" href="'.$this->app->url($this->editPath["route"], array('method' => $this->editPath['method'], 'id' => $instanceArray['id'])).'"><span class="fa fa-pencil"></span></a>
					<a title="Delete" href="'.$this->app->url($this->deletePath["route"], array('method' => $this->deletePath['method'], 'id' => $instanceArray['id'])).'" data-toggle="dialog"><span class="fa fa-trash"></span></a>
					</div>';
        if (!is_null($this->dragField)) {
            $actions .='<input type="hidden" class="id" value="'.$instanceArray['id'].'"/>
					<input type="hidden" class="'.$this->dragField.'" value="'.$instanceArray[$this->dragField].'"/>';
        }
        return $actions;
    }


    // ------- end of override these function if needed


    // Please do not override these functions, unless you know the risk.
    public function add() {
        $this->breadcrumbs[] = array('url' => $this->app->url($this->indexPath["route"], array("method" => $this->indexPath["method"])), 'name' => ucfirst($this->title) . ' List');
        $this->breadcrumbs[] = array('url' => '', 'name' => 'New '.$this->title);
        $instance = $this->findInstance(false);
        $instance = $this->setInstanceAttributes($instance);
        $assigns = $this->setupAssigns($instance);
        $assigns["form"] = $this->render($this->addFormTpl, $assigns);
        if ($this->app->isAjax()) {
            return $this->render($this->addAjaxTpl, $assigns);
        }
        else {
            return $this->render($this->addNoAjaxTpl, $assigns);
        }
    }

    public function edit() {
        $this->breadcrumbs[] = array('url' => $this->app->url($this->indexPath["route"], array("method" => $this->indexPath["method"])), 'name' => ucfirst($this->title) . ' List');
        $this->breadcrumbs[] = array('url' => '', 'name' => 'Edit '.$this->title);
        $instance = $this->findInstance(false);
        if (is_null($instance)) {
            $assigns = array_merge($this->setupAssigns($instance), array(
                'error' => $this->app->trans('instanceNotFound', array('model' => $this->title)),
                'errorAttributes' => array(),
                'instance' => $this->instanceName
            ));
        }
        else {
            $instance = $this->setInstanceAttributes($instance);
            $assigns = $this->setupAssigns($instance);
        }
        $assigns["form"] = $this->render($this->editFormTpl, $assigns);
        if ($this->app->isAjax()) {
            return $this->render($this->editAjaxTpl, $assigns);
        }
        else {
            return $this->render($this->editNoAjaxTpl, $assigns);
        }
    }

    public function delete() {
        $instance = $this->findInstance(false);
        if (is_null($instance)) {
            $assigns = array(
                'error' => $this->app->trans('instanceNotFound', array('model' => $this->title)),
                'errorAttributes' => array(),
                'instance' => $this->instanceName
            );
        }
        else {
            $assigns = $this->setupAssigns($instance);
            if ($this->destroyPath != null) {
                $assigns["destroyPath"] = $this->destroyPath;
            }
        }
        $assigns["form"] = $this->render($this->deleteFormTpl, $assigns);
        if ($this->app->isAjax()) {
            $this->render($this->deleteAjaxTpl, $assigns);
        }
        else {
            $this->render($this->deleteNoAjaxTpl, $assigns);
        }
    }

    public function create() {
        $this->breadcrumbs[] = array('url' => $this->app->url($this->indexPath["route"], array("method" => $this->indexPath["method"])), 'name' => ucfirst($this->title) . ' List');
        $this->breadcrumbs[] = array('url' => '', 'name' => 'New '.$this->title);

        if (!empty($this->request->get($this->instanceName)) && !empty($this->request->get($this->instanceName)['id'])) {
            // For upload images, where image upload will return id to form.
            $instance = $this->findInstance(true);
        }
        else {
            $instance = $this->findInstance(false);
        }

        $instance = $this->setInstanceAttributes($instance);
        if ($instance->errors->is_empty()) {
            try {
                $instance->save();
            }
            catch (\Exception $e) {
                $message = $e->getMessage();
                // When message is unrelated to instance validation, instance
                // has no error, but error message is not empty.
                if (!empty($message) && $instance->errors->is_emtpy()) {
                    $instance->add("", $message);
                }
                $this->app->log("error happened when updating from CRUDController (model ".$this->model()."): " . $message);
//                $this->app->debugBacktrace();
                $this->app->log("params are: " . $this->request->getContent());
            }
        }

        if ($instance->errors->is_empty()) {
            $this->afterCreateSuccess($instance);
            if ($this->successTarget == 'edit') {
                return $this->successAction($this->app->trans('created'), $this->app->url($this->editPath["route"], array("method" => $this->editPath["method"], 'id' => $instance->id)));
            }
            else {
                return $this->successAction($this->app->trans('created'), $this->app->url($this->indexPath["route"], array("method" => $this->indexPath["method"])));
            }
        }
        else {
            if ($this->app->isAjax()) {
                return $this->displayInstanceErrors($instance, $this->instanceName, $this->addAjaxTpl);
            }
            else {
                return $this->displayInstanceErrors($instance, $this->instanceName, $this->addNoAjaxTpl);
            }
        }
    }

    public function update() {
        $this->breadcrumbs[] = array('url' => $this->app->url($this->indexPath["route"], array("method" => $this->indexPath["method"])), 'name' => ucfirst($this->title) . ' List');
        $this->breadcrumbs[] = array('url' => '', 'name' => 'Edit '.$this->title);
        $instance = $this->findInstance(true);
        $error = true;

        if (is_null($instance)) {
            $errorMessage = $this->app->trans('instanceNotFound', array('model' => $this->title));
            if ($this->app->isAjax()) {
                return $this->displayErrors($errorMessage, $this->editAjaxTpl, $this->model());
            }
            else {
                return $this->displayErrors($errorMessage, $this->editNoAjaxTpl, $this->model());
            }
        }
        else {
            $instance = $this->setInstanceAttributes($instance);

            if ($instance->errors->is_empty()) {
                try {
                    $this->afterUpdateSuccess($instance);
                    $instance->save();

                    $error = false;

                    if ($this->successTarget == 'edit') {
                        return $this->successAction($this->app->trans('updated'), $this->app->url($this->editPath["route"], array("method" => $this->editPath["method"], 'id' => $instance->id)));
                    }
                    else {
                        return $this->successAction($this->app->trans('updated'), $this->app->url($this->indexPath["route"], array("method" => $this->indexPath["method"])));
                    }
                }
                catch (\Exception $e) {
                    $message = $e->getMessage();
                    // When message is unrelated to instance validation, instance
                    // has no error, but error message is not empty.
                    if (!empty($message) && $instance->errors->is_emtpy()) {
                        $instance->add("", $message);
                    }
                    $this->app->log("error happened when updating from CRUDController (model ".$this->model()."): " . $message);
//                    $this->app->debugBacktrace();
                    $this->app->log("params are: " . print_r($this->getParams(), true));
                }
            }
            if ($error) {
                if ($this->app->isAjax()) {
                    return $this->displayInstanceErrors($instance, $this->instanceName, $this->editAjaxTpl);
                }
                else {
                    return $this->displayInstanceErrors($instance, $this->instanceName, $this->editNoAjaxTpl);
                }
            }
        }
    }

    public function destroy() {
        $instance = $this->findInstance(true);
//        $instance = $this->setInstanceAttributes($instance);
        $error = true;
        if ($instance->errors->is_empty()) {
            try {
                $this->app->log("trying to delete data..");
                $instance->delete();
                $error = false;
                return $this->successAction($this->app->trans('deleted'), $this->app->url($this->indexPath["route"], array("method" => $this->indexPath["method"])));
            }
            catch (\Exception $e) {
                $message = $e->getMessage();
                // When message is unrelated to instance validation, instance
                // has no error, but error message is not empty.
                if (!empty($message) && $instance->errors->is_emtpy()) {
                    $instance->add("", $message);
                }
                $this->app->log("Cannot delete data : " . $e->getMessage());
            }
        }
        if ($error) {
            if ($this->app->isAjax()) {
                return $this->displayInstanceErrors($instance, $this->instanceName, $this->deleteAjaxTpl);
            }
            else {
                return $this->displayInstanceErrors($instance, $this->instanceName, $this->deleteNoAjaxTpl);
            }
        }
    }

    protected function decideIdSource($post) {
        $id = '';
        if ($post) {
            if (!empty($this->request->get($this->instanceName)) && !empty($this->request->get($this->instanceName)['id'])) {
                $id = $this->request->get($this->instanceName)['id'];
            }
        }
        else {
            if (!empty($this->request->get('id'))) {
                $id = $this->request->get('id');
            }
        }
        return $id;
    }

    protected function findInstance($post = false) {
        $id = $this->decideIdSource($post);

        $instance = null;
        if (!empty($id)) {
            $instance = call_user_func(array($this->model(), "find_by_id"), $id);
            if (empty($instance)) {
                return null;
            }
        }
        else {
            if (!in_array($this->currentAction, array('edit', 'update', 'delete', 'destroy'))) {
                $instance = $this->app->createModel($this->model());
            }
        }
        return $instance;
    }

    /**
     * @param \Full-Silex\Models\BaseModel $instance
     * @return mixed
     */
    protected function setInstanceAttributes($instance) {
        if (!empty($this->request->get($this->instanceName))) {
            $instance->update_attributes($this->request->get($this->instanceName));
        }
        return $instance;
    }

    protected function setupInstanceAssigns($instance) {
//        if (empty($instance)) {
//            return $this->app->redirect($this->app->url("admin/home", array("method" => "notFound")));
//        }

        $currentAction = $this->currentAction;
        if ($currentAction == 'edit') {
            $action = 'update';
        }
        elseif ($currentAction == 'add') {
            $action = 'create';
        }
        else {
            $action = $currentAction;
        }

        $assigns = array(
            $this->instanceName => empty($instance) ? array() : $instance->to_array(),
            'instanceName' => $this->instanceName,
            'title' => $this->title,
            'isAjax' => $this->app->isAjax(),
            'action' => $action,
            "message" => $this->showMessage("message"),
            "error" => $this->showMessage("error")
        );

        if ($this->destroyPath != null) {
            $assigns['destroyPath'] = $this->destroyPath;
        }

        return $assigns;
    }

    protected function setupAssigns($instance) {
        return array_merge(
            $this->setupInstanceAssigns($instance),
            $this->setupAdditionalAssigns($instance)
        );
    }

    protected function displayInstanceErrors($modelInstance, $instanceName, $template) {
        $errorObjects = array();
        foreach($modelInstance->errors->to_array() as $key => $value){
            $errorObjects[$key] = implode(' ', $value);
        }
        return $this->displayErrors($modelInstance->errorMessages('<br/>'), $template, $instanceName, $modelInstance, $errorObjects);
    }

    protected function displayErrors($message, $template = '', $instanceName = '', $modelInstance = null, $errorObjects = array())
    {
        if ($this->app->isAjax()) {
            return json_encode(array(
                'error' => $message,
                'errorAttributes' => $errorObjects,
                'instance' => $instanceName
            ));
        }
        else {
            $formTemplate = array();
            if($template == $this->addNoAjaxTpl || $template == $this->addAjaxTpl) $formTemplate = $this->addFormTpl;
            else if($template == $this->editNoAjaxTpl || $template == $this->editAjaxTpl) $formTemplate = $this->editFormTpl;
            else if($template == $this->deleteNoAjaxTpl || $template == $this->deleteAjaxTpl) $formTemplate = $this->deleteFormTpl;

            if(!empty($modelInstance)){
                $assigns = $this->setupAssigns($modelInstance);
            }

            if(!empty($formTemplate)){
                $assigns["form"] = $this->render($formTemplate, $assigns);
            }

            $assigns = array_merge($assigns, array(
                'error' => $message,
                'errorAttributes' => $errorObjects,
                'instance' => $instanceName
            ));

            return $this->render($template, $assigns);
        }
    }
}