<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Feedback.php 2968 2010-08-20 15:26:33Z vipsoft $
 *
 * @category Piwik_Plugins
 * @package Piwik_Trails
 */

/**
 *
 * @package Piwik_Trails
 */
class Piwik_Trails extends Piwik_Plugin
{
	public function getInformation()
	{
		return array(
			'description' => 'Trails gives a visual overview of your users navigation patterns.',
			'author' => 'Rasmus Berg Palm (rasmus(at)bergpalm.dk)',
			'author_homepage' => 'http://bergpalm.dk',
			'version' => '0.1b',
		);
	}

	function getListHooksRegistered()
	{
		return array(
			'AssetManager.getJsFiles' => 'getJsFiles',
			'Menu.add' => 'addMenu',
		);
	}

	public function addMenu()
	{
                Piwik_AddMenu('Actions_Actions', 'Trails!', array('module' => 'Trails', 'action' => 'index'), true, $order = 20);
	}

	function getJsFiles( $notification )
	{
		$jsFiles = &$notification->getNotificationObject();
		
		$jsFiles[] = "plugins/Trails/templates/js/AC_OETags.min.js";
                $jsFiles[] = "plugins/Trails/templates/js/cytoscapeweb.min.js";
                $jsFiles[] = "plugins/Trails/templates/js/json2.min.js";
                $jsFiles[] = "plugins/Trails/templates/js/jquery.mousewheel.min.js";
	}	
	
}
