<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticActOnBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

class Contacts
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var
     */
    protected $actonId;

    /**
     * @var Lead
     */
    protected $lead;

    /*
     * @var \DateTime
     */
    protected $dateAdded;


    public function __construct()
    {
        $this->setDateAdded(new \DateTime());
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('acton_contacts')
            ->setCustomRepositoryClass(ContactsRepository::class)
            ->addId()
            ->addNamedField('actonId', Type::TEXT, 'acton_id', false)
            ->addNamedField('dateAdded', 'datetime', 'date_added');

        $builder->createManyToOne(
            'lead',
            'Mautic\LeadBundle\Entity\Lead'
        )->addJoinColumn('lead_id', 'id', true, false, 'CASCADE')->build();
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('event_log')
            ->addListProperties(
                [
                    'id',
                    'acton_id',
                    'lead_id',
                    'dateAdded',
                ]
            )
            ->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $dateAdded
     *
     * @return EventLog
     */
    public function setDateAdded(\DateTime $dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }


    /**
     * @param Lead $lead
     *
     * @return EventLog
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $actonId
     *
     * @return Contacts
     */
    public function setActonId($actonId)
    {
        $this->actonId = $actonId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getActonId()
    {
        return $this->actonId;
    }


}
