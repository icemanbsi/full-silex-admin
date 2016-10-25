<?php
/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/13/16
 * Time: 4:09 PM
 */

namespace FullSilexAdmin;


trait AdminApplication
{
    public function setAdminTemplateDirectories(){
        return array($this->getRootDir() . '/../resources/views');
    }
}