<?php
/**
 * Created by PhpStorm.
 * User: Trio-1602
 * Date: 10/13/16
 * Time: 3:27 PM
 */

namespace FullSilexAdmin;


class AdminControllerProvider extends \FullSilex\ControllerProvider
{

    protected function setUrlRules( $controllers ){
        /*
        EXAMPLE :
        --------------------
        $controllers
            ->match('/users/{method}', 'App\\Controllers\\Api\\UsersController::action')
            ->method('GET|POST');
        $controllers
            ->match('/campaigns/{method}', 'App\\Controllers\\Api\\CampaignsController::action')
            ->method('GET|POST');
        $controllers
            ->match('/workshops/{method}', 'App\\Controllers\\Api\\WorkshopsController::action')
            ->method('GET|POST');
        $controllers
            ->match('/utilities/{method}', 'App\\Controllers\\Api\\UtilitiesController::action')
            ->method('GET|POST');
        */

        $controllers
            ->match('/', 'FullSilexAdmin\\Controllers\\HomeController::action')
            ->method('GET|POST');

    }

}