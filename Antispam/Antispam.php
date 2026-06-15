<?php

if ( !defined('MEDIAWIKI') ) {
    die;
}

require_once('Cleantalk.php');
require_once('CleantalkRequest.php');
require_once('CleantalkResponse.php');
require_once('CleantalkHelper.php');
require_once('CleantalkSFW.php');

/**
 * cleantalk.org is a cloud antispam service
 *
 * @see http://stopforumspam.com/faq
 * @author Denis Shagimuratov <shagimuratov@cleantalk.org>
 * @license GPL v2 or higher
 */

/**
 * Access key for cleantalk.org
 * @see https://clenatalk.org/register
 */
$GLOBALS['wgCTAccessKey'] = '';

/**
 * Check registrations, when there is errors with connection to service
 * true - option enabled
 * false - option disabled
 */
$GLOBALS['wgCTCheckNoConnect'] = true;

/**
 * Cloud URL
 */
$GLOBALS['wgCTServerURL'] = 'https://moderate.cleantalk.org/api2.0';

/**
 * Extension agent name and version
 */
$GLOBALS['wgCTAgent'] = 'mediawiki-2.4.1';

/**
 * Extension name
 */
$ctExtName = 'Antispam by CleanTalk';
$GLOBALS['wgCTExtName'] = $ctExtName;

/**
 * Admin notificaction account ID
 */
$GLOBALS['wgCTAdminAccountId'] = 1;

/**
 * Admin notificactions interval in seconds
 */
$GLOBALS['wgCTAdminNotificaionInteval'] = 10800;

/**
 * Show link to CleanTalk
 * Enabling this option places a small link under the comment form that lets others know what antispam tool protects your site.
 */
$GLOBALS['wgCTShowLink'] = true;

/**
 * SpamFireWall
 * Enabling this option makes SpamFileWall feature active.
 */
$GLOBALS['wgCTSFW'] = false;

/**
 * Edit new edits only
 * Check all edits or new edits only.
 */
$GLOBALS['wgCTNewEditsOnly'] = false;

/**
 * Minimal edits count to skip edit checking
 * Checking will skipped for users with getEditCount() more than this value.
 * It doesn'f affect when $wgCTNewEditsOnly == true
 */
$GLOBALS['wgCTMinEditCount'] = 10;

/**
 * Extension settings store file
 * @deprecated : Antispam Data stored on DB instead Antispam.store.dat file
 */
if ( file_exists(__DIR__ . '/Antispam.store.dat') ) {
    $GLOBALS['wgCTDataStoreFile'] = __DIR__ . '/Antispam.store.dat';
}

$GLOBALS['wgExtensionCredits']['antispam'][] = array(
    'path' => __FILE__,
    'name' => $ctExtName,
    'author' => 'Denis Shagimuratov',
    'url' => 'https://www.mediawiki.org/wiki/Extension:Antispam',
    'descriptionmsg' => 'cleantalk-desc',
    'version' => '2.4',
);

$GLOBALS['wgAutoloadClasses']['CTBody'] = __DIR__ . '/Antispam.body.php';
$GLOBALS['wgAutoloadClasses']['CTHooks'] = __DIR__ . '/Antispam.hooks.php';

$GLOBALS['wgHooks']['AbortNewAccount'][] = 'CTHooks::onAbortNewAccount';

$GLOBALS['wgHooks']['EditFilter'][] = 'CTHooks::onEditFilter';

$GLOBALS['wgHooks']['UploadVerifyFile'][] = 'CTHooks::onUploadFilter';

// Skip test for Administrators
$GLOBALS['wgGroupPermissions']['sysop']['cleantalk-bypass'] = true;

// Skip test for registered
$GLOBALS['wgGroupPermissions']['user']['cleantalk-bypass'] = false;

// Skip test for autoconfirmed users
$GLOBALS['wgGroupPermissions']['autoconfirmed']['cleantalk-bypass'] = false;

$GLOBALS['wgHooks']['SkinAfterBottomScripts'][] = 'CTHooks::onSkinAfterBottomScripts';
$GLOBALS['wgHooks']['TitleMove'][] = 'CTHooks::onTitleMove';
