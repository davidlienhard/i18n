<?php declare(strict_types=1);

/**
  * contains i18n interface for cached files
  *
  * @package        tourBase
  * @subpackage     Core i18n
  * @author         David Lienhard <david@t-error.ch>
  * @copyright      tourasia
*/

namespace DavidLienhard\i18n;

/**
  * interface for cached i18n classes
  *
  * @author         David Lienhard <david@t-error.ch>
  * @copyright      tourasia
*/
interface i18nCacheInterface
{
    /**
     * calls the static properties set in the class
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string          $string         name of the property to call
     * @param           array           $args           arguments for translation
     */
    public static function __callStatic(string $string, array|null $args) : int|float|string|bool;

    /**
     * used to get properties set in the class
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string          $string         name of the property to call
     * @param           array|null      $args           arguments for translation
     */
    public static function get(string $string, array|null $args = null) : int|float|string|bool;

    /**
     * return a translation as int
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string          $string         name of the property to call
     * @param           array|null      $args           arguments for translation
     */
    public static function getAsInt(string $string, array|null $args = null) : int;

    /**
     * return a translation as float
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string          $string         name of the property to call
     * @param           array|null      $args           arguments for translation
     */
    public static function getAsFloat(string $string, array|null $args = null) : float;

    /**
     * return a translation as string
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string          $string         name of the property to call
     * @param           array|null      $args           arguments for translation
     */
    public static function getAsString(string $string, array|null $args = null) : string;

    /**
     * return a translation as bool
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string          $string         name of the property to call
     * @param           array|null      $args           arguments for translation
     */
    public static function getAsBool(string $string, array|null $args = null) : bool;
}
