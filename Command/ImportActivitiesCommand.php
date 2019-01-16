<?php

namespace MauticPlugin\MauticActOnBundle\Command;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticActOnBundle\Command\Helper\DateTimeConvertor;
use MauticPlugin\MauticActOnBundle\Command\Helper\Validators;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiUserItemsInteractions;
use Monolog\Logger;
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
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Logger $logger */
        $logger = $this->getContainer()->get('monolog.logger.mautic');

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
            'b'  => 'page.hit',
        ];

        $activities = \JsonMachine\JsonMachine::fromFile($dest);
        $stats      = [];
        foreach ($activities as $actOnId => $activity) {
            if (!isset($activity['activitylist'])) {
                continue;
            }
            $counter = 0;
            foreach ($activity['activitylist'] as $act) {
                if (isset($act['Verb']) && in_array($act['Verb'], array_keys($actions))) {
                    $counter++;

                }
            }
            $stats[$actOnId] = $counter;
        }
        $progress = ProgressBarHelper::init($output, count($stats));
        $progress->start();
        $dt         = new DateTimeHelper();
        $activities = \JsonMachine\JsonMachine::fromFile($dest);
        $created    = 0;
        $skipped    = 0;
        $notes      = [];
        foreach ($activities as $actOnId => $activity) {

            if (!isset($activity['activitylist'])) {
                $note    = 'Contact from Act-On '.$actOnId.' don\'t have any activity logs in this batch.';
                $notes[] = $note;
                $logger->log('debug', $note);
                continue;
            }

            $lead = $leadRepo->getLeadsByFieldValue('act_on_id', $actOnId);
            $lead = current($lead);
            if (!$lead || !$lead instanceof Lead || !$lead->getId()) {
                $note    = 'Contact from Act-On '.$actOnId.' not exist in Mautic';
                $notes[] = $note;
                $logger->log('debug', $note);
                continue;
            }

            $logs = [];
            $q    = $eventLogRepository->createQueryBuilder($eventLogRepository->getTableAlias())->select(
                'lel.properties'
            );
            $q->andWhere($q->expr()->eq('lel.lead', ':contactId'))
                ->andWhere($q->expr()->eq('lel.bundle', ':bundle'))
                ->setParameter('contactId', $lead->getId())
                ->setParameter('bundle', 'MauticActOnBundle');
            $alls = array_column($q->getQuery()->getArrayResult(), 'properties');
            foreach ($alls as $all) {
                $allHashs[$this->generateHash($all, $lead)] = 1;
            }
            $progress->advance();
            foreach ($activity['activitylist'] as $key => $act) {
                if (isset($act['Verb']) && in_array($act['Verb'], array_keys($actions))) {
                    $id     = isset($act['id']) ? $act['id'] : $act['What'];
                    $action = $actions[$act['Verb']];
                    // If already exist, skip
                    if (isset($allHashs[$this->generateHash($act, $lead)])) {
                        $skipped++;
                        continue;
                    }

                    $log = new LeadEventLog();
                    $log->setLead($lead)
                        ->setBundle('MauticActOnBundle')
                        ->setAction($action)
                        ->setObject('activities')
                        ->setUserName($id)
                        ->setDateAdded($dateTimeConvert->getDateTimeFromTime($act['WhenMillis']))
                        ->setProperties(
                            $act
                        );
                    $logs[] = $log;
                    $created++;
                }
            }
            $eventLogRepository->saveEntities($logs);
            $entityManager->clear(Lead::class);
            $entityManager->clear(LeadEventLog::class);
        }
        $progress->finish();
        $output->writeln('');
        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>DONE: '.$translator->trans(
                    'mautic.plugin.act.on.command.event.process',
                    ['%created%' => $created, '%skiped%' => $skipped]
                )
            )
        );

        $validators->displayNotes($notes);
    }

    /**
     * @param      $row
     * @param Lead $lead
     *
     * @return string
     */
    private function generateHash($row, Lead $lead)
    {
        $row['lead_id'] = $lead->getId();
        return md5(\GuzzleHttp\json_encode($row, true));
    }
}
