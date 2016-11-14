<?php
/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 11/14/16
 * Time: 4:35 PM
 */

namespace FullSilexAdmin\Controllers\ImageUploader;

use FullSilex\Helpers\UtilitiesHelper;
use FullSilex\Libraries\ImageProcessor\ImageProcessor;

trait ImageUploader
{
// IMPORTANT: Add the following to your Controller:
//    protected $imageMovePath = 'admin/controllerName/moveImage';
//    protected $imageNewRowPath = 'admin/controllerName/newRow';
//    protected $imageUploadPath = 'admin/controllerName/uploadImage';
//    protected $imageDeletePath = 'admin/controllerName/deleteImage';
//    protected $imageDestroyPath = 'admin/controllerName/destroyImage';

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
            if (!empty($oldFile) && file_exists($oldFile)) {
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
        $type = $this->request->get('type');
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
                            if (is_string($instanceImages) && $imageSetting["types"]) {
                                $instanceImages = json_decode($instanceImages, true);
                            }

                            if ($uploadOnce) {
                                // Upload once can only be used by multiple images.
                                if ($imageSetting['types']) {
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
                                if ($imageSetting['types']) {
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
                                            $instance->$imageFieldName = $instanceImages;
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

    public function processDeleteImage($instance) {
        if (is_null($instance)) {
            $this->app->getTemplateEngine()->assign(array(
                'error' => $this->app->trans('instanceNotFound', array('model' => $this->model())),
                'errorAttributes' => array(),
                'instance' => $this->model()
            ));
        }
        else {
            if ($this->imageDestroyPath != null) {
                $field = $this->getParam('field');
                $imagesField = $instance->get($field);
                $imageSettings = $this->getImageSettings();
                if (!is_array($imagesField)) {
                    if(!(!$imageSettings[$field]["_config"]["multiple"] && !empty($imageSettings[$field]["options"]))){
                        $imagesField = UtilitiesHelper::decodeJson($imagesField, true);
                    }
                }
                $position = $this->getParam('position');
                if(!empty($position) || (int)$position === 0) $imageField = $imagesField[$position];
                else $imageField = $imagesField;

                if (is_array($imageField)) {
                    $values = array_values($imageField);
                    $image = $values[0];
                }
                else {
                    $image = $imageField;
                }
                $this->setPaths();
                $this->app->getTemplateEngine()->assign(array(
                    'position' => $this->getParam('position'),
                    '_imageSettingName' => $this->getParam('setting'),
                    '_image' => $image
                ));
            }
            else {
                $this->app->getTemplateEngine()->assign(array(
                    'error' => "Please set destroyPath",
                    'errorAttributes' => array(),
                    'instance' => $this->model()
                ));
            }
        }
        $this->render('/admin/widgets/imageUploader/multiple/_delete');
    }
}