<?php
/**
  * contains i18n interface
  *
  * @package        tourBase
  * @subpackage     Core\i18n
  * @author         David Lienhard <david@t-error.ch>
  * @copyright      tourasia
*/

declare(strict_types=1);

namespace DavidLienhard\i18n;

/**
  * interface for i18n class
  *
  * @author         David Lienhard <david@t-error.ch>
  * @copyright      tourasia
*/
interface i18nInterface
{
    /**
     * Constructor
     * The constructor sets all important settings. All params are optional, you can set the options via extra functions too.
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string|null     $filePath       This is the path for the language files. You must use the '{LANGUAGE}' placeholder for the language.
     * @param           string|null     $cachePath      This is the path for all the cache files. Best is an empty directory with no other files in it. No placeholders.
     * @param           string|null     $fallbackLang   This is the language which is used when there is no language file for all other user languages. It has the lowest priority.
     * @param           string|null     $prefix         The class name of the compiled class that contains the translated texts. Defaults to 'L'.
     * @return          void
     */
    public function __construct(
        string $filePath = null,
        string $cachePath = null,
        string $fallbackLang = null,
        string $prefix = null
    );

    /**
     * initializes the class
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     */
    public function init();

    /**
     * return whether the class is initialized or not
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @return          bool
     */
    public function isInitialized() : bool;

    /**
     * returns the applied language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @return          string
     */
    public function getAppliedLang() : string;

    /**
     * returns the cache path
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @return          string
     */
    public function getCachePath() : string;

    /**
     * returns the fallback language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @return          string
     */
    public function getFallbackLang() : string;

    /**
     * sets the path of the lanuage files
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $filePath   filepath to set
     * @return          void
     */
    public function setFilePath(string $filePath);

    /**
     * sets the path to the cache files
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $cachePath  cache path to set
     * @return          void
     */
    public function setCachePath(string $cachePath);

    /**
     * sets a fallback language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $fallbackLang   language to set
     * @return          void
     */
    public function setFallbackLang($fallbackLang);

    /**
     * whether to merge the fallback languages or not
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           bool    $mergeFallback  merge fallback language
     * @return          void
     */
    public function setMergeFallback($mergeFallback);

    /**
     * sets the prefix for the result class
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $prefix     prefix to set
     * @return          void
     */
    public function setPrefix(string $prefix);

    /**
     * sets a forced language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $forcedLang     forced language to set
     * @return          void
     */
    public function setForcedLang(string $forcedLang);

    /**
     * sets as section separator
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string      $sectionSeparator       section separator to set
     * @return          void
     */
    public function setSectionSeparator(string $sectionSeparator);

    /**
     * getUserLangs()
     * Returns the user languages
     * Normally it returns an array like this:
     * 1. Forced language
     * 2. Language in $_GET['lang']
     * 3. Language in $_SESSION['lang']
     * 4. HTTP_ACCEPT_LANGUAGE
     * 5. Fallback language
     * Note: duplicate values are deleted.
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @return          array       with the user languages sorted by priority
     */
    public function getUserLangs() : array;
}
