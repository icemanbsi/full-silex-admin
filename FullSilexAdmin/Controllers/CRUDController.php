<?php

/**
 * Created by Bobby Stenly Irawan (http://bobbystenly.com)
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
    protected $sortableIdColumnIndex = 0;
    // -- END - SORTABLE -- //

    // if you dealing with a large data, you can use data tables server side option.
    protected $dataTableServerSide = false;

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


    protected $dtsFields = array();
    protected $primaryKey = "id";

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
        $sql = "SELECT * FROM " . $this->getTableName();
        $instances = ModelHelper::objectsToArray( call_user_func(array($this->model(), "find_by_sql"), $sql) );
        $instanceRows = array();
        if (!empty($instances)) {
            foreach ($instances as $instanceArray) {
                $instanceRow = array(
                    // List your field names here
                    $instanceArray['field_name'],

                    $this->listActions($instanceArray)
                );
                if ($this->setupSortable && !$this->dataTableServerSide) {
                    $instanceRow[] = $instanceArray[$this->primaryKey];
                    array_unshift($instanceRow, $instanceArray[$this->dragField]);
                }
                $instanceRows[] = $instanceRow;
            }
        }
        return $instanceRows;
    }

    protected function dataTableServerSideFields(){
        return array(
            array(  'column'    => 'Name',
                'field'     => 'guest_name',
                'rawSql'    => 'g.name'),
            array(  'column'    => 'Cert Card',
                'field'     => 'cert',
                'rawSql'    => "concat(g.cert_type, ' - ', g.cert_number)" ),
            array(  'column'    => 'Room',
                'field'     => 'room_name',
                'rawSql'    => 'r.name' ),
            array(  'column'    => 'Room Type',
                'field'     => 'room_type',
                'rawSql'    => 'c.name' ),
            array(  'column'    => 'Check In',
                'field'     => 'check_in',
                'prefix'    => 't',
                'filter'    => 'between'),
            array(  'column'    => 'Due Out',
                'field'     => 'due_out',
                'prefix'    => 't',
                'filter'    => 'between' ),
            array(  'column'    => 'Check Out',
                'field'     => 'check_out',
                'prefix'    => 't',
                'filter'    => 'between' ),
            array(  'column'    => 'Actions',
                'field'     => 't_id',
                'rawSql'    => 't.id',
                'formatter' => function( $value, $array ) {
                    return  '<a title="View" href="'.$this->app->getRouter()->getUrl($this->editPath, array('id' => $value)).'" data-toggle="dialog"><span class="fa fa-pencil"></span></a>
                                  <a title="Delete" href="'.$this->app->getRouter()->getUrl($this->deletePath, array('id' => $value)).'" data-toggle="dialog"><span class="fa fa-trash"></span></a>';
                })
        );
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

    /**
     * Data used in index listing when dataTableServerSide is turned on
     * @return array
     */
    protected function listDataServerSide() {
        $request = $_GET;

        // Build the SQL query string from the request
        $limit  = $this->limit( $request, $this->dtsFields );
        $where  = $this->filter( $request, $this->dtsFields );
        $order  = $this->order( $request, $this->dtsFields );

        $data   = null;

        if( $this->checkUnionSqlForHaving( $this->dtsFields ) ) {
            $having = $this->filterHaving( $request, $this->dtsFields );
            $selectSql    = "(SELECT " . $this->pluckString($this->dtsFields, 'db') . "
                 FROM {$this->getTableName(true)} t
                 $where)

                 UNION

                 (SELECT " . $this->pluckString($this->dtsFields, 'db') . "
                 FROM {$this->getTableName(true)} t
                 $having)";

            $sql = "$selectSql $order $limit";
        }
        else {
            $selectSql = "SELECT SQL_CALC_FOUND_ROWS " . $this->pluckString($this->dtsFields, 'db') . "
                 FROM {$this->getTableName(true)} t
                 $where";

            $sql = "$selectSql $order $limit";
        }
        $data = ModelHelper::objectsToArray( call_user_func(array($this->model(), "find_by_sql"), $sql) );

        $resFilterLength = ModelHelper::objectsToArray( call_user_func(array($this->model(), "find_by_sql"), "SELECT COUNT(*) AS found_rows FROM ($selectSql) countTable") );
        $recordsFiltered    = $resFilterLength[0]['found_rows'];

        $resTotalLengthSql = "SELECT COUNT(t.{$this->getTableNameQuote()}{$this->primaryKey}{$this->getTableNameQuote()}) AS count
			 FROM {$this->getTableName(true)} t";
        $resTotalLength = ModelHelper::objectsToArray( call_user_func(array($this->model(), "find_by_sql"), $resTotalLengthSql) );
        $recordsTotal = $resTotalLength[0]['count'];

        return array(
            "draw"            => intval( $request['draw'] ),
            "recordsTotal"    => intval( $recordsTotal ),
            "recordsFiltered" => intval( $recordsFiltered ),
            "data"            => $this->dataOutput( $this->dtsFields, $data )
        );
    }

    protected function setupAdditionalAssigns($instance) {
        return array();
    }

    public function index() {

        if($this->dataTableServerSide){
            $columnDefs = $this->getColumnsDef();
            $this->setFields( $this->dataTableServerSideFields() );
        }
        else{
            $columnDefs = $this->columnDefs;
        }

        if($this->app->isAjax()){
            if($this->dataTableServerSide){
                return json_encode($this->listDataServerSide());
            }
            else {
                return json_encode(array('aaData' => $this->listData()));
            }
        }
        else{
            return $this->render($this->indexTpl, array(
                'dataTableServerSide'   => $this->dataTableServerSide,
                'reorderPath'           => $this->reorderPath,
                'isSortable'            => $this->setupSortable,
                'dragField'             => $this->dragField,
                'indexPath'             => $this->indexPath,
                'addPath'               => $this->addPath,
                'columns'               => $this->columns,
                'thAttributes'          => $this->thAttributes,
                'columnDefs'            => $columnDefs,
                'instanceName'          => $this->instanceName,
                'title'                 => $this->title
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

    public function __construct()
    {
        if ($this->setupSortable) {
            $this->setupSortability();
        }
    }

    protected function getTableNameQuote(){
        switch($this->app->config("dbConfig")["type"]){
            case "pgsql": return '"';
            default: return "`";
        }
    }
    /**
     * @param Boolean $useQuote
     * @return mixed
     */
    public function getTableName($useQuote = false){
        return ($useQuote ? $this->getTableNameQuote() : "") . call_user_func(array($this->model(), "table_name")) . ($useQuote ? $this->getTableNameQuote() : "");
    }

    protected function setupSortability()
    {
        // Adding 'position' field into columns.
        array_unshift($this->columns, 'position');

        // Setting ID column's index.
        $this->sortableIdColumnIndex = count($this->columns);

        // Addition on $thAttributes variable to sort ascendingly on position column.
        array_unshift($this->thAttributes, 'class="sort_asc"');

        // Make all columns unsortable and hide the position column.
        $columnDefs_r = json_decode($this->columnDefs, true);
        $sortableAdded = false;
        $visibleAdded = false;
        foreach ($columnDefs_r as $index => $column) {
            // todo: What if there is a bSortable = true and bVisible = true rules??
            // todo: To fix this, USE TDD!

            // When bSortable = false definition exists
            if ($column['bSortable'] === false) {
                $allIndexes = array();
                foreach($this->columns as $key => $value) {
                    $allIndexes[] = $key;
                }
                $columnDefs_r[$index]['aTargets'] = $allIndexes;
                $sortableAdded = true;
            }
            // When bVisible = false definition exists
            if ($column['bVisible'] === false) {
                $allIndexes = array();
                // Push indexes by one number.
                foreach($column['aTargets'] as $targetIndex) {
                    $allIndexes[] = $targetIndex+1;
                }
                $columnDefs_r[$index]['aTargets'] = $allIndexes;
                $sortableAdded = true;
            }
        }
        // If sortable / visible not added, create them.
        if (!$sortableAdded) {
            $allIndexes = array();
            foreach($this->columns as $key => $value) {
                $allIndexes[] = $key;
            }
            $columnDefs_r[] = array('bSortable' => false, 'aTargets' => $allIndexes);
        }
        if (!$visibleAdded) {
            $columnDefs_r[] = array('bVisible' => false, 'aTargets' => array(0));
        }
        $this->columnDefs = json_encode($columnDefs_r);
    }

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
            return $this->render($this->deleteAjaxTpl, $assigns);
        }
        else {
            return $this->render($this->deleteNoAjaxTpl, $assigns);
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
        if (is_null($instance)) {
            $errorMessage = $this->app->trans('instanceNotFound', array('model' => $this->title));
            if ($this->app->isAjax()) {
                return $this->displayErrors($errorMessage, $this->deleteAjaxTpl, $this->model());
            }
            else {
                return $this->displayErrors($errorMessage, $this->deleteNoAjaxTpl, $this->model());
            }
        }
        if (empty($instance->errors) || $instance->errors->is_empty()) {
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
            if (!empty($this->request->get($this->instanceName)) && !empty($this->request->get($this->instanceName)[$this->primaryKey])) {
                $id = $this->request->get($this->instanceName)[$this->primaryKey];
            }
        }
        else {
            if (!empty($this->request->get($this->primaryKey))) {
                $id = $this->request->get($this->primaryKey);
            }
        }
        return $id;
    }

    protected function findInstance($post = false) {
        $id = $this->decideIdSource($post);

        $instance = null;
        if (!empty($id)) {
            $instance = call_user_func(array($this->model(), "find_by_" . $this->primaryKey), $id);
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

    public function reorder() {
        // Reorder positions first to fix broken data
        $dragField = $this->getTableNameQuote() . $this->dragField . $this->getTableNameQuote();
        call_user_func(array($this->model(), "query"), "SET @ordering = 0");
        $sql = "UPDATE {$this->getTableName()} SET
		    {$dragField} = (@ordering := @ordering + 1)
		    ORDER BY {$dragField}, id ASC";
        call_user_func(array($this->model(), "query"), $sql);

        $toPosition = $this->request->get('toPosition');
        $fromPosition = $this->request->get('fromPosition');
        $direction = $this->request->get('direction');
        $id = $this->request->get('id');
        /** @var \FullSilex\Models\BaseModel $instance */
        $instance = call_user_func(array($this->model(), "find_by_id"), $id);
        if (!empty($instance)) {
            if ($direction == 'back') {
                // Adds all rows after this one's final position by 1
                $sql =  "UPDATE {$this->getTableName()}
              			 SET {$dragField} = {$dragField} + 1
              			 WHERE {$dragField} >= '".$toPosition."'
              			 AND {$dragField} < '{$fromPosition}'";
            }
            else {
                // Reduce all rows before this one's final position by 1
                $sql =  "UPDATE {$this->getTableName()}
              			 SET {$dragField} = {$dragField} - 1
              			 WHERE {$dragField} > '".$fromPosition."'
              			 AND {$dragField} <= '{$toPosition}'";
            }
            call_user_func(array($this->model(), "query"), $sql);
            $field = $this->dragField;
            $instance->$field = $toPosition;
            $instance->save();
            if(!$instance->errors->is_empty()) {
                $this->app->log("error happened when updating from CRUDController (model ".$this->model()."): " . $instance->errors->full_messages());
                $this->app->log("params are: " . print_r($this->request->attributes, true));
            }
        }

        return "";
    }


    //Data Tables Server Side Functions
    /**
     * You don't need to override this function. You can add your custom column defs at the $columnDefs attributes above.
     * @return string
     */
    protected function getColumnsDef( ) {
        $columnDefs = array();
        if( $this->setupSortable ) {
            $columnDefs[] = array(
                "targets" => array(0, 1),
                "visible" => false,
                "searchable" => false
            );
        }
        else {
            $columnDefs[] = array(
                "targets" => array(0),
                "visible" => false,
                "searchable" => false
            );
        }

        return json_encode(array_merge($columnDefs, json_decode($this->columnDefs, true)));
    }

    protected function setFields( $array ) {
        //set columns
        $this->columns   = $this->setupSortable ? array($this->dragField) : array();
        $this->columns[] = 'Id';

        //set fields
        $this->dtsFields    = $this->setupSortable ? array(array('db' => $this->dragField, 'dt' => 0, 'prefix' => 't')) : array();
        $this->dtsFields[]  = array(
            'db'        => $this->primaryKey,
            'dt'        => $this->setupSortable ? 1 : 0,
            'prefix'    => 't'
        );
        foreach($array as $key => $value) {
            //set columns
            $this->columns[] = isset( $value['column'] ) ? $value['column'] : $value['field'];

            //set fields
            $this->dtsFields[]['db']                                        = $value['field'];
            $this->dtsFields[count( $this->dtsFields ) - 1]['dt']           = $this->setupSortable ? $key + 2 : $key + 1;
            $this->dtsFields[count( $this->dtsFields ) - 1]['prefix']       = isset( $value['prefix'] ) ? $value['prefix'] : 't';
            $this->dtsFields[count( $this->dtsFields ) - 1]['rawSql']       = isset( $value['rawSql'] ) ? $value['rawSql'] : null;
            $this->dtsFields[count( $this->dtsFields ) - 1]['formatter']    = isset( $value['formatter'] ) ? $value['formatter'] : null;
            $this->dtsFields[count( $this->dtsFields ) - 1]['filter']       = isset( $value['filter'] ) ? $value['filter'] : "like";
        }
    }

    protected function limit( $request, $columns ) {
        $limit = '';

        if( isset( $request['start'] ) && $request['length'] != -1 ) {
            switch($this->app->config("dbConfig")["type"]){
                case "pgsql" :
                    $limit = "LIMIT " . intval( $request['length'] ) . " OFFSET " . intval( $request['start'] );
                    break;
                default :
                    $limit = "LIMIT " . intval( $request['start'] ) . ", " . intval( $request['length'] );
            }

        }

        return $limit;
    }

    protected function order( $request, $columns ) {
        $order = '';

        if( isset( $request['order'] ) && count( $request['order'] ) ) {
            $orderBy    = array();
            $dtColumns  = $this->pluck( $columns, 'dt' );

            for( $i = 0, $ien = count( $request['order'] ); $i < $ien; $i++ ) {
                // Convert the column index into the column data property
                $columnIdx      = intval( $request['order'][$i]['column'] );
                $requestColumn  = $request['columns'][$columnIdx];

                $columnIdx      = array_search( $requestColumn['data'], $dtColumns );
                $column         = $columns[$columnIdx];

                if( $requestColumn['orderable'] == 'true' ) {
                    $dir = $request['order'][$i]['dir'] === 'asc' ?
                        'ASC' :
                        'DESC';

                    if( $this->checkUnionSqlForHaving( $this->dtsFields ) ) {
                        $orderBy[] = $column['db'] . ' ' . $dir;
                    }
                    else {
                        $orderBy[] = $column['prefix'] . '.' . $this->getTableNameQuote() . $column['db'] . $this->getTableNameQuote() . $dir;
                    }
                }
            }

            if(!empty($orderBy)) {
                $order = 'ORDER BY ' . implode( ', ', $orderBy );
            }
        }

        return $order;
    }

    protected function pluck( $a, $prop ) {
        $out = array();

        for( $i = 0, $len = count($a); $i < $len; $i++ ) {
            $out[] = $a[$i][$prop];
        }

        return $out;
    }

    protected function pluckString( $a, $prop ) {
        $out = array();

        for( $i = 0, $len = count($a); $i < $len; $i++ ) {
            if( isset( $a[$i]['rawSql'] ) && !empty($a[$i]['rawSql']) ) {
                $out[] = $a[$i]['rawSql'] . ' AS ' . $a[$i][$prop];
            }
            else {
                $out[] = $a[$i]['prefix'] . '.' . $this->getTableNameQuote() . $a[$i][$prop] . $this->getTableNameQuote();
            }
        }

        return implode( ',', $out );
    }

    protected function filter( $request, $columns ) {
        $globalSearch   = array();
        $columnSearch   = array();
        $dtColumns      = $this->pluck( $columns, 'dt' );

        if( isset($request['search']) && $request['search']['value'] != '' ) {
            $str = $request['search']['value'];

            for( $i = 0, $ien = count($request['columns']); $i < $ien; $i++ ) {
                $requestColumn  = $request['columns'][$i];
                $columnIdx      = array_search( $requestColumn['data'], $dtColumns );
                $column         = $columns[$columnIdx];

                if( $requestColumn['searchable'] == 'true' &&  empty( $column['rawSql'] ) ) {
                    switch(strtolower($column['filter'])){
                        case "equals" :
                            $globalSearch[] = $column['prefix'] . ".{$this->getTableNameQuote()}" . $column['db'] . "{$this->getTableNameQuote()} = " . '\'' . $str . '\'';
                            break;
                        case "between" :
                            $arr = explode(";", $str);
                            if (count($arr) == 2) {
                                $globalSearch[] = $column['prefix'] . ".{$this->getTableNameQuote()}" . $column['db'] . "{$this->getTableNameQuote()} BETWEEN " . '\'' . $arr[0] . '\' AND \'' . $arr[1] . '\'';
                            }
                            break;
                        case "like" :
                        default :
                            $globalSearch[] = $column['prefix'] . ".{$this->getTableNameQuote()}" . $column['db'] . "{$this->getTableNameQuote()} LIKE " . '\'%' . $str . '%\'';
                            break;
                    }
                }
            }
        }

        // Individual column filtering
        for( $i = 0, $ien = count( $request['columns'] ); $i < $ien; $i++ ) {
            $requestColumn  = $request['columns'][$i];
            $columnIdx      = array_search( $requestColumn['data'], $dtColumns );
            $column         = $columns[$columnIdx];

            $str            = $requestColumn['search']['value'];

            if( $requestColumn['searchable'] == 'true' && $str != '' &&  empty( $column['rawSql'] ) ) {
                switch(strtolower($column['filter'])){
                    case "equals" :
                        $columnSearch[] = $column['prefix'] . ".{$this->getTableNameQuote()}" . $column['db'] . "{$this->getTableNameQuote()} = " . '\'' . $str . '\'';
                        break;
                    case "between" :
                        $arr = explode(";", $str);
                        if (count($arr) == 2) {
                            $columnSearch[] = $column['prefix'] . ".{$this->getTableNameQuote()}" . $column['db'] . "{$this->getTableNameQuote()} BETWEEN " . '\'' . $arr[0] . '\' AND \'' . $arr[1] . '\'';
                        }
                        break;
                    case "like" :
                    default :
                        $columnSearch[] = $column['prefix'] . ".{$this->getTableNameQuote()}" . $column['db'] . "{$this->getTableNameQuote()} LIKE " . '\'%' . $str . '%\'';
                        break;
                }
            }
        }

        // Combine the filters into a single string
        $where = '';

        if( count( $globalSearch ) ) {
            $where = '(' . implode( ' OR ', $globalSearch ) . ')';
        }

        if( count( $columnSearch ) ) {
            $where = $where === '' ?
                implode( ' AND ', $columnSearch ) :
                $where .' AND '. implode( ' AND ', $columnSearch );
        }

        if( $where !== '' ) {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }

    protected function filterHaving( $request, $columns ) {
        $globalSearch   = array();
        $columnSearch   = array();
        $dtColumns      = $this->pluck( $columns, 'dt' );

        if( isset($request['search']) && $request['search']['value'] != '' ) {
            $str = $request['search']['value'];

            for( $i = 0, $ien = count($request['columns']); $i < $ien; $i++ ) {
                $requestColumn  = $request['columns'][$i];
                $columnIdx      = array_search( $requestColumn['data'], $dtColumns );
                $column         = $columns[$columnIdx];

                if( $requestColumn['searchable'] == 'true' &&  !empty( $column['rawSql'] ) ) {
                    switch(strtolower($column['filter'])){
                        case "equals" :
                            $globalSearch[] = $column['db'] . " = " . '\'' . $str . '\'';
                            break;
                        case "between" :
                            $arr = explode(";", $str);
                            if (count($arr) == 2) {
                                $globalSearch[] = $column['db'] . " BETWEEN " . '\'' . $arr[0] . '\' AND \'' . $arr[1] . '\'';
                            }
                            break;
                        case "like" :
                        default :
                            $globalSearch[] = $column['db'] . " LIKE " . '\'%' . $str . '%\'';
                            break;
                    }
                }
            }
        }

        // Individual column filtering
        for( $i = 0, $ien = count( $request['columns'] ); $i < $ien; $i++ ) {
            $requestColumn  = $request['columns'][$i];
            $columnIdx      = array_search( $requestColumn['data'], $dtColumns );
            $column         = $columns[$columnIdx];

            $str            = $requestColumn['search']['value'];

            if( $requestColumn['searchable'] == 'true' && $str != '' &&  !empty( $column['rawSql'] ) ) {
                switch(strtolower($column['filter'])){
                    case "equals" :
                        $columnSearch[] = $column['db'] . " = " . '\'' . $str . '\'';
                        break;
                    case "between" :
                        $arr = explode(";", $str);
                        if (count($arr) == 2) {
                            $columnSearch[] = $column['db'] . " BETWEEN " . '\'' . $arr[0] . '\' AND \'' . $arr[1] . '\'';
                        }
                        break;
                    case "like" :
                    default :
                        $columnSearch[] = $column['db'] . " LIKE " . '\'%' . $str . '%\'';
                        break;
                }
            }
        }

        $having = '';

        if( count( $globalSearch ) ) {
            $having = '(' . implode( ' OR ', $globalSearch ) . ')';
        }

        if( count( $columnSearch ) ) {
            $having = $having === '' ?
                implode( ' AND ', $columnSearch ) :
                $having .' AND '. implode( ' AND ', $columnSearch );
        }

        if( $having !== '' ) {
            $having = 'HAVING ' . $having;
        }

        return $having;
    }

    protected function dataOutput( $columns, $data ) {
        $out = array();

        for( $i = 0, $ien = count($data) ; $i < $ien ; $i++ ) {
            $row        = array();

            $max_row    = -1;
            for( $j = 0, $jen = count($columns) ; $j < $jen ; $j++ ) {
                $column = $columns[$j];

                // Is there a formatter?
                if( isset( $column['formatter'] ) ) {
                    $row[ $column['dt'] ] = $column['formatter']( $data[$i][ $column['db'] ], $data[$i] );
                }
                else {
                    $row[ $column['dt'] ] = $data[$i][ $columns[$j]['db'] ];
                }

                if( $max_row < $column['dt'] ) { $max_row = $column['dt']; }
            }

            $row['DT_RowAttr']['data-id'] = $data[$i][$this->primaryKey];
            if( isset( $data[$i][$this->dragField] ) ) {
                $row['DT_RowAttr']['data-position'] = $data[$i][$this->dragField];
            }

            $out[] = $row;
        }

        return $out;
    }

    protected function checkUnionSqlForHaving( $a ) {
        $flag = false;

        for( $i = 0, $len = count($a); $i < $len; $i++ ) {
            if( isset( $a[$i]['rawSql'] ) ) {
                $flag = true;
                break;
            }
        }

        return $flag;
    }

    public function reorderServerSide() {
        if($this->app->isAjax()) {
            $oldPosition    = $_POST['oldPosition'];
            $newPosition    = $_POST['newPosition'];
            $id             = $_POST['id'];

            if($oldPosition > $newPosition) {
                call_user_func(array($this->model(), "update_all"), array(
                    "set" => ' ' . $this->getTableNameQuote() . $this->dragField . $this->getTableNameQuote() . '=' . $this->getTableNameQuote() . $this->dragField . $this->getTableNameQuote() . ' + 1 ',
                    "conditions" => array($this->getTableNameQuote() . $this->dragField . $this->getTableNameQuote() . ' < ? AND ' . $this->getTableNameQuote() . $this->dragField . $this->getTableNameQuote() . ' >= ?', $oldPosition, $newPosition)
                ));
            } else if ($oldPosition < $newPosition) {
                call_user_func(array($this->model(), "update_all"), array(
                    "set" => ' ' . $this->getTableNameQuote() . $this->dragField . $this->getTableNameQuote() . '=' . $this->getTableNameQuote() . $this->dragField . $this->getTableNameQuote() . ' - 1 ',
                    "conditions" => array($this->getTableNameQuote() . $this->dragField . $this->getTableNameQuote() . ' > ? AND ' . $this->getTableNameQuote() . $this->dragField . $this->getTableNameQuote() . ' <= ?', $oldPosition, $newPosition)
                ));
            }

            call_user_func(array($this->model(), "update_all"), array(
                "set" => array($this->dragField => $newPosition),
                "conditions" => array($this->getTableNameQuote() . $this->primaryKey . $this->getTableNameQuote() . ' = ?', $id)
            ));
        }
        return "";
    }
}