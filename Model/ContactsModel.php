<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticActOnBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticRecommenderBundle\Entity\Item;

class ContactsModel extends AbstractCommonModel
{
    /**
     * Get this model's repository.
     *
     * @return \MauticPlugin\MauticRecommenderBundle\Entity\ItemRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticActOnBundle:Contacts');
    }

    public function getContactRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Lead');

    }


}
