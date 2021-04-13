<?php
/**
  * contains i18n interface for cached files
  *
  * @package        tourBase
  * @subpackage     Core i18n
  * @author         David Lienhard <david@t-error.ch>
  * @copyright      tourasia
*/
declare(strict_types=1);

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
     * @copyright       tourasia
     * @param           string          $string         name of the property to call
     * @param           array           $args           arguments for translation
     */
    public static function __callStatic(string $string, array|null $args) : void;
}
