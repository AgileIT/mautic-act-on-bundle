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

class Validators
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Validators constructor.
     *
     * @param OutputInterface     $output
     * @param TranslatorInterface $translator
     */
    public function __construct(OutputInterface $output, TranslatorInterface $translator)
    {

        $this->output     = $output;
        $this->translator = $translator;
    }

    /**
     * @param string $dest
     *
     * @throws \Exception
     */
    public function checkEmpty($dest)
    {
        if (empty($dest)) {
            $result = $this->translator->trans(
                'mautic.plugin.act.on.command.dest.param.empty'
            );
            $this->output->writeln(
                sprintf(
                    '<error>ERROR:</error> <info>'.$result
                )
            );
            throw new \Exception($result);
        }
    }

    /**
     * @param $dest
     *
     * @return bool
     */
    public function checkJsonExist($dest)
    {
        $translator = $this->translator;
        $output     = $this->output;

        if (!file_exists($dest) || !is_readable($dest)) {
            $result = $translator->trans(
                'mautic.plugin.act.on.command.dest.not.exist',
                ['%path%' => $dest]
            );
            $output->writeln(
                sprintf(
                    '<error>ERROR:</error> <info>'.$result
                )
            );
            throw new \Exception($result);
        }
    }

    /**
     * @param array $notes
     */
    public function displayNotes(array $notes)
    {
        $output = $this->output;
        // Notes
        if (!empty($notes)) {
            $output->writeln('');
            $output->writeln('Notes:');
            foreach ($notes as $note) {
                $output->writeln($note);
            }
        }
    }
}
