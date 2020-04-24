<?php

class PHPUnitUtil {
	public static function callMethod($obj, $name, array $args) {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
	}
	
	public static function callStaticMethod($obj, $name, array $args) {
        $ref = new \ReflectionMethod(get_class($obj), $name);
        return $ref->invokeArgs(null, $args);
    }
}