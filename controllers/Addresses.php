<?php

declare(strict_types=1);

namespace WebBook\Mall\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Classes\Controller;
use BackendMenu;

class Addresses extends Controller
{
    /**
     * Implement behaviors for this controller.
     * @var array
     */
    public $implement = [
        FormController::class,
        ListController::class,
    ];

    /**
     * The configuration file for the form controller implementation.
     * @var string
     */
    public $formConfig = 'config_form.yaml';

    /**
     * The configuration file for the list controller implementation.
     * @var string
     */
    public $listConfig = 'config_list.yaml';

    /**
     * Required admin permission to access this page.
     * @var array
     */
    public $requiredPermissions = [
        'webbook.mall.manage_customer_addresses',
    ];

    /**
     * Construct the controller.
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('RainLab.User', 'user', 'customer_addresses');
    }
}
