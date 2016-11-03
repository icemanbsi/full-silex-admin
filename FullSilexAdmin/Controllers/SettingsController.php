<?php
/**
 * Created by Bobby Stenly Irawan (http://bobbystenly.com)
 * Date: 10/24/16
 * Time: 10:31 AM
 */

namespace FullSilexAdmin\Controllers;

use FullSilex\Helpers\ModelHelper;

class SettingsController extends CRUDController
{
    protected $instanceName = 'setting'; // Instance name used in parameter prefix i.e. 'instance' of $this->params['instance']['attributeName']
    protected $title = 'setting'; // Page Title

    // Form tpl files
    protected $addFormTpl = '_form';
    protected $editFormTpl = '_form';
    protected $deleteFormTpl = '/admin/widgets/crud/delete/_deleteForm';
    protected $indexTpl = '_index';

    // For redirect when success / error happens
    protected $indexPath = array('route' => 'admin/settings', 'method' => 'index');
    protected $addPath = array('route' => 'admin/settings', 'method' => 'add');
    protected $editPath = array('route' => 'admin/settings', 'method' => 'edit');
    protected $deletePath = array('route' => 'admin/settings', 'method' => 'delete');

    protected $successTarget = 'edit'; // index or edit, where to redirect after success

    // If you don't want to create deleteForm.tpl. define this instead.
    // Sample value: instances/destroy
    protected $destroyPath = null;

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

    public $columns = array('Name', 'Value', '', 'position');
    public $thAttributes = array('', '', '', 'class="sort_asc"', ''); // Class sort_asc or sort_desc can be used to set default sorting.
    public $columnDefs = "[{'bVisible': false, aTargets:[3]}, {'bSortable' : false, 'aTargets':[2]}]"; // Use this to handle columns' behaviours, doc: http://www.datatables.net/usage/columns

    /**
     * Override this with model linked with this controller.
     * Use lowercase.
     */
    protected function model() {
        return 'App\Models\Setting';
    }

    /**
     * Data used in index listing.
     * @return array
     */
    protected function listData() {
        $sql = "SELECT * FROM " . call_user_func(array($this->model(), "table_name")) . " WHERE is_visible=? ORDER BY position";
        $instances = ModelHelper::objectsToArray( call_user_func(array($this->model(), "find_by_sql"), $sql, array(true)) );
        $instanceRows = array();
        if (!empty($instances)) {
            foreach ($instances as $instanceArray) {
                if($instanceArray["type"] == "password"){
                    $value = "********";
                }
                else{
                    $value = $instanceArray["value"];
                    if(strlen($value) > 40){
                        $value = substr($value, 0, strrpos( substr($value, 0, 41), " ") ) . "...";
                    }
                }
                $instanceRow = array(
                    // List your field names here
                    $instanceArray["name"],
                    $value,
                    $this->listActions($instanceArray),
                    $instanceArray["position"]
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

    protected function listActions($instanceArray)
    {
        $actions = '<div class="text-right">
                    <a title="Edit" href="'.$this->app->url($this->editPath["route"], array('method' => $this->editPath['method'], 'id' => $instanceArray['id'])).'" data-toggle="dialog"><span class="fa fa-pencil"></span></a>
					</div>';
        if (!is_null($this->dragField)) {
            $actions .='<input type="hidden" class="id" value="'.$instanceArray['id'].'"/>
					<input type="hidden" class="'.$this->dragField.'" value="'.$instanceArray[$this->dragField].'"/>';
        }
        return $actions;
    }
}