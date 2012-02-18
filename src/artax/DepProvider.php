<?php

/**
 * Artax DepProvider Class File
 * 
 * PHP version 5.4
 * 
 * @category artax
 * @package  core
 * @author   Daniel Lowrey <rdlowrey@gmail.com>
 */

namespace artax {
  
  /**
   * DepProvider Class
   * 
   * @category artax
   * @package  core
   * @author   Daniel Lowrey <rdlowrey@gmail.com>
   */
  class DepProvider extends Bucket implements ProviderInterface
  {
    /**
     * @var DotNotation
     */
    protected $dotNotation;
    
    /**
     * Initializes DotNotation object dependency
     * 
     * @param DotNotation $dotNotation A DotNotation object for class name parsing
     * 
     * @return void
     */
    public function __construct(DotNotation $dotNotation)
    {
      $this->dotNotation = $dotNotation;
    }
    
    /**
     * Factory method for object instantiation
     * 
     * @param string $type   A dot notation class name
     * @param array  $custom An array of specific constructor arguments to use
     * 
     * @return mixed A dependency-injected object
     */
    public function make($type, Array $custom=[])
    {
      $specd = empty($this->params[$type]) ? [] : $this->params[$type];
      $class = $this->dotNotation->parse($type);
      return $this->getInjectedInstance($class, $specd, $custom);
    }
    
    /**
     * Return an instantiated object based on specified and custom dependencies
     * 
     * The dot notation class names specified in the config file are necessary
     * when a class constructor's method signature specifies an abstract class
     * or interface. When this occurs, reflection alone cannot allow dependency
     * instantiation. As a result, we need to manually specify the name of the
     * appropriate class to load for such instances.
     * 
     * @param string $class  A fully qualified and namespaced class name
     * @param array  $specd  An associative array of fully qualified dependency
     *                       class names needed for object instantiation
     * @param array  $custom An associative array of specific dependency objects
     *                       to use for object instantiation instead of new
     *                       dependency instances
     * 
     * @return mixed A dependency-injected object
     */
    protected function getInjectedInstance($class, Array $specd, Array $custom)
    {
      $refl = new \ReflectionClass($class);
      $args = $this->parseConstructorArgs($refl);
      $deps = [];
      
      foreach ($args as $key => $val) {
        if (isset($custom[$key])) {
          $deps[$key] = $custom[$key];
        } elseif (isset($specd[$key])) {
          $deps[$key] = $this->make($specd[$key]);
        } else {
          $deps[$key] = $this->make($val);
        }
      }
      
      return $refl->newInstanceArgs($deps);
    }
    
    /**
     * Parses a key/value array of argument names and types from a constructor
     * 
     * @param \ReflectionClass $refl A reflection class instance
     * 
     * @return Returns a key/value array of argument
     */
    protected function parseConstructorArgs(\ReflectionClass $refl)
    {
      $args = [];
      $p = '/Parameter\s#\d+\s\[\s<(?:optional|required)>\s([^\s]+)\s\$([^\s]+)\s\]/';
      if (preg_match_all($p, $refl->getConstructor(), $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
          $args[$m[2]] = $m[1];
        }
      }
      return $args;
    }
  }
}
