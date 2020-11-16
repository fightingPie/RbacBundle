<?php

namespace SymfonyRbac\Rbac;

/**
 * Class BaseObject
 * @package SymfonyRbac\Rbac
 */
class BaseObject {

  /**
   * BaseObject constructor.
   * @param array $config
   */
  public function __construct($config = []) {
    if (!empty($config)) {
      self::configure($this, $config);
    }
  }

  /**
   * @param $object
   * @param $properties
   * @return mixed
   */
  public static function configure($object, $properties) {
    foreach ($properties as $name => $value) {
      $object->$name = $value;
    }

    return $object;
  }

  /**
   * @return false|string
   */
  public static function className() {
    return get_called_class();
  }

  /**
   * @param $name
   * @return mixed
   */
  public function __get($name) {
    $getter = 'get' . $name;
    if (method_exists($this, $getter)) {
      return $this->$getter();
    } elseif (method_exists($this, 'set' . $name)) {
      throw new \InvalidArgumentException('Getting write-only property: ' . get_class($this) . '::' . $name);
    }

    throw new \InvalidArgumentException('Getting unknown property: ' . get_class($this) . '::' . $name);
  }

  /**
   * @param $name
   * @param $value
   */
  public function __set($name, $value) {
    $setter = 'set' . $name;
    if (method_exists($this, $setter)) {
      $this->$setter($value);
    } elseif (method_exists($this, 'get' . $name)) {
      throw new \InvalidArgumentException('Setting read-only property: ' . get_class($this) . '::' . $name);
    } else {
      throw new \InvalidArgumentException('Setting unknown property: ' . get_class($this) . '::' . $name);
    }
  }

  /**
   * @param $name
   * @return bool
   */
  public function __isset($name) {
    $getter = 'get' . $name;
    if (method_exists($this, $getter)) {
      return $this->$getter() !== null;
    }

    return false;
  }

  /**
   * @param $name
   */
  public function __unset($name) {
    $setter = 'set' . $name;
    if (method_exists($this, $setter)) {
      $this->$setter(null);
    } elseif (method_exists($this, 'get' . $name)) {
      throw new \InvalidArgumentException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
    }
  }

  /**
   * @param $name
   * @param $params
   */
  public function __call($name, $params) {
    throw new \InvalidArgumentException('Calling unknown method: ' . get_class($this) . "::$name()");
  }

  /**
   * @param $name
   * @param bool $checkVars
   * @return bool
   */
  public function hasProperty($name, $checkVars = true) {
    return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
  }

  /**
   * @param $name
   * @param bool $checkVars
   * @return bool
   */
  public function canGetProperty($name, $checkVars = true) {
    return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
  }

  /**
   * @param $name
   * @param bool $checkVars
   * @return bool
   */
  public function canSetProperty($name, $checkVars = true) {
    return method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name);
  }

  /**
   * @param $name
   * @return bool
   */
  public function hasMethod($name) {
    return method_exists($this, $name);
  }
}
