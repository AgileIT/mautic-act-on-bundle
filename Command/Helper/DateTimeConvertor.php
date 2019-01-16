<?php

namespace MauticPlugin\MauticActOnBundle\Command\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\ReportBundle\Model\ExportResponse;
use MauticPlugin\MauticActOnBundle\Model\ContactsModel;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiCommands;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiUserItemsInteractions;
use MauticPlugin\MauticRecommenderBundle\Entity\EventLogRepository;
use MauticPlugin\MauticRecommenderBundle\Helper\RecommenderHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\TranslatorInterface;

class DateTimeConvertor
{

    /** @var DateTimeHelper  */
    private $dateTimeHelper;

    /**
     * DateTimeConvertor constructor.
     */
    public function __construct()
   {
    $this->dateTimeHelper = new DateTimeHelper();
   }

    /**
     * @param $time
     *
     * @return \DateTime
     */
    public function getDateTimeFromTime($time)
    {
        $dateTime = $this->dateTimeHelper->getDateTime();
        $dateTime->setTimestamp(($time / 1000));
        $this->dateTimeHelper->setDateTime($dateTime);
        return  $this->dateTimeHelper->getDateTime();

    }
}
