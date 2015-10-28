<?php

namespace ModUser;

use Zend\Mvc\MvcEvent;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'prefixes' => array(
                    'ModUser_' => __DIR__ . '/library',
                ),
            ),
        );
    }

    public function getConfig()
    {
        return array_merge(
            array_change_key_case(
                require __DIR__ . '/configs/resources.config.php'
            ),
            array(
                'front_controller' => array(
                    'controllerDirectory' => array(
                        'mod-user' => __DIR__ . '/controllers',
                    ),
                ),
                'router' => array(
                    'routes' => require __DIR__ . '/configs/routes.config.php',
                ),
                'view' => array(
                    'scriptPath' => array(
                        __DIR__ . '/views/scripts',
                    ),
                ),
            )
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'user.model.userMapper' => 'ModUser_Service_UserMapperFactory',
                'user.userManager'      => 'ModUser_Service_UserManagerFactory',
            ),
        );
    }

    public function onBootstrap(MvcEvent $e)
    {
        $sm = $e->getApplication()->getServiceManager();

        // If log resource is present register plugin which adds user-related
        // variables to extra data of a log event
        $log = $sm->get('Log');

        if ($log) {
            /** @var $frontController \Zend_Controller_Front */
            $frontController = $sm->get('FrontController');
            $frontController->registerPlugin(new \ModUser_Plugin_LogExtras($log, $frontController));
        }
    }
}
