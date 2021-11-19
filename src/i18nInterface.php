<?php declare(strict_types=1);

/**
  * contains i18n interface
  *
  * @package        tourBase
  * @subpackage     Core\i18n
  * @author         David Lienhard <david@t-error.ch>
  * @copyright      tourasia
*/

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
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
     */
    public function init(): void;

    /**
     * return whether the class is initialized or not
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     */
    public function isInitialized() : bool;

    /**
     * returns the applied language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     */
    public function getAppliedLang() : string|null;

    /**
     * returns the cache path
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     */
    public function getCachePath() : string;

    /**
     * returns the fallback language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     */
    public function getFallbackLang() : string;

    /**
     * sets the path of the lanuage files
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string  $filePath   filepath to set
     */
    public function setFilePath(string $filePath): void;

    /**
     * sets the path to the cache files
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string  $cachePath  cache path to set
     */
    public function setCachePath(string $cachePath): void;

    /**
     * sets a fallback language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string  $fallbackLang   language to set
     */
    public function setFallbackLang(string $fallbackLang): void;

    /**
     * whether to merge the fallback languages or not
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           bool    $mergeFallback  merge fallback language
     */
    public function setMergeFallback(bool $mergeFallback): void;

    /**
     * sets the prefix for the result class
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string  $prefix     prefix to set
     */
    public function setPrefix(string $prefix): void;

    /**
     * sets a forced language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string  $forcedLang     forced language to set
     */
    public function setForcedLang(string $forcedLang): void;

    /**
     * sets as section separator
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string      $sectionSeparator       section separator to set
     */
    public function setSectionSeparator(string $sectionSeparator): void;

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
     * @copyright       David Lienhard
     * @return          array       with the user languages sorted by priority
     */
    public function getUserLangs() : array;

    /**
     * sets the namespace for the class
     * null means no namespace
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string|null $namespace              namespace to set
     */
    public function setNamespace(string|null $namespace) : void;
}
