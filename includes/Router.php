<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 15/08/2017
 * Time: 15:14
 */


/**
 * Class Router
 */
class Router {

    private static $data;

    private static $controller;

    private static $controllerName;

    private static $action;

    private static $argsOrdered;

    private function __construct() {}

    private function __clone() {}

    public static function route() {
        self::addData($_POST);
        self::addData($_GET);
        self::addData($_REQUEST);

        if (!self::getController()) {
            Page::notFound();
        } else {
            if (self::getAction()) {
                self::getParameters();
                if (self::is_static()) {
                    self::output(self::executeStatic());
                } else {
                    self::output(self::execute());
                }
            } else {
                Page::notFound();
            }
        }
    }

    private static function addData($data) {
        if (!empty($data)) {
            foreach ($data as $key=>$value) {
                self::$data[$key] = $value;
            }
        }
    }

    public static function getData() {
        return self::$data;
    }

    private static function getParameters() {
        $rm = new \ReflectionMethod(self::$controllerName, self::$action);
        $params = $rm->getParameters();

        self::$argsOrdered = array();
        foreach($params as $param) {
            if (key_exists($param->getName(), self::$data)) {
                self::$argsOrdered[] = self::$data[$param->getName()];
            }
        }

        if (empty(self::$argsOrdered)) {
            self::$argsOrdered = array(self::$data);
        }
    }

    private static function instantiate($controllerName) {
        if (class_exists($controllerName)) {
            if (self::is_singleton($controllerName)) {
                self::$controller = $controllerName::getInstance();
            } else {
                self::$controller = new $controllerName();
            }
            return true;
        }
        return false;
    }

    private static function getController() {
        if (isset(self::$data['controller'])) {
            self::$controllerName = self::$data['controller'];
            unset(self::$data['controller']);
            return true;
        }
        return false;
    }

    private static function getAction() {
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

    private static function is_static() {
        $MethodChecker = new ReflectionMethod(self::$controllerName, self::$action);
        return $MethodChecker->isStatic();
    }

    private static function is_singleton($controllerName) {
        $MethodChecker = new ReflectionMethod($controllerName, '__construct');
        return $MethodChecker->isPrivate();
    }

    private static function execute() {
        try {
            self::instantiate(self::$controllerName);
            return call_user_func_array(array(self::$controller, self::$action), self::$argsOrdered);
        } catch (Exception $e) {
            Logger::getInstance(APP_NAME)->error($e);
            return array('status'=>false);
        }
    }

    private static function executeStatic() {
        try {
            return call_user_func_array(self::$controllerName . "::" . self::$action, self::$argsOrdered);
        } catch (Exception $e) {
            Logger::getInstance(APP_NAME, __CLASS__)->error($e);
            return array('status'=>false);
        }
    }

    private static function output($result) {
        if (self::is_ajax()) {
            echo json_encode($result);
        }
    }

    private static function is_ajax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}