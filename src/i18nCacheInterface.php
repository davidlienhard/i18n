<?php
/**
  * contains i18n interface for cached files
  *
  * @package        tourBase
  * @subpackage     Core i18n
  * @author         David Lienhard <david@t-error.ch>
  * @version        1.0.0, 12.11.2020
  * @since          1.0.0, 12.11.2020, created
  * @copyright      tourasia
*/
declare(strict_types=1);

namespace DavidLienhard\i18n;

/**
  * interface for cached i18n classes
  *
  * @author         David Lienhard <david@t-error.ch>
  * @version        1.0.0, 12.11.2020
  * @since          1.0.0, 12.11.2020, created
  * @copyright      tourasia
*/
interface i18nCacheInterface
{
    /**
     * calls the static properties set in the class
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @version         1.0.0, 12.11.2020
     * @since           1.0.0, 12.11.2020, created
     * @copyright       tourasia
     * @param           string          $string         name of the property to call
     * @param           string          $args           arguments for translation
     */
    public static function __callStatic($string, $args);
}
