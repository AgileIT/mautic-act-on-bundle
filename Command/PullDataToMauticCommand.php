<?php

namespace MauticPlugin\MauticActOnBundle\Command;

use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
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
            $this->setName('mautic:act-on:import')
            ->setDescription('Import data from JSON Act-On export to Mautic')
           ->addOption(
                '--dest',
                '-d',
                InputOption::VALUE_OPTIONAL,
                'JSON path location of files'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $translator = $this->getContainer()->get('translator');

        $dest = $input->getOption('dest');

        if (empty($dest)) {
            return $output->writeln(
                sprintf(
                    '<error>ERROR:</error> <info>'.$translator->trans(
                        'mautic.plugin.act.on.command.dest.param.empty'
                    )
                )
            );
        }


        $paths = [$dest,
            $dest.DIRECTORY_SEPARATOR.'contactLists.json',
            $dest.DIRECTORY_SEPARATOR.'allEmails.json',
            $dest.DIRECTORY_SEPARATOR.'messageLists.json',
            $dest.DIRECTORY_SEPARATOR.'formLists.json',

        ];
        foreach ($paths as $path) {
            if (!$this->checkJsonExist($path, $output)) {
                return;
            }
        }

    {
        $logs                    = [];
        /** @var EventLogRepository $eventLogRepository */
        $eventLogRepository = $this->getContainer()->get('mautic.lead.repository.lead_event_log');
        /** @var LeadModel $leadModel */
        $leadModel = $this->getContainer()->get('mautic.lead.model.lead');


            $log = new LeadEventLog();
        $log->setLead($leadModel->getEntity(58))
                ->setBundle('MauticActOnBundle')
                ->setAction('email_sent')
                ->setObject('segment')
                ->setObjectId('1')
                ->setProperties(
                    [
                        'object_description' => 'tester',
                    ]
                );
        }

        $eventLogRepository->saveEntity($log);
        die();
       // $eventLogRepository->saveEntities($logs);
        //$eventLogRepository->clear();


        //$json = file_get_contents($paths['2']);
        //$items = \GuzzleHttp\json_decode($json, true);

        $users = \JsonMachine\JsonMachine::fromFile($paths['1']);
  /*      foreach ($users as $name => $user) {
            die(print_r($user));
        }*/

        $users = \JsonMachine\JsonMachine::fromFile($paths['1']);
        $i=0;
        $keys = [];
        foreach ($users as $name => $user) {
            if (in_array($name, ['headers'])) {
                foreach ($user as $u) {
                $keys[] = $u;
                }
            }else{
                break;
            }
        }
        $users = \JsonMachine\JsonMachine::fromFile($paths['1'], '/data');



        $contacts = [];
        $i = 0;
        foreach ($users as $name => $user) {
          $contact   = array_combine($keys, $user);
            if($i == 1)
            die(print_r($contact));
            $contacts[] =  $contact['ContactID'];
    $i++;
            if ($i > 1000) {
                die(print_r($contacts));
            }
            // just process $user as usual
        }
        echo $i;
        die();
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
        return;


            if (empty($file)) {
                return $output->writeln(
                    sprintf(
                        '<error>ERROR:</error> <info>'.$translator->trans(
                            'mautic.plugin.recommender.command.file.required'
                        )
                    )
                );
            }

            $json = file_get_contents($file);
            if (empty($json)) {
                return $output->writeln(
                    sprintf(
                        '<error>ERROR:</error> <info>'.$translator->trans(
                            'mautic.plugin.recommender.command.file.fail',
                            ['%file' => $file]
                        )
                    )
                );
            }
            $items = \GuzzleHttp\json_decode($json, true);

            if (empty($items) || ![$items]) {
                return $output->writeln(
                    sprintf(
                        '<error>ERROR:</error> <info>'.$translator->trans(
                            'mautic.plugin.recommender.command.json.fail',
                            ['%file' => $file]
                        )
                    )
                );
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
                        ['%path%'=>$dest]
                    )
                )
            );
            return false;
        }
        return true;
    }
}
