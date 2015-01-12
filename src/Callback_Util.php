<?php

namespace Zaphpa;


/**
 * Callback class for route-processing.
 */
class Callback_Util {

    private static function loadFile($file) {

        if (file_exists($file)) {
            include_once($file);
        } else {
            throw new CallbackFileNotFoundException('Controller file not found');
        }

    }

    public static function getCallback($callback, $file = null) {

        if ($file) {
            self::loadFile($file);
        }

        if (is_array($callback)) {

            $originalClass = array_shift($callback);

            $method = new ReflectionMethod($originalClass, array_shift($callback));

            if ($method->isPublic()) {
                if ($method->isStatic()) {
                    $callback = array($originalClass, $method->name);
                } else {
                    $callback = array(new $originalClass, $method->name);
                }
            }
        }

        if (is_callable($callback)) {
            return $callback;
        }

        throw new InvalidCallbackException('Invalid callback');

    }

}