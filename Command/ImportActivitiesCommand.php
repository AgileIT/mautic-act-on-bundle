<?php

namespace MauticPlugin\MauticActOnBundle\Command;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use MauticPlugin\MauticActOnBundle\Command\Contacts\Contacts;
use MauticPlugin\MauticActOnBundle\Command\Helper\DateTimeConvertor;
use MauticPlugin\MauticActOnBundle\Command\Helper\Validators;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiCommands;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiUserItemsInteractions;
use MauticPlugin\MauticRecommenderBundle\Entity\EventLogRepository;
use MauticPlugin\MauticRecommenderBundle\Helper\RecommenderHelper;
use MauticPlugin\MauticRecommenderBundle\Helper\SqlQuery;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportActivitiesCommand extends ContainerAwareCommand
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:act-on:import:activities')
            ->setDescription('Import data from JSON Act-On export to Mautic')
            ->addOption(
                '--from',
                '-f',
                InputOption::VALUE_OPTIONAL,
                'Path to location of JSONs'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $translator      = $this->getContainer()->get('translator');
        $dateTimeConvert = new DateTimeConvertor();
        $validators      = new Validators($output, $translator);
        /** @var LeadModel $leadModel */
        $leadModel = $this->getContainer()->get('mautic.lead.model.lead');
        /** @var LeadRepository $leadRepo */
        $leadRepo           = $leadModel->getRepository();
        $eventLogRepository = $leadModel->getEventLogRepository();

        $dest = $input->getOption('from');


        try {
            $validators->checkEmpty($dest);
            $validators->checkJsonExist($dest);
        } catch (\Exception $e) {
            return;
        }

        $actions = [
            's'  => 'email.sent',
            'os' => 'email.read',
            'fs' => 'form.submitted',
        ];

        $activities = \JsonMachine\JsonMachine::fromFile($dest);
        foreach ($activities as $actOnId => $activity) {
            if (!isset($activity['activitylist'])) {
                echo 'activitylist dont';
                //die();
                continue;
            }
            $lead = $leadRepo->getLeadsByFieldValue('act_on_id', $actOnId);
            $lead = current($lead);
            if (!$lead || !$lead instanceof Lead || !$lead->getId()) {
                // @todo Add audit log
                die('Lead not exists');
            }

            $logs = [];
            $q    = $eventLogRepository->createQueryBuilder($eventLogRepository->getTableAlias())->select(
                'lel.userName',
                'lel.action',
                'lel.dateAdded'
            );
            $q->andWhere($q->expr()->eq('lel.lead', ':contactId'))
                ->andWhere($q->expr()->eq('lel.bundle', ':bundle'))
                ->setParameter('contactId', $lead->getId())
                ->setParameter('bundle', 'MauticActOnBundle');
            $alls     = $q->getQuery()->getArrayResult();
            $allHashs = [];
            foreach ($alls as $all) {
                $values = array_values($all);
                $values[] = $lead->getId();
                $allHashs[md5(\GuzzleHttp\json_encode($values))] = $all;
            }
            foreach ($activity['activitylist'] as $act) {
                if (isset($act['Verb']) && in_array($act['Verb'], array_keys($actions))) {
                    $action = $actions[$act['Verb']];

                    $values = [];
                    $values[] = $act['id'];
                    $values[] = $action;
                    $values[] = $dateTimeConvert->getDateTimeFromTime($act['WhenMillis']);
                    $values[] = $lead->getId();
                    $hash = md5(\GuzzleHttp\json_encode($values));
                    if (isset($allHashs[$hash])) {
                        continue;
                    }

                    $log = new LeadEventLog();
                    $log->setLead($lead)
                        ->setBundle('MauticActOnBundle')
                        ->setAction($action)
                        ->setObject('activities')
                        ->setDateAdded($dateTimeConvert->getDateTimeFromTime($act['WhenMillis']))
                        ->setUserName($act['id'])
                        ->setProperties(
                            $act
                        );
                    $logs[] = $log;
                }
                $eventLogRepository->saveEntities($logs);
            }
            die();
        }
    }

    private function checkJsonExist($dest, $output)
    {
        $translator = $this->getContainer()->get('translator');

        if (!file_exists($dest) || !is_readable($dest)) {
            $output->writeln(
                sprintf(
                    '<error>ERROR:</error> <info>'.$translator->trans(
                        'mautic.plugin.act.on.command.dest.not.exist',
                        ['%path%' => $dest]
                    )
                )
            );

            return false;
        }

        return true;
    }
}
