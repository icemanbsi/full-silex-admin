<?php
/**
 * Created by Bobby Stenly Irawan (http://bobbystenly.com)
 * Date: 10/13/16
 * Time: 3:39 PM
 */

namespace FullSilexAdmin\Controllers;


class HomeController extends BaseController
{
    public function index() {
        $this->render("index");
    }
}