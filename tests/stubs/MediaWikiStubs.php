<?php

/**
 * Minimal MediaWiki stubs for Psalm static analysis.
 *
 * @psalm-stub-file
 */

namespace MediaWiki\Auth {

    abstract class AbstractAuthenticationProvider
    {
    }

    abstract class AbstractPreAuthenticationProvider extends AbstractAuthenticationProvider
    {
    }
}

namespace MediaWiki {

    class MediaWikiServices
    {
        /** @return self */
        public static function getInstance()
        {
        }

        /** @return \Wikimedia\Rdbms\IConnectionProvider */
        public function getConnectionProvider()
        {
        }
    }
}

namespace Wikimedia\Rdbms {

    interface IConnectionProvider
    {
        /** @return IDatabase */
        public function getPrimaryDatabase();
    }

    interface IDatabase
    {
        /** @return IResultWrapper|bool */
        public function query($sql);

        /** @return bool */
        public function tableExists($table);
    }

    interface IResultWrapper
    {
        /** @return array|false */
        public function fetchRow();
    }
}

class User
{
    /** @var string */
    public $mEmail;

    /** @var string */
    public $mName;

    /** @return self */
    public static function newFromId($id)
    {
    }

    /** @return Status */
    public function sendMail($title, $body)
    {
    }

    /** @return bool */
    public function isAllowed($permission)
    {
    }

    /** @return int */
    public function getEditCount()
    {
    }
}

class Status
{
    /** @return self */
    public static function newFatal($message)
    {
    }

    /** @return self */
    public static function newGood()
    {
    }

    /** @return bool */
    public function isGood()
    {
    }
}

class RequestContext
{
    /** @return self */
    public static function getMain()
    {
    }

    /** @return User */
    public function getUser()
    {
    }
}

class Html
{
    /** @return string */
    public static function openElement($tag, $attrs = array())
    {
    }

    /** @return string */
    public static function closeElement($tag)
    {
    }

    /** @return string */
    public static function element($tag, $attrs = array())
    {
    }
}

class Title
{
    /** @return string */
    public function getPartialURL()
    {
    }
}

class PermissionsError extends \Exception
{
    /** @param string $action @param array $errors */
    public function __construct($action, $errors)
    {
    }
}

/** @var string $wgCTAccessKey */
/** @var bool $wgCTCheckNoConnect */
/** @var string $wgCTServerURL */
/** @var string $wgCTAgent */
/** @var string $wgCTExtName */
/** @var int $wgCTAdminAccountId */
/** @var int $wgCTAdminNotificaionInteval */
/** @var bool $wgCTShowLink */
/** @var bool $wgCTSFW */
/** @var bool $wgCTNewEditsOnly */
/** @var int $wgCTMinEditCount */
/** @var string|null $wgCTDataStoreFile */
/** @var array<string, list<array<string, mixed>>> $wgExtensionCredits */
/** @var array<string, string> $wgAutoloadClasses */
/** @var array<string, list<string>> $wgHooks */
/** @var array<string, array<string, bool>> $wgGroupPermissions */

/** @param int $index */
function wfGetDB($index)
{
}

/**
 * @param string $name
 * @param string $value
 * @param int|array{expires?: int, path?: string, domain?: string|null, secure?: bool, httponly?: bool, samesite?: string} $expires_or_options
 * @param string $path
 * @param string|null $domain
 * @param bool $secure
 * @param bool $httponly
 * @return bool
 */
function setcookie($name, $value = '', $expires_or_options = 0, $path = '', $domain = '', $secure = false, $httponly = false)
{
}
