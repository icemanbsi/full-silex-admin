<?php

namespace FullSilexAdmin\Controllers\ImageUploader;


use FullSilex\Helpers\TextHelper;

trait ImageUploaderCRUD
{
    use ImageUploader;

    // IMPORTANT: Add the following to your Controller:
//    protected $imageMovePath    = array("route" => 'admin/controllerName', "method" => 'moveImage');
//    protected $imageNewRowPath  = array("route" => 'admin/controllerName', "method" => 'newRow');
//    protected $imageUploadPath  = array("route" => 'admin/controllerName', "method" => 'uploadImage');
//    protected $imageDeletePath  = array("route" => 'admin/controllerName', "method" => 'deleteImage');
//    protected $imageDestroyPath = array("route" => 'admin/controllerName', "method" => 'destroyImage');

//    protected $imagesTemplatePath = "/admin/widgets/imageUploader/images";
//    protected $pageTitle = "Image Setting";

    protected function getBackUrl(){
        return $this->app->url("admin/controllerName", array("method" => "index"));
    }

    protected function getImageSettings()
    {
        return array(
            'many_types_image_field' => array(
                '_config' => array(
                    'multiple' => false,
                    'adminName' => "Name Displayed in Admin"
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
                    'multiple' => false,
                    'adminName' => "Main Image"
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


    /**
     * @param \FullSilex\Models\BaseModel $instance
     * @return Array
     */
    protected function setupInstanceImageAssigns($instance) {
        $images = array();
        $imageSettings = $this->getImageSettings();
        if (!empty($imageSettings)) {
            foreach($imageSettings as $key => $imageSetting) {
                if (TextHelper::isJson($instance->$key)) {
                    $images[$key] = json_decode($instance->$key, true);
                }
                else {
                    $images[$key] = $instance->$key;
                }
            }
        }
        return array('instanceImages' => $images, 'isSettingModel' => false);
    }

    public function images()
    {
        $instance = $this->findInstance(false);
        $assigns = array();
        if (!method_exists($instance, 'imageBaseUrl')) {
            $assigns = array('error' => 'WARNING: Model ' . ucfirst($this->model()) . ' needs HasImages trait, otherwise image won\'t upload!');
        }
        $imageSettings = $this->getImageSettings();
        $assigns = array_merge(
            $assigns,
            $this->setupInstanceImageAssigns($instance),
            $this->setupInstanceAssigns($instance),
            $this->setPaths(),
            array(
                '_imageSettings' => $imageSettings,
                'pageTitle'         => $this->pageTitle,
                'backUrl'           => $this->getBackUrl()
            )
        );
        $assigns['indexContent'] = $this->render('/admin/widgets/imageUploader/_index', $assigns);
        return $this->render($this->imagesTemplatePath, $assigns);
    }

    public function uploadImage()
    {

        $error = '';
        $uploadedImages = array();

        if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST")
        {
            // Find instance
            $id = $this->request->get($this->instanceName.'_id');
            if (!empty($id)) {
                $instance = call_user_func(array($this->model(), "find_by_" . $this->primaryKey), $id);
            }
            else {
                $instance = null;
            }

            try {
                $uploadedImages = $this->processUploadedImage($instance, $this->request->get('settingName'));
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
        $instance = $this->findInstance(false);
        return $this->processDeleteImage($instance, $this->setupAssigns($instance));
    }
    public function destroyImage()
    {
        $instance = $this->findInstance(true);

        try {
            return $this->processDestroyImage($instance, $this->request->get('setting'), $this->request->get('field'), $this->request->get('position'));
        }
        catch (\Exception $e) {
            $assigns = $this->setupAssigns($instance);
            if ($this->destroyPath != null) {
                $assigns = array_merge($assigns, array('destroyPath' => $this->destroyPath));
            }
            return $this->displayInstanceErrors($instance, $this->instanceName, 'delete');
        }
    }

}