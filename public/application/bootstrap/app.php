<?php
/* @var Concrete\Core\Application\Application $app */
/* @var Concrete\Core\Console\Application $console only set in CLI environment */

/*
 * ----------------------------------------------------------------------------
 * # Custom Application Handler
 *
 * You can do a lot of things in this file.
 *
 * ## Set a theme by route:
 *
 * Route::setThemeByRoute('/login', 'greek_yogurt');
 *
 *
 * ## Register a class override.
 *
 * $app->bind('helper/feed', function() {
 * 	 return new \Application\Core\CustomFeedHelper();
 * });
 *
 * $app->bind('\Concrete\Attribute\Boolean\Controller', function($app, $params) {
 * 	return new \Application\Attribute\Boolean\Controller($params[0]);
 * });
 *
 * ## Register Events.
 *
 * Events::addListener('on_page_view', function($event) {
 * 	$page = $event->getPageObject();
 * });
 *
 *
 * ## Register some custom MVC Routes
 *
 * Route::register('/test', function() {
 * 	print 'This is a contrived example.';
 * });
 *
 * Route::register('/custom/view', '\My\Custom\Controller::view');
 * Route::register('/custom/add', '\My\Custom\Controller::add');
 *
 * ## Pass some route parameters
 *
 * Route::register('/test/{foo}/{bar}', function($foo, $bar) {
 *  print 'Here is foo: ' . $foo . ' and bar: ' . $bar;
 * });
 *
 *
 * ## Override an Asset
 *
 * use \Concrete\Core\Asset\AssetList;
 * AssetList::getInstance()
 *     ->getAsset('javascript', 'jquery')
 *     ->setAssetURL('/path/to/new/jquery.js');
 *
 * or, override an asset by providing a newer version.
 *
 * use \Concrete\Core\Asset\AssetList;
 * use \Concrete\Core\Asset\Asset;
 * $al = AssetList::getInstance();
 * $al->register(
 *   'javascript', 'jquery', 'path/to/new/jquery.js',
 *   array('version' => '2.0', 'position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false)
 *   );
 *
 * ----------------------------------------------------------------------------
 */

use Application\Karfu\Journey\Hook\HookExecuter;
use Application\Karfu\Journey\Hook\HookFunctionFactory;

$classLoader = new \Symfony\Component\ClassLoader\Psr4ClassLoader();
$classLoader->addPrefix('Application\\Factory', DIR_APPLICATION . '/' . DIRNAME_CLASSES . '/Factory');
$classLoader->addPrefix('Application\\Helper', DIR_APPLICATION . '/' . DIRNAME_CLASSES . '/Helper');
$classLoader->addPrefix('Application\\Helper\\CostCalculator', DIR_APPLICATION . '/' . DIRNAME_CLASSES . '/Helper/CostCalculator');
$classLoader->addPrefix('Application\\Karfu', DIR_APPLICATION . '/' . DIRNAME_CLASSES . '/Karfu');
$classLoader->addPrefix('Application\\Model', DIR_APPLICATION . '/' . DIRNAME_CLASSES . '/Model');
$classLoader->addPrefix('Application\\Service', DIR_APPLICATION . '/' . DIRNAME_CLASSES . '/Service');

$classLoader->register();
Database::getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

// Add api routes here
$router = $app->make('router');
$router->post('/api/process-results/process', 'Application\Controller\Api\ProcessResults::process');
$router->post('/api/suitabilityscore/build', 'Application\Controller\Api\SuitabilityScore::build');
$router->post('/api/shortlist/add', 'Application\Controller\Api\Shortlist::add');
$router->post('/api/save-search/save', 'Application\Controller\Api\SaveSearch::save');
