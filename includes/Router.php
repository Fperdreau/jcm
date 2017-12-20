<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 15/08/2017
 * Time: 15:14
 */

class Router {

    private static $data;

    private static $controller;

    private static $action;

    private static $argsOrdered;

    private function __construct() {}

    private function __clone() {}

    private static function sanitize(array $data) {
        if (!empty($data)) {
            // Sanitize $_POST data
            foreach ($data as $key=>$value) {
                self::$data[$key] = htmlspecialchars($value);
            }
        }
    }

    public static function route() {
        self::sanitize($_POST);
        self::sanitize($_GET);
        self::sanitize($_REQUEST);

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

    public static function getData() {
        return self::$data;
    }

    private static function getParameters() {
        $rm = new \ReflectionMethod(self::$controller, self::$action);
        $params = $rm->getParameters();

        self::$argsOrdered = array();

        foreach($params as $param) {
            if (key_exists($param->getName(), self::$data)) {
                self::$argsOrdered[] = self::$data[$param->getName()];
            }
        }

        if (empty(self::$argsOrdered)) {
            self::$argsOrdered = self::$data;
        }
    }

    private static function instantiate() {
        if (class_exists(self::$data['controller'])) {
            if (self::is_singleton(self::$data['controller'])) {
                $controllerName = self::$data['controller'];
                self::$controller = $controllerName::getInstance();
            } else {
                self::$controller = new self::$data['controller']();
            }
            unset(self::$data['controller']);
            return true;
        }
        return false;
    }

    private static function getController() {
        if (isset(self::$data['controller'])) {
            self::$controller = self::$data['controller'];
            return true;
        }
        return false;
    }

    private static function getAction() {
        if (isset(self::$data['action'])) {
            if (method_exists(self::$controller, self::$data['action'])) {
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
        $MethodChecker = new ReflectionMethod(self::$controller, self::$action);
        return $MethodChecker->isStatic();
    }

    private static function is_singleton($controllerName) {
        $MethodChecker = new ReflectionMethod($controllerName, '__construct');
        return $MethodChecker->isPrivate();
    }

    private static function execute() {
        try {
            self::instantiate();
            return call_user_func(array(self::$controller, self::$action), self::$argsOrdered);
        } catch (Exception $e) {
            Logger::getInstance(APP_NAME)->error($e);
            return array('status'=>false);
        }
    }

    private static function executeStatic() {
        try {
            return call_user_func(self::$controller . "::" . self::$action, self::$argsOrdered);
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