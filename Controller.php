<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package Piwik_Trails
 */

/**
 *
 * @package Piwik_Trails
 */
class Piwik_Trails_Controller extends Piwik_Controller
{
	function index()
	{
		$view = Piwik_View::factory('index');
		$view->graph = $this->getGraph();
		echo $view->render();
	}

	function getGraph()
	{
		$idSite = Piwik_Common::getRequestVar('idSite');
		$sDate = Piwik_Common::getRequestVar('date');
		$bCalc = false;

		switch( Piwik_Common::getRequestVar('period')) {
			case 'range':
				$bCalc = true;
				$aDate = explode(',', $sDate);
				$sStart = date('Y-m-d 00:00:00', strtotime($aDate[0]));
				$sEnd   = date('Y-m-d 23:59:59', strtotime($aDate[0]));
				break;
			case 'year':
				$sSO = ' THIS YEAR';
				$sEO = ' +1 YEAR -1 SECOND';
				break;
			case 'month':
				$sSO = ' THIS MONTH';
				$sEO = ' +1 MONTH -1 SECOND';
				break;
			case 'week':
				$sSO = ' THIS WEEK';
				$sEO = ' +1 WEEK -1 SECOND';
				break;
			case 'day':
			default:
				$sSO = '';
				$sEO = ' +1 DAY -1 SECOND';
		}
		if(!$bCalc) {
			$sStart = date('Y-m-d H:i:s', strtotime($sDate.$sSO));
			$sEnd   = date('Y-m-d H:i:s', strtotime($sStart.$sEO));
		}

                $sSite = Piwik_FetchOne("SELECT main_url FROM ".Piwik_Common::prefixTable('site'));
                $aHosts = array(parse_url($sSite, PHP_URL_HOST));

		$aSiteHosts = Piwik_FetchAll("SELECT url FROM ".Piwik_Common::prefixTable('site_url')." WHERE idsite = ?", array($idSite));
		foreach($aSiteHosts as $aHost)
		{
			$aHosts[] = parse_url($aHost['url'],  PHP_URL_HOST);
		}

		$aVisitActions = Piwik_FetchAll("SELECT N.name AS node, R.name AS node_ref, COUNT(1) AS num 
			FROM ".Piwik_Common::prefixTable('log_link_visit_action')." L
			JOIN ".Piwik_Common::prefixTable('log_action')." N ON N.idaction = L.idaction_url
			JOIN ".Piwik_Common::prefixTable('log_action')." R ON R.idaction = L.idaction_url_ref
			WHERE L.idsite = ? AND L.server_time BETWEEN ? AND ? 
			GROUP BY node, node_ref
			ORDER BY num DESC LIMIT 0, 20", array($idSite, $sStart, $sEnd));

		$aTrails = array('nodes' => array(), 'edges' => array());
		$aReturn = array('data' => $aTrails);

		foreach(array_keys($aVisitActions) as $id)
		{
			$aVisitAction = &$aVisitActions[$id];
			foreach(array('node', 'node_ref') as $k)
			{
				$aVisitAction[$k] = preg_replace('#^(https?://)?(www\.)?#', '', str_replace($aHosts, '', $aVisitAction[$k]));
			}
			$sNode = $aVisitAction['node'];
			if(!isset($aTrails['nodes'][$sNode]))
			{
				$aTrails['nodes'][$sNode] = array(
					'visits' => $aVisitAction['num'],
					'label' => $sNode,
					'out' => 0,
				);
			} else {
				$aTrails['nodes'][$sNode]['visits'] += $aVisitAction['num'];
			}

		}

		while($aVisitActions) 
		{
			$aVisitAction = array_shift($aVisitActions);
			$sEdge = $aVisitAction['node_ref'].'_to_'.$aVisitAction['node'];

			$sNode = $aVisitAction['node_ref'];
			if(!isset($aTrails['nodes'][$sNode]))
			{
				$aTrails['nodes'][$sNode] = array(
					'visits' => $aVisitAction['num'],
					'label' => $sNode,
					'out' => $aVisitAction['num'],
				);
			} else {
				$aTrails['nodes'][$sNode]['out'] += $aVisitAction['num'];
			}

			if(empty($aTrails['edges'][$sEdge]))
			{
				$aTrails['edges'][$sEdge] = array(
					'target' => $aVisitAction['node'],
					'source' => $aVisitAction['node_ref'],
					'weight' => $aVisitAction['num'],
				);
			} else {
				$aTrails['edges'][$sEdge]['weight']+=$aVisitAction['num'];
			}
		}

		foreach(array_keys($aTrails['nodes']) as $sNode)
		{
			$aNode = &$aTrails['nodes'][$sNode];
			$aReturn['data']['nodes'][] = array(
				'id' => strval($sNode),
				'label' => strval($aNode['label']),
				'visits' => $aNode['visits'],
				'bounce' => $aNode['out']/$aNode['visits']
			);
			unset($aTrails['nodes'][$sNode]);
		}

		foreach(array_keys($aTrails['edges']) as $sEdge)
		{
			$aEdge = &$aTrails['edges'][$sEdge];
			$aReturn['data']['edges'][] =array(
				'id' => strval($sEdge),
				'target' => strval($aEdge['target']),
				'source' => strval($aEdge['source']),
				'weight' => $aEdge['weight']
			);
			unset($aTrails['edges'][$sEdge]);
		}
		return json_encode($aReturn);
	}
}
