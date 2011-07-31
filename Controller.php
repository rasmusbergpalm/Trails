<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Controller.php 4533 2011-04-22 22:05:46Z vipsoft $
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
		//$view->nonce = Piwik_Nonce::getNonce('Piwik_Feedback.sendFeedback', 3600);
                $view->graph = $this->getGraph();
		echo $view->render();
	}

        

        function getGraph()
        {
                $idSite = Piwik_Common::getRequestVar('idSite');

                $actions = Piwik_FetchAll("SELECT * FROM ".Piwik_Common::prefixTable('log_action'));
                $site = Piwik_FetchOne("SELECT main_url FROM ".Piwik_Common::prefixTable('site'));
                $host = parse_url($site, PHP_URL_HOST);

                $rmstrings = array($host, 'http://', 'www.');
                foreach ($actions as $action){
                    $ac[$action['idaction']] = str_replace($rmstrings, '', $action['name']);
                }

                $vas = Piwik_FetchAll("SELECT idaction_url, idaction_url_ref FROM ".Piwik_Common::prefixTable('log_link_visit_action')." WHERE idsite = ?", array($idSite));

                

                foreach($vas as $va){
                    $node_id = $ac[$va['idaction_url']];
                    if(empty($nw['nodes'][$node_id])){
                        $nw['nodes'][$node_id]['visits'] = 1;
                        $nw['nodes'][$node_id]['label'] = $node_id;
                        $nw['nodes'][$node_id]['out'] = 0;
                    }else{
                        $nw['nodes'][$node_id]['visits']++;
                    }

                    if($va['idaction_url_ref'] == 0) continue;

                    $edge_id = $ac[$va['idaction_url_ref']].'to'.$ac[$va['idaction_url']];
                    if(empty($nw['edges'][$edge_id])){
                        $nw['edges'][$edge_id] = array(
                            'target' => $ac[$va['idaction_url']],
                            'source' => $ac[$va['idaction_url_ref']],
                            'weight' => 1
                        );
                        $nw['nodes'][$ac[$va['idaction_url_ref']]]['out'] = 1;
                    }else{
                        $nw['edges'][$edge_id]['weight']++;
                        $nw['nodes'][$ac[$va['idaction_url_ref']]]['out']++;
                    }
                }

                
                foreach($nw['nodes'] as $id => $n){
                    $nw2['data']['nodes'][] =array(
                        'id' => "$id",
                        'label' => $n['label'],
                        'visits' => $n['visits'],
                        'bounce' => $n['out']/$n['visits']
                    );
                }
                
                foreach($nw['edges'] as $id => $e){
                    $nw2['data']['edges'][] =array(
                            'id' => $id,
                            'target' => "".$e['target']."",
                            'source' => "".$e['source']."",
                            'weight' => $e['weight']
                        );
                }
                
                return json_encode($nw2);

        }

	/**
	 * send email to Piwik team and display nice thanks
	 */
	function sendFeedback()
	{
		$email = Piwik_Common::getRequestVar('email', '', 'string');
		$body = Piwik_Common::getRequestVar('body', '', 'string');
		$category = Piwik_Common::getRequestVar('category', '', 'string');
		$nonce = Piwik_Common::getRequestVar('nonce', '', 'string');

		$view = Piwik_View::factory('sent');
		$view->feedbackEmailAddress = Zend_Registry::get('config')->General->feedback_email_address;
		try
		{
			$minimumBodyLength = 35;
			if(strlen($body) < $minimumBodyLength)
			{
				throw new Exception(Piwik_TranslateException('Feedback_ExceptionBodyLength', array($minimumBodyLength)));
			}
			if(!Piwik::isValidEmailString($email))
			{
				throw new Exception(Piwik_TranslateException('UsersManager_ExceptionInvalidEmail'));
			}
			if(preg_match('/https?:/i', $body))
			{
				throw new Exception(Piwik_TranslateException('Feedback_ExceptionNoUrls'));
			}
			if(!Piwik_Nonce::verifyNonce('Piwik_Feedback.sendFeedback', $nonce))
			{
				throw new Exception(Piwik_TranslateException('General_ExceptionNonceMismatch'));
			}
			Piwik_Nonce::discardNonce('Piwik_Feedback.sendFeedback');

			$mail = new Piwik_Mail();
			$mail->setFrom(Piwik_Common::unsanitizeInputValue($email));
			$mail->addTo($view->feedbackEmailAddress, 'Piwik Team');
			$mail->setSubject('[ Feedback form - Piwik ] ' . $category);
			$mail->setBodyText(Piwik_Common::unsanitizeInputValue($body) . "\n"
				. 'Piwik ' . Piwik_Version::VERSION . "\n"
				. 'IP: ' . Piwik_IP::getIpFromHeader() . "\n"
				. 'URL: ' . Piwik_Url::getReferer() . "\n");
			@$mail->send();
		}
		catch(Exception $e)
		{
			$view->ErrorString = $e->getMessage();
			$view->message = $body;
		}

		echo $view->render();
	}
}
