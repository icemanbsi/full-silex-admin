<?php
/**
 * Created by Bobby Stenly Irawan (http://bobbystenly.com)
 * Date: 10/13/16
 * Time: 4:09 PM
 */

namespace FullSilexAdmin;


trait AdminApplication
{
    public function setAdminTemplateDirectories(){
        return array(__DIR__ . '/../resources/views');
    }
}