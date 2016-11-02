# Full Silex Administrator Package

Silex is a powerfull micro framework. Well, "micro" means you can create a small website within seconds. But sometimes you need to expand this framework into a full stack to moderate a bigger website. To make it happen at least you need to register several service providers, adding some functions in base controller, models, and many more.

Full Silex is the a fast track to build a full stack framework based on the amazing Silex Micro Framework. It contains several base classes such as Base Controller, Base Model, several Helpers, and many more.

To make your development process faster, here the administrator site package. You can use it along with Full Silex and get your CRUD template and some useful features.

## Installation

#### 1. Add full-silex-admin as a required library in your composer project
```
composer require icemanbsi/full-silex-admin
```

#### 2. Prepare the project
you can copy the project template from `/vendor/icemanbsi/full-silex-admin/public/resources` into your project "public/resources".

#### 3. Setting up the project
- Go to `App/Application.php` at the first row after class declaration, add `use AdminApplication;`.
- Add AdminControllerProvider in the setControllerProviders function.
- Add setTemplateDirectories function in Application class. Your Application class now should be like :
```
namespace App;

use FullSilexAdmin\AdminApplication;

class Application extends \FullSilex\Application
{
    use AdminApplication;

    protected $useDatabase          = false;
    protected $useMailer            = true;
    protected $useTranslator        = true;
    protected $useTemplateEngine    = true;

    protected function setControllerProviders(){
        $this->mount("/", new DefaultControllerProvider());
        $this->mount("/admin", new AdminControllerProvider());
    }

    public function setTemplateDirectories(){
        return array_merge(parent::setTemplateDirectories(), $this->setAdminTemplateDirectories());
    }
}
```
- Create AdminControllerProvider class, extended from FullSilex\ControllerProvider
- Create App\Controllers\Admin\AdminsController class, extended from FullSilexAdmin\Controller\AdminsController
- Create App\Controllers\Admin\HomeController class, extended from FullSilexAdmin\Controller\HomeController
- Create App\Controllers\Admin\SettingsController class, extended from FullSilexAdmin\Controller\SettingsController
- Create App\Models\Admin class, extended from FullSilexAdmin\Models\Admin
- Create App\Models\AdminSession class, extended from FullSilexAdmin\Models\AdminSession
- Create App\Models\Setting class, extended from FullSilexAdmin\Models\Setting
- Create App\Models\Repositories\AdminRepository class, extended from FullSilexAdmin\Models\Repositories\AdminRepository

#### 4. You are ready to go..
Add your controllers, models, template files, and others.

#### VIEWS
You can override default views by creating files with the same name (and subfolder) in resources/views/admin. For example to replace main menu you can create resources/views/admin/widgets/_mainMenu.twig.


## Credits

1. Silex Framework
2. Database migration by Ruckus (ruckusing/ruckusing-migrations)
3. Template by Revox (This is not a free template, so to get full details please buy the original from https://themeforest.net/item/pages-admin-dashboard-template-web-app/9694847)