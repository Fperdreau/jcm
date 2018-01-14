<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 15/08/2017
 * Time: 15:14
 */

namespace includes;

use \ReflectionMethod;

/**
 * Class Router
 */
class Router
{

    private static $namespace = 'includes';

    private static $data;

    private static $controller;

    private static $controllerName;

    private static $action;

    private static $argsOrdered;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Build route
     *
     * @return void
     */
    public static function route($namespace = null)
    {
        if (!is_null($namespace)) {
            self::$namespace = $namespace;
        }
        self::addData($_POST);
        self::addData($_GET);
        self::addData($_REQUEST);

        if (!self::getController()) {
            Page::notFound();
        } else {
            if (self::getAction()) {
                self::getParameters();
                if (self::isStatic()) {
                    self::output(self::executeStatic());
                } else {
                    self::output(self::execute());
                }
            } else {
                Page::notFound();
            }
        }
    }

    /**
     * Add data
     *
     * @param array $data
     * @return void
     */
    private static function addData(array $data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                self::$data[$key] = $value;
            }
        }
    }

    /**
     * Return Router's data
     *
     * @return array
     */
    public static function getData()
    {
        return self::$data;
    }

    /**
     * Get parameters of target controller's method
     *
     * @return void
     */
    private static function getParameters()
    {
        $rm = new \ReflectionMethod(self::$controllerName, self::$action);
        $params = $rm->getParameters();

        self::$argsOrdered = array();
        foreach ($params as $param) {
            if (key_exists($param->getName(), self::$data)) {
                self::$argsOrdered[] = self::$data[$param->getName()];
            }
        }

        if (empty(self::$argsOrdered)) {
            self::$argsOrdered = array(self::$data);
        }
    }

    /**
     * Instantiate controller from name
     *
     * @param string $controllerName
     * @return void
     */
    private static function instantiate($controllerName)
    {
        if (class_exists($controllerName)) {
            if (self::isSingleton($controllerName)) {
                self::$controller = $controllerName::getInstance();
            } else {
                self::$controller = new $controllerName();
            }
            return true;
        }
        return false;
    }

    /**
     * Get controller name
     *
     * @return bool: success or failure
     */
    private static function getController()
    {
        if (isset(self::$data['controller'])) {
            self::$controllerName = "\\" . self::$namespace . "\\" . self::$data['controller'];
            unset(self::$data['controller']);
            return true;
        }
        return false;
    }

    /**
     * Get action name
     *
     * @return bool: success or failure
     */
    private static function getAction()
    {
        if (isset(self::$data['action'])) {
            if (method_exists(self::$controllerName, self::$data['action'])) {
                self::$action = self::$data['action'];
                unset(self::$data['action']);
                return true;
            } else {
                return false;
            }
        } else {
            self::$action = "index";
            return true;
        }
    }

    /**
     * Check if target method is static
     *
     * @return boolean
     */
    private static function isStatic()
    {
        $MethodChecker = new ReflectionMethod(self::$controllerName, self::$action);
        return $MethodChecker->isStatic();
    }

    /**
     * Check if target controller is a singleton class (private constructor)
     *
     * @param string $controllerName
     * @return boolean
     */
    private static function isSingleton($controllerName)
    {
        $MethodChecker = new \ReflectionMethod($controllerName, '__construct');
        return $MethodChecker->isPrivate();
    }

    /**
     * Execute request
     *
     * @return void
     */
    private static function execute()
    {
        try {
            self::instantiate(self::$controllerName);
            return call_user_func_array(array(self::$controller, self::$action), self::$argsOrdered);
        } catch (\Exception $e) {
            Logger::getInstance(APP_NAME)->error($e);
            return array('status'=>false);
        }
    }

    /**
     * Execute request for static controllers
     *
     * @return void
     */
    private static function executeStatic()
    {
        try {
            return call_user_func_array(self::$controllerName . "::" . self::$action, self::$argsOrdered);
        } catch (Exception $e) {
            Logger::getInstance(APP_NAME, __CLASS__)->error($e);
            return array('status'=>false);
        }
    }

    /**
     * Output request
     *
     * @param mixed $result
     * @return void
     */
    private static function output($result)
    {
        if (self::isAjax()) {
            echo json_encode($result);
        }
    }

    /**
     * Test if AJAX call
     *
     * @return boolean
     */
    private static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Build URL
     *
     * @param string $controller
     * @param string $action
     * @param array $params
     * @return string
     */
    public static function buildUrl($controller, $action, array $params = null)
    {
        $paramStr = '';
        if (!is_null($params)) {
            foreach ($params as $key => $value) {
                $paramStr .= "&{$key}={$value}";
            }
        }
        return "php/router.php?controller={$controller}&action={$action}{$paramStr}";
    }
}
