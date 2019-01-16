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
use MauticPlugin\MauticActOnBundle\Command\Helper\DateTimeConvertor;
use MauticPlugin\MauticActOnBundle\Command\Helper\Validators;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiCommands;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiUserItemsInteractions;
use MauticPlugin\MauticRecommenderBundle\Entity\EventLogRepository;
use MauticPlugin\MauticRecommenderBundle\Helper\RecommenderHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateContactImportCSVCommand extends ContainerAwareCommand
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
        $this->setName('mautic:act-on:create:csv')
            ->setDescription('Create CSV with Act-On id for import to contacts')
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
        $dateTimeConvert = new DateTimeConvertor();
        $validators      = new Validators($output, $translator);

        $dest = $input->getOption('from');
        $to = $input->getOption('to');

        try {
            $validators->checkEmpty($dest);
            $validators->checkEmpty($to);
            $validators->checkJsonExist($dest);
        } catch (\Exception $e) {
            return;
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
        new Contacts($dest, $to, $output, $translator);
    }
}
