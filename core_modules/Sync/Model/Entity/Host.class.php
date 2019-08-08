<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Host
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */

namespace Cx\Core_Modules\Sync\Model\Entity;

/**
 * Host
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */
class Host extends \Cx\Model\Base\EntityBase
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $host
     */
    protected $host;

    /**
     * @var boolean $active
     */
    protected $active;

    /**
     * @var string $apiKey
     */
    protected $apiKey;

    /**
     * @var integer $apiVersion
     */
    protected $apiVersion;

    /**
     * @var string $urlTemplate
     */
    protected $urlTemplate;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $hostEntities;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $changes;

    /**
     * @var integer $state
     */
    protected $state;

    /**
     * @var \DateTime $lastUpdate
     */
    protected $lastUpdate;
    
    /**
     * @var string Default template for URI
     */
    protected $defaultUrlTemplate = '[[HOST]]/api/sync/v[[API_VERSION]]/json/[[DATA_SOURCE]]/[[INDEX_DATA]]?apikey=[[API_KEY]]';

    public function __construct()
    {
        $this->hostEntities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->changes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set host
     *
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Get host
     *
     * @return string $host
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return boolean $active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set apiKey
     *
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Get apiKey
     *
     * @return string $apiKey
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set apiVersion
     *
     * @param integer $apiVersion
     */
    public function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;
    }

    /**
     * Get apiVersion
     *
     * @return integer $apiVersion
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Set urlTemplate
     *
     * @param string $urlTemplate
     */
    public function setUrlTemplate($urlTemplate)
    {
        if ($urlTemplate == $this->defaultUrlTemplate) {
            $urlTemplate = null;
        }
        $this->urlTemplate = $urlTemplate;
    }

    /**
     * Get urlTemplate
     *
     * @return string $urlTemplate
     */
    public function getUrlTemplate()
    {
        if (empty($this->urlTemplate)) {
            return $this->defaultUrlTemplate;
        }
        return $this->urlTemplate;
    }

    /**
     * Add hostEntity
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\HostEntity $hostEntity
     */
    public function addHostEntity(\Cx\Core_Modules\Sync\Model\Entity\HostEntity $hostEntity)
    {
        $this->hostEntities[] = $hostEntity;
    }

    /**
     * Remove hostEntities
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\HostEntity $hostEntities
     */
    public function removeHostEntity(\Cx\Core_Modules\Sync\Model\Entity\HostEntity $hostEntities)
    {
        $this->hostEntities->removeElement($hostEntities);
    }

    /**
     * Get hostEntities
     *
     * @return \Doctrine\Common\Collections\Collection $hostEntities
     */
    public function getHostEntities()
    {
        return $this->hostEntities;
    }

    /**
     * Set hostEntities
     *
     * @param array $hostEntities
     */
    public function setHostEntities($hostEntities)
    {
        $this->hostEntities = $hostEntities;
    }

    /**
     * Add Change
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Change $change
     */
    public function addChange(\Cx\Core_Modules\Sync\Model\Entity\Change $change)
    {
        $this->changes[] = $change;
    }

    /**
     * Get changes
     *
     * @return Doctrine\Common\Collections\Collection $changes
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Set changes
     *
     * @param array $changes
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;
    }
    
    /**
     * Remove Change
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Change $change
     */
    public function removeChange($change)
    {
        $this->changes->removeElement($change);
    }
    
    /**
     * Set state
     *
     * @param integer $state
     */
    public function setState($state)
    {
        $this->state = $state;
        $this->setLastUpdate(
            $this->getComponent('DateTime')->createDateTimeForDb()
        );
    }

    /**
     * Get state
     *
     * @return integer $state
     */
    public function getState()
    {
        if (
            $this->state == 1 &&
            $this->getLastUpdate() < $this->getComponent(
                'DateTime'
            )->createDateTimeForDb('5 minutes ago')
        ) {
            $this->state = 0;
        }
        return $this->state;
    }

    /**
     * Set lastUpdated
     *
     * @param \DateTime $lastUpdated
     */
    public function setLastUpdate($lastUpdate) {
        $this->lastUpdate = $lastUpdate;
    }

    /**
     * Get lastUpdated
     *
     * @return \DateTime $lastUpdated
     */
    public function getLastUpdate() {
        return $this->lastUpdate;
    }
    
    /**
     * Returns the URL for pushing data to this host
     * @param string $dataSourceName DataSource name to get the URL for
     * @param array $entityIndexData (optional) Field=>Value-type array with entity index data
     * @return string URL to push to
     */
    public function getToUri($dataSourceName, $entityIndexData = array()) {
        $indexData = implode('/', $entityIndexData);
        
        //<domain>(/<offset>)/api/sync/<apiVersion>/<outputModule>/<dataSource>/<parameters>[(?apikey=<apikey>(&<options>))|?<options>]
        //<domain>(/<offset>)/api/sync/<apiVersion>/<outputModule>/[[DATA_SOURCE]]/[[INDEX_DATA]][(?apikey=[[API_KEY]](&<options>))|?<options>]
        $uri = $this->getUrlTemplate();
        $uri = str_replace('[[HOST]]', $this->getHost(), $uri);
        $uri = str_replace('[[API_VERSION]]', $this->getApiVersion(), $uri);
        $uri = str_replace('[[DATA_SOURCE]]', $dataSourceName, $uri);
        $uri = str_replace('[[API_KEY]]', $this->getApiKey(), $uri);
        $uri = str_replace('[[INDEX_DATA]]', $indexData, $uri);
        return $uri;
    }
    
    public function handleChange($change) {
        // do I sync this entity?
        if (!$this->getActive()) {
            //echo "Host not active!\n";
            return true;
        }
        
        $handle = false;
        foreach ($change->getOriginSync()->getHostEntitiesIncludingLegacy(false) as $hostEntity) {
            if ($hostEntity['host'] != $this) {
                continue;
            }
            if (
                $hostEntity['entityId'] != '*' &&
                $hostEntity['entityId'] != current($change->getOriginEntityIndexData())
            ) {
                continue;
            }
            $handle = true;
            break;
        }
        
        // no: did I before?
        if (!$handle) {
            // no: return;
            $in_array = false;
            foreach ($change->getHosts() as $host) {
                if ($host === $this) {
                    $in_array = true;
                    break;
                }
            }
            if (!$in_array) {
                return true;
            }
            // yes: use delete changeset
            $mode = 'delete';
            $handle = $change->setMode('delete');
        
        // yes: use "normal" changeset
        } else {
            $mode = 'forward';
            $handle = $change->setMode('forward');
        }
        if (!$handle) {
            // change does not need to be applied for selected mode
            //echo "Ignoring change of type " . $change->getEventType() . " due to mode " . $mode . "!\n";
            return true;
        }
        //echo "Handling change of type " . $change->getEventType() . " due to mode " . $mode . "!\n";
        
        // replace IDs
        // @todo: This is currently done on remote side
        
        //echo 'Sending change #' . $change->getId() . "\n";
        return $this->sendRequest(
            $change->getContents(),
            $change->getEntityIndexData(),
            $change->getEventType(),
            $change->getSync()
        );
    }
    
    protected function sendRequest($content, $entityIndexData, $eventType, $sync) {
        $method = strtoupper($eventType);
        $url = $this->getToUri(
            $sync->getDataAccess()->getName(),
            $entityIndexData
        );
        
        $config = array(
        );
        $request = new \HTTP_Request2($url, $method, $config);
        $refUrl = \Cx\Core\Routing\Url::fromDocumentRoot();
        $refUrl->setMode('backend');
        $request->setHeader('Referrer', $refUrl->toString());
        $request->setHeader('connection', 'close');
        $request->setBody(http_build_query($content, null, '&'));
        $request->setConfig(array(
            'follow_redirects' => true,
            'strict_redirects' => true,
        ));
        
        $response = $request->send();
        var_dump($response->getStatus());
        echo 'Pushed to ' . $url . ' with method ' . $method . ', body was: ' . http_build_query($content) . "\n";
        echo '<pre>' . $response->getBody() . "</pre>\n\n";
        
        return $response->getStatus() == 200;
    }
    
    public function isLocked() {
        $em = $this->cx->getDb()->getEntityManager();
        $hostRepo = $em->getRepository(get_class($this));
        $me = $hostRepo->find($this->getId());
        $this->setState($me->getState());
        return $this->getState() == 1;
    }
    
    public function lock() {
        if ($this->isLocked()) {
            return false;
        }
        $this->setState(1);
        $em = $this->cx->getDb()->getEntityManager();
        $em->persist($this);
        $em->flush();
        return true;
    }
    
    public function removeLock() {
        $this->setState(0);
        $em = $this->cx->getDb()->getEntityManager();
        $em->persist($this);
        $em->flush();
    }
    
    public function disable() {
        $this->setState(2);
        $em = $this->cx->getDb()->getEntityManager();
        $em->persist($this);
        $em->flush();
    }
}

