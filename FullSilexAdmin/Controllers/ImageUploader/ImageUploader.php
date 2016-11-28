<?php

namespace FullSilexAdmin\Controllers\ImageUploader;

use FullSilex\Helpers\UtilitiesHelper;
use FullSilex\Libraries\ImageProcessor\ImageProcessor;

trait ImageUploader
{
// IMPORTANT: Add the following to your Controller:
//    protected $imageMovePath    = array("route" => 'admin/controllerName', "method" => 'moveImage');
//    protected $imageNewRowPath  = array("route" => 'admin/controllerName', "method" => 'newRow');
//    protected $imageUploadPath  = array("route" => 'admin/controllerName', "method" => 'uploadImage');
//    protected $imageDeletePath  = array("route" => 'admin/controllerName', "method" => 'deleteImage');
//    protected $imageDestroyPath = array("route" => 'admin/controllerName', "method" => 'destroyImage');

    protected function setupInstanceImageAssigns($instance) {
        return array();
    }

    protected function multipleManyTypesForm()
    {
        return 'admin/widgets/imageUploader/types/_multipleManyTypes.twig';
    }

    protected function multipleOneTypeForm()
    {
        return 'admin/widgets/imageUploader/types/_multipleOneType.twig';
    }

    protected function singleManyTypesForm()
    {
        return 'admin/widgets/imageUploader/types/_singleManyTypes.twig';
    }

    protected function singleOneTypeForm()
    {
        return 'admin/widgets/imageUploader/types/_singleOneType.twig';
    }

    protected function getImageSettings()
    {
        return array(
            ''
        );
    }

    protected function processTempImage($tmp, $options, $oldFile = '')
    {
        $tmp = str_replace('/', DIRECTORY_SEPARATOR, $tmp);
        try {
            $options = array_merge(array(
                'onlyCreateWhenNew' => false,
                'overwrite' => false
            ), $options);
            /** delete previous images */
            if (!empty($oldFile) && $oldFile != '/' && $oldFile != $this->app->getPublicBasePath() && file_exists($oldFile)) {
                unlink($oldFile);
            }
            $path = ImageProcessor::resize($tmp, $options);
        }
        catch (\Exception $e) {
            $this->app->log("error with message " . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
        $path = str_replace(array($this->app->getPublicBasePath(), DIRECTORY_SEPARATOR), array('', '/'), $path);
        return $path;
    }


    /**
     * @param $instance
     * @param string $imageFieldName Name of image field to lookup for. In
     *        setting this is 'value', but in CRUD this should be name of
     *        image field.
     * @throws \Exception
     * @return null|string
     */
    protected function processUploadedImage($instance, $imageFieldName = 'value')
    {
        $uploadOnce = UtilitiesHelper::toBoolean($this->request->get('uploadOnce',0));
        $uploadedImages = array();
        $error = null;
        $type = $this->request->get('type', '');
        $settingName = $this->request->get('settingName');
        $validFormats = array("jpg", "png", "gif", "bmp","jpeg");
        $imageSettings = $this->getImageSettings();
        $imageSetting = $imageSettings[$settingName];
//        $this->app->log("files uploaded: " . print_r($_FILES, true));
        if (empty($error) && !empty($instance) && !empty($imageSetting)) {
            $nFiles = count($_FILES);
            for($i=0; $i<$nFiles; $i++) {
                $name = $_FILES['file-'.$i]['name'];
                $size = $_FILES['file-'.$i]['size'];
                if(strlen($name))
                {
                    $filename_r = explode(".", $name);
                    $ext = strtolower($filename_r[count($filename_r)-1]);
                    unset($filename_r[count($filename_r)-1]);
                    $imageName = implode($filename_r);
                    if(in_array($ext,$validFormats))
                    {
                        $maxFilesize = (int)(ini_get('upload_max_filesize')) * 1024 * 1024;
                        if($size<($maxFilesize))
                        {
                            $tmp = $_FILES['file-'.$i]['tmp_name'];

                            $filePath = $instance->imageBaseUrl();
                            $fullPath = $this->app->getPublicBasePath().$filePath;

                            if (!file_exists($fullPath)) {
                                mkdir($fullPath,0775, true);
                            }

                            // Create the file

                            $options = array(
                                'resultDir' => $fullPath,
                                'noImagick' => $this->app->config('noImagick'),
                                'imagickProgressive' => $this->app->config('imagickProgressive'),
                                'outputFilename' => str_replace(" ", "-", $name)
                            );
                            $instanceImages = $instance->$imageFieldName;
                            if (is_string($instanceImages) && (isset($imageSetting["types"]) || $imageSetting["_config"]["multiple"])) {
                                $instanceImages = json_decode($instanceImages, true);
                            }

                            if ($uploadOnce) {
                                // Upload once can only be used by multiple images.
                                if (isset($imageSetting['types'])) {
                                    // Many types
                                    $newInstanceImage = array();
                                    foreach($imageSetting['types'] as $key => $typeOptions) {
                                        $options = array_merge($options, $imageSetting['types'][$key]);
                                        $options['outputFilename'] = str_replace(" ", "-", $imageName) .'-'.$key.'.'.$ext;
                                        try {
                                            $path = $this->processTempImage($tmp, $options);
                                            $newInstanceImage[$key] = $path;
                                            $uploadedImages[] = array(
                                                'data' => $this->request->get('data'),
                                                'path' => $newInstanceImage[$key],
                                                'message' => $this->app->trans("imageUploaded")
                                            );
                                        }
                                        catch (\Exception $e) {
                                            $uploadedImages[] = array(
                                                'data' => $this->request->get('data'),
                                                'path' => $filePath.$imageName.'.'.$ext,
                                                'message' => $e->getMessage(),
                                                'error' => true
                                            );
                                        }
                                    }
                                    $instanceImages[] = $newInstanceImage;
                                    $instance->$imageFieldName = json_encode($instanceImages);
                                }
                                else {
                                    // Single type
                                }
                            }
                            else {
                                // Upload one image from "change" button.
                                if (isset($imageSetting['types'])) {
                                    // Many types
                                    if ($imageSetting['_config']['multiple']) {
                                        // multiple images
                                        $position = $this->request->get("position");
                                        $options = array_merge($options, $imageSetting['types'][$type]);

                                        $oldFile = $this->app->getPublicBasePath().$instanceImages[$position][$type];
                                        try {
                                            $path = $this->processTempImage($tmp, $options, $oldFile);
                                            $instanceImages[$position][$type] = $path;
                                            $instance->$imageFieldName = json_encode($instanceImages);
                                            $uploadedImages[] = array(
                                                'data' => $this->request->get('data'),
                                                'path' => $instanceImages[$position][$type],
                                                'message' => $this->app->trans("imageUploaded")
                                            );
                                        }
                                        catch (\Exception $e) {
                                            $uploadedImages[] = array(
                                                'data' => $this->request->get('data'),
                                                'path' => $filePath.$imageName.'.'.$ext,
                                                'message' => $e->getMessage(),
                                                'error' => true
                                            );
                                        }
                                    }
                                    else {

                                        // single image
                                        if (empty($imageSetting['types'][$type])) {
                                            throw new \Exception("Type $type not found");
                                        }
                                        if (empty($instanceImages)) {
                                            $instanceImages = array();
                                        }
                                        $options = array_merge($options, $imageSetting['types'][$type]);
                                        $oldFile = $this->app->getPublicBasePath().$instanceImages[$type];
                                        try {
                                            $path = $this->processTempImage($tmp, $options, $oldFile);
                                            $instanceImages[$type] = $path;
                                            $instance->$imageFieldName = json_encode($instanceImages);
                                            $uploadedImages[] = array(
                                                'data' => $this->request->get('data'),
                                                'path' => $instanceImages[$type],
                                                'message' => $this->app->trans("imageUploaded")
                                            );
                                        }
                                        catch (\Exception $e) {
                                            throw new \Exception($e->getMessage());
                                        }
                                    }
                                }
                                else {
                                    // One type
                                    $options = array_merge($options, $imageSetting['options']);
                                    if($imageSetting['_config']['multiple']){
                                        //MULTIPLE
                                        $position = $this->request->get("position");
                                        $oldFile = $this->app->getPublicBasePath().$instanceImages[$position];
                                        try {
                                            $path = $this->processTempImage($tmp, $options, $oldFile);
                                            $instanceImages[$position] = $path;
                                            $instance->$imageFieldName = json_encode($instanceImages);
                                            $uploadedImages[] = array(
                                                'data' => $this->request->get('data'),
                                                'path' => $instanceImages[$position],
                                                'message' => $this->app->trans("imageUploaded")
                                            );
                                        }
                                        catch (\Exception $e) {
                                            throw new \Exception($e->getMessage());
                                        }
                                    }
                                    else{
                                        //SINGLE
                                        $oldFile = $this->app->getPublicBasePath().$instanceImages;
                                        try {
                                            $path = $this->processTempImage($tmp, $options, $oldFile);
                                            $instanceImages = $path;
                                            $instance->$imageFieldName = $instanceImages;
                                            $uploadedImages[] = array(
                                                'data' => $this->request->get('data'),
                                                'path' => $instanceImages,
                                                'message' => $this->app->trans("imageUploaded")
                                            );
                                        }
                                        catch (\Exception $e) {
                                            throw new \Exception($e->getMessage());
                                        }
                                    }
                                }
                            }

                            $instance->save();
                        }
                        else {
                            throw new \Exception($this->app->trans('errorFilesizeTooBig', array("fileSize" => FileHelper::parseBytes($maxFilesize))));
                        }
                    }
                    else {
                        throw new \Exception($this->app->trans("errorInvalidFormat", array("ext" => implode(", ", $validFormats))));
                    }
                }
                else {
                    throw new \Exception($this->app->trans("errorFileEmpty"));
                }
            }
        }
        else {
            if (empty($instance)) {
                $uploadedImages = array(
                    'data' => $this->request->get('data'),
                    'path' => '',
                    'message' => $this->app->trans("errorInstanceEmpty")
                );
            }
            elseif (empty($imageSetting)) {
                $uploadedImages = array(
                    'data' => $this->request->get('data'),
                    'path' => '',
                    'message' => $this->app->trans("errorSettingEmpty")
                );
            }
        }
        return $uploadedImages;
    }

    protected function setPaths()
    {
        return array(
            'imageDestroyPath' => $this->imageDestroyPath,
            'imageDeletePath' => $this->imageDeletePath,
            'imageNewRowPath' => $this->imageNewRowPath,
            'imageUploadPath' => $this->imageUploadPath,
            'imageMovePath' => $this->imageMovePath,
            'multipleManyTypesForm' => $this->multipleManyTypesForm(),
            'multipleOneTypeForm' => $this->multipleOneTypeForm(),
            'singleManyTypesForm' => $this->singleManyTypesForm(),
            'singleOneTypeForm' => $this->singleOneTypeForm()
        );
    }

    public function processDeleteImage($instance, $additionalAssign=array()) {
        $assigns = array();
        if (is_null($instance)) {
            $assigns = array(
                'error' => $this->app->trans('instanceNotFound', array('model' => $this->model())),
                'errorAttributes' => array(),
                'instance' => $this->model()
            );
        }
        else {
            if ($this->imageDestroyPath != null) {
                $field = $this->request->get('field', '');
                $setting = $this->request->get('setting', '');
                if($setting != "" && $setting != $field && $field == "value"){
                    $field = $setting; // settingModel
                    $imagesField = $instance->value;
                }
                else{
                    $imagesField = $instance->$field;
                }

                $imageSettings = $this->getImageSettings();
                if (!is_array($imagesField)) {
                    if(!(!$imageSettings[$field]["_config"]["multiple"] && !empty($imageSettings[$field]["options"]))){
                        $imagesField = json_decode($imagesField, true);
                    }
                }
                $position = $this->request->get('position', -1);
                if((!empty($position) || (int)$position === 0) && $position > -1) $imageField = $imagesField[$position];
                else $imageField = $imagesField;

                if (is_array($imageField)) {
                    $values = array_values($imageField);
                    $image = $values[0];
                }
                else {
                    $image = $imageField;
                }
                $assigns = array_merge($assigns,
                    $this->setPaths(),
                    array(
                        'position' => $this->request->get('position'),
                        '_imageSettingName' => $this->request->get('setting'),
                        '_image' => $image,
                        'isAjax' => $this->app->isAjax()
                    ));
            }
            else {
                $assigns = array(
                    'error' => "Please set destroyPath",
                    'errorAttributes' => array(),
                    'instance' => $this->model()
                );
            }
        }

        $assigns = array_merge($assigns, $additionalAssign);
        return $this->render('/admin/widgets/imageUploader/multiple/_delete', $assigns);
    }

    public function processDestroyImage($instance, $imageSetting, $imageField, $position) {
        if (empty($instance->errors) || !$instance->errors->is_empty()) {
            try {
                $imageSettings = $this->getImageSettings();
                if($imageSettings[$imageSetting]["_config"]["multiple"]){
                    $imageValue = $instance->$imageField;
                    if (!is_array($imageValue)) {
                        $imageValue = json_decode($imageValue, true);
                    }
                    $imagesAtPosition = $imageValue[$position];
                    if (!empty($imagesAtPosition)) {
                        if(!empty($imageSettings[$imageSetting]["types"])){ //multiple many types
                            foreach($imagesAtPosition as $key => $image) {
                                unlink(str_replace('/', DIRECTORY_SEPARATOR, $this->app->getPublicBasePath() . $image));
                            }
                        }
                        else{ //multiple single types
                            unlink(str_replace('/', DIRECTORY_SEPARATOR, $this->app->getPublicBasePath() . $imagesAtPosition));
                        }
                    }
                    unset($imageValue[$position]);
                    $imageValue = array_values($imageValue);
                    $instance->$imageField = json_encode($imageValue);
                }
                else if(!$imageSettings[$imageSetting]["_config"]["multiple"] && !empty($imageSettings[$imageSetting]["types"])){
                    $imageValue = $instance->$imageField;
                    if (!is_array($imageValue)) {
                        $imageValue = json_decode($imageValue, true);
                    }
                    if(!empty($imageValue) && is_array($imageValue)){
                        foreach($imageValue as $key => $image) {
                            unlink(str_replace('/', DIRECTORY_SEPARATOR, $this->app->getPublicBasePath() . $image));
                        }
                    }
                    $instance->$imageField = "";
                }
                else if(!$imageSettings[$imageSetting]["_config"]["multiple"] && !empty($imageSettings[$imageSetting]["options"])){ //single one type
                    $imageValue = $instance->$imageField;
                    if(!empty($imageValue)){
                        unlink(str_replace('/', DIRECTORY_SEPARATOR, $this->app->getPublicBasePath() . $imageValue));
                    }
                    $instance->$imageField = "";
                }

                $instance->save();

                return json_encode(array(
                    'message' => $this->app->trans('deleted'),
                    'setting' => $imageSetting,
                    'field' => $imageField,
                    'position' => $position
                ));
            }
            catch (\Exception $e) {
                $this->app->log("Cannot delete data : " . $e->getMessage());
                return json_encode(array(
                    'error' => $this->app->trans('cannot delete image. Unknown Error Occured')
                ));
            }
        }

        return json_encode(array(
            'error' => $this->app->trans('file not found')
        ));
    }

    protected function getImagesValue($instance, $settingName){
        return $instance->$settingName;
    }

    /**
     * If pos is empty, means it creates a new row
     */
    public function newRow()
    {
        $isNew = UtilitiesHelper::toBoolean($this->request->get('new'));
        $instanceId = $this->request->get('id');
        $instance = call_user_func(array($this->model(), "find_by_" . $this->primaryKey), $instanceId);
        $imageField = $this->request->get('field');
        $imagePosition = (int)$this->request->get('pos');
        $imageSettings = $this->getImageSettings();
        $settingName = $this->request->get('setting');
        $imageSetting = $imageSettings[$settingName];
        $images = $this->getImagesValue($instance, $settingName);
        if (empty($images)) {
            $images = array();
        }
        else {
            if (!is_array($images)) {
                $images = json_decode($images, true);
            }
        }

        $assigns = array_merge(
            $this->setPaths(),
            $this->setupInstanceImageAssigns($instance),
            array(
                'instanceName'      => $this->instanceName,
                '_imageSetting'     => $imageSetting,
                '_imagePos'         => $imagePosition,
                '_imageSettingName' => $settingName,
                '_image'            => array()
            )
        );

        if (isset($imageSetting['types']) && count($imageSetting['types']) > 0) {
            $newImageRow = array();
            foreach($imageSetting['types'] as $type => $setting) {
                $newImageRow[$type] = '';
            }
            if ($isNew) {
                array_unshift($images, $newImageRow);
                $json_encoded = json_encode($images);
                $instance->$imageField = $json_encoded;
                $instance->save();
            }
            else {
                $image = $images[$imagePosition];
                $assigns = array_merge($assigns, array(
                    '_image' => $image
                ));
            }
            $assigns = array_merge($assigns, array(
                $this->instanceName => $instance->to_array()
            ));
            return $this->render('/admin/widgets/imageUploader/multiple/_manyTypes', $assigns);
        }
        else {
            $newImageRow = '';
            array_unshift($images, $newImageRow);
            $json_encoded = json_encode($images);
            $instance->$imageField = $json_encoded;
            $instance->save();
            $assigns = array_merge($assigns, array(
                $this->instanceName => $instance->to_array()
            ));
            return $this->render('/admin/widgets/imageUploader/multiple/_oneType', $assigns);
        }
    }

    public function moveImage()
    {
        $id = $this->request->get('id');
        $from = (int)$this->request->get('position');
        $direction = $this->request->get('direction');
        $settingName = $this->request->get('setting');
        $fieldName = $this->request->get('field');
        if ($direction == 'up') {
            $to = $from -1;
        }
        else {
            $to = $from +1;
        }
        $instance = call_user_func(array($this->model(), "find_by_" . $this->primaryKey), $id);
        $images = $instance->$fieldName;
        if (!is_array($images)) {
            $images = json_decode($images, true);
        }
        $imageFrom = $images[$from];
        $images[$from] = $images[$to];
        $images[$to] = $imageFrom;
        $instance->$fieldName = json_encode($images);
        $instance->save();
        return json_encode(array('settingName' => $settingName, 'from' => $from, 'to' => $to));
    }
}