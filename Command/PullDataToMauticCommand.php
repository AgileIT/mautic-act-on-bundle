<?php

namespace MauticPlugin\MauticActOnBundle\Command;

use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use MauticPlugin\MauticActOnBundle\Command\Contacts\Contacts;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiCommands;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiUserItemsInteractions;
use MauticPlugin\MauticRecommenderBundle\Entity\EventLogRepository;
use MauticPlugin\MauticRecommenderBundle\Helper\RecommenderHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PullDataToMauticCommand extends ContainerAwareCommand
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
        $this->setName('mautic:act-on')
            ->setDescription('Import data from JSON Act-On export to Mautic')
            ->addOption(
                '--action',
                '-a',
                InputOption::VALUE_OPTIONAL,
                'Action'
            )
            ->addOption(
                '--from',
                '-f',
                InputOption::VALUE_OPTIONAL,
                'Path to location of JSON'
            )->addOption(
                '--to',
                '-t',
                InputOption::VALUE_OPTIONAL,
                'Path to location to export data'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $translator = $this->getContainer()->get('translator');

        $action = $input->getOption('action');

        if (empty($action)) {
            return $output->writeln(
                sprintf(
                    '<error>ERROR:</error> <info>'.$translator->trans(
                        'mautic.plugin.act.on.command.action.param.empty'
                    )
                )
            );
        }

        $dest = $input->getOption('from');

        if (empty($dest)) {
            return $output->writeln(
                sprintf(
                    '<error>ERROR:</error> <info>'.$translator->trans(
                        'mautic.plugin.act.on.command.dest.param.empty'
                    )
                )
            );
        }

        $to = $input->getOption('to');

        if (in_array($action, ['convert_to_csv']) && empty($to)) {
            return $output->writeln(
                sprintf(
                    '<error>ERROR:</error> <info>'.$translator->trans(
                        'mautic.plugin.act.on.command.to.param.empty'
                    )
                )
            );
        }


        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');
        $actOnId = $fieldModel->getEntityByAlias('act_on_id');
        if (!$actOnId) {
            return $output->writeln(
                sprintf(
                    '<error>ERROR:</error> <info>'.$translator->trans(
                        'mautic.plugin.act.on.command.contact.field.not.exist'
                    )
                )
            );
        }


        /*$paths = [
            $dest,
            $dest.DIRECTORY_SEPARATOR.'contactLists.json',
            $dest.DIRECTORY_SEPARATOR.'allEmails.json',
            $dest.DIRECTORY_SEPARATOR.'messageLists.json',
            $dest.DIRECTORY_SEPARATOR.'formLists.json',

        ];*/
        if (!$this->checkJsonExist($dest, $output)) {
            return;
        }

        $logs = [];
        /** @var EventLogRepository $eventLogRepository */
        $eventLogRepository = $this->getContainer()->get('mautic.lead.repository.lead_event_log');
        /** @var LeadModel $leadModel */
        $leadModel = $this->getContainer()->get('mautic.lead.model.lead');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /*     $log = new LeadEventLog();
         $log->setLead($leadModel->getEntity(58))
                 ->setBundle('MauticActOnBundle')
                 ->setAction('email_sent')Con
                 ->setObject('segment')
                 ->setObjectId('1')
                 ->setProperties(
                     [
                         'object_description' => 'tester',
                     ]
                 );
         }

         $eventLogRepository->saveEntity($log);
         die();*/
        // $eventLogRepository->saveEntities($logs);
        //$eventLogRepository->clear();


        //$json = file_get_contents($paths['2']);
        //$items = \GuzzleHttp\json_decode($json, true);

        //$users = \JsonMachine\JsonMachine::fromFile($paths['1']);
            /*  foreach ($users as $name => $user)
                  die(print_r($user));
              }*/
        if (in_array($action, ['convert_to_csv'])) {
            return new Contacts($dest, $to, $output, $translator);
        }else{

            $activities = \JsonMachine\JsonMachine::fromFile($dest);
            foreach ($activities as $key=>$activity) {
                echo $key;
                die();
                //die(print_r($activity));
            }
        }
        // die(print_r($items));

        /*  $log = [
           'bundle'    => 'plugin.mauticSocial',
           'object'    => 'monitoring',
           'objectId'  => $monitoring->getId(),
           'action'    => $action,
           'details'   => ['name' => $monitoring->getTitle()],
           'ipAddress' => $this->container->get('mautic.helper.ip_lookup')->getIpAddressFromRequest(),
       ];

       $this->getModel('core.auditLog')->writeToLog($log);*/
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
