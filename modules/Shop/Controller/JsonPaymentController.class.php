<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * JsonController for Payment view
 *
 * @copyright  Cloudrexx AG
 * @author     Michael Ritter <michael.ritter@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_shop
 * @version    5.0.0
 */

namespace Cx\Modules\Shop\Controller;

/**
 * JsonController for Payment view
 *
 * @copyright  Cloudrexx AG
 * @author     Michael Ritter <michael.ritter@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_shop
 * @version    5.0.0
 */
class JsonPaymentController
    extends \Cx\Core\Core\Model\Entity\Controller
    implements \Cx\Core\Json\JsonAdapter
{
    /**
     * @var array messages from this controller
     */
    protected $messages;

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName()
    {
        return 'Payment';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'getZoneDropdown',
            'storeZone',
        );
    }

    /**
     * Returns all messages as string
     *
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString()
    {
        return implode('<br />', $this->messages);
    }

    /**
     * Returns default permission as object
     *
     * @return \Cx\Core_Modules\Access\Model\Entity\Permission
     */
    public function getDefaultPermissions()
    {
        $permission = new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array('http', 'https'),
            array('get', 'post'),
            true,
            array()
        );

        return $permission;
    }

    /**
     * Returns the dropdown to select a Zone
     *
     * @param array $params VG table-parse or formfield callback arguments
     * @return \Cx\Core\Html\Model\Entity\HtmlElement Zone select field
     */
    public function getZoneDropdown($params)
    {
        // overview
        if (isset($params['rows']) && isset($params['rows']['id'])) {
            $paymentId = $params['rows']['id'];
            $inputName = 'zones-' . $paymentId;

        // edit
        } else if (isset($params['id'])) {
            $paymentId = $params['id'];
            $inputName = 'zones';

        // new
        } else {
            $paymentId = null;
            $inputName = 'zones';
        }

        $em = $this->cx->getDb()->getEntityManager();
        $zoneRepo = $em->getRepository('Cx\Modules\Shop\Model\Entity\Zone');
        $paymentRepo = $em->getRepository('Cx\Modules\Shop\Model\Entity\Payment');

        if ($paymentId) {
            $payment = $paymentRepo->find($paymentId);
            $selectedZone = $payment->getZones()->first();
            $selectedZoneId = $selectedZone->getId();
        } else {
            $selectedZoneId = 0;
        }

        $zones = array();
        foreach ($zoneRepo->findAll() as $zone) {
            $zones[$zone->getId()] = (string) $zone;
        }
        $el = new \Cx\Core\Html\Model\Entity\DataElement(
            $inputName,
            $selectedZoneId,
            \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT,
            null,
            $zones
        );
        return $el;
    }

    /**
     * Stores the association between Payment and Zone
     *
     * @param array $params VG storecallback params
     */
    public function storeZone($params) {
        // VG Bug #1: Params are mixed up
        $postedValue = $params['entity']['zones'];
        $entity = $params['postedValue'];

        // get Zone by ID
        $em = $this->cx->getDb()->getEntityManager();
        $zoneRepo = $em->getRepository('Cx\Modules\Shop\Model\Entity\Zone');
        $zone = $zoneRepo->find($postedValue);

        // set $zone as the only Zone to $entity
        $zones = $entity->getZones();
        if (
            count($zones) == 1 &&
            $zones->first()->getId() == $zone->getId()
        ) {
            return;
        }

        // Owning side is Zone!
        foreach ($zones as $zoneToRemove) {
            $entity->removeZone($zoneToRemove);
            $zoneToRemove->removePayment($entity);
            $em->persist($zoneToRemove);
        }
        $zone->addPayment($entity);
        $entity->addZone($zone);
        $em->persist($zone);
        // VG Bug #2: Need to manually flush
        $em->flush();
    }
}
