<?php

namespace FullSilexAdmin\Controllers\ImageUploader;


use FullSilex\Helpers\TextHelper;

trait ImageUploaderSetting
{
    use ImageUploader;
// IMPORTANT: Add the following to your Controller:
//    protected $imageMovePath    = array("route" => 'admin/controllerName', "method" => 'moveImage');
//    protected $imageNewRowPath  = array("route" => 'admin/controllerName', "method" => 'newRow');
//    protected $imageUploadPath  = array("route" => 'admin/controllerName', "method" => 'uploadImage');
//    protected $imageDeletePath  = array("route" => 'admin/controllerName', "method" => 'deleteImage');
//    protected $imageDestroyPath = array("route" => 'admin/controllerName', "method" => 'destroyImage');

    protected function getImageSettings()
    {
        return array(
            'multiple_types_setting_name' => array(
                '_config' => array(
                    'multiple' => false,
                    'adminName' => 'Name in Admin'
                ),
                'types' => array(
                    'smartphone' => array(
                        'w' => 300,
                        'maxOnly' => true
                    ),
                    'desktop' => array(
                        'w' => 600,
                        'maxOnly' => true
                    )
                )
            ),
            'one_type_image_field' => array(
                '_config' => array(
                    'multiple' => true,
                    'adminName' => "Main Images"
                ),
                'options' => array(
                    'description' => 'max 768px x 273px',
                    'w' => 768,
                    'h' => 273,
                    'scale' => true,
                    'maxOnly' => true
                )
            )
        );
    }

    protected function model() {
        return 'App\Models\Setting';
    }

    protected function setupInstanceImageAssigns() {
        $images = array();
        $instances = array();
        $imageSettings = $this->getImageSettings();
        if (!empty($imageSettings)) {
            foreach($imageSettings as $key => $imageSetting) {
                $setting = call_user_func(array($this->model(), "find_by_name"), $key);
                if (TextHelper::isJson($setting->value)) {
                    $images[$key] = json_decode($setting->value, true);
                }
                else {
                    $images[$key] = $setting->value;
                }
                $instances[$key] = $setting->to_array();
            }
        }
        return array('instanceImages' => $images, 'instances' => $instances, 'isSettingModel' => true);
    }

    public function images()
    {
        $assigns = array();
        $className = $this->model();
        if (!method_exists(new $className(), 'imageBaseUrl')) {
            $assigns = array('error' => 'WARNING: Model ' . ucfirst($this->model()) . ' needs HasImages trait, otherwise image won\'t upload!');
        }
        $imageSettings = $this->getImageSettings();
        $assigns = array_merge(
            $assigns,
            $this->setupInstanceImageAssigns(),
            $this->setPaths(),
            array(
                '_imageSettings' => $imageSettings,
                'indexContent' => $this->fetch('/admin/widgets/imageUploader/_index', array('_imageSettings' => $imageSettings))
            )
        );
        return $this->render('images', $assigns);
    }

    /**
     * 	When uploading images, use following parameters:
     *  $_FILES = array(
     *      array(
     *          file-(n): array(
     *              name: string,
     *              size: int
     *          ), . . .
     *      )
     *  )
     *
     *  Just upload the image to images/[instance_id] directory then pass back image url.
     *  You may also send "data" parameter which will be passed back (maybe needed for reference or anything).
     **/

    public function uploadImage()
    {
        $error = '';
        $uploadedImages = array();

        if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST")
        {
            // Find instance
            $instance = call_user_func(array($this->model(), "find_by_name"), $this->request->get('settingName'));
            try {
                $uploadedImages = $this->processUploadedImage($instance, 'value');
            }
            catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        if (!empty($error)) {
            return json_encode(array('error' => $error));
        }
        else{
            return json_encode($uploadedImages);
        }
    }

    public function deleteImage()
    {
        // Find instance
        $instance = call_user_func(array($this->model(), "find_by_name"), $this->request->get('setting'));
        return $this->processDeleteImage($instance, array('instanceName' => 'setting', 'setting' => $instance->export(true), 'isSettingModel' => true));
    }

    public function destroyImage()
    {
        // Find instance
        $instance = call_user_func(array($this->model(), "find_by_name"), $this->request->get('setting'));

        //$assigns = array('instanceName' => 'setting', 'setting' => $instance->export(true), 'isSettingModel' => true);
        return $this->processDestroyImage($instance, $this->request->get('setting'), $this->request->get('field'), $this->request->get('position'));
    }

    protected function getImagesValue($instance, $settingName){
        return $instance->value;
    }
}