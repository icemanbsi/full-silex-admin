<?php
/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/13/16
 * Time: 3:39 PM
 */

namespace FullSilexAdmin\Controllers;


class HomeController extends BaseController
{
    public function index() {
        $this->render("dashboard");
    }
}