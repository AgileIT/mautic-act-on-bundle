<?php

namespace MauticPlugin\MauticActOnBundle\Command;

use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\EmailBundle\Entity\Copy;
use MauticPlugin\MauticActOnBundle\Command\Helper\DateTimeConvertor;
use MauticPlugin\MauticActOnBundle\Command\Helper\Validators;
use MauticPlugin\MauticRecommenderBundle\Api\Service\ApiUserItemsInteractions;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportEmailsCommand extends ContainerAwareCommand
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
        $this->setName('mautic:act-on:import:emails')
            ->setDescription('Import data from JSON Act-On export to Mautic')
            ->addOption(
                '--from',
                '-f',
                InputOption::VALUE_OPTIONAL,
                'Path to location of JSON'
            )
            ->addOption(
                '--emails',
                '-em',
                InputOption::VALUE_OPTIONAL,
                'Path to location of JSON'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $translator        = $this->getContainer()->get('translator');
        $validators        = new Validators($output, $translator);
        $dateTimeConvertor = new DateTimeConvertor();
        /** @var Logger $logger */
        $logger = $this->getContainer()->get('monolog.logger.mautic');


        $dest   = $input->getOption('from');
        $emails = $input->getOption('emails');
        try {
            $validators->checkEmpty($dest);
            $validators->checkEmpty($emails);
            $validators->checkJsonExist($dest);
            $validators->checkJsonExist($emails);
        } catch (\Exception $e) {
            return;
        }

        $emails    = \JsonMachine\JsonMachine::fromFile($emails, '/msgresult');
        $allEmails = [];
        foreach ($emails as $email) {
            $allEmails[$email['msg_id']] = $email;
        }

        $emailModel     = $this->getContainer()->get('mautic.email.model.email');
        $copyRepository = $emailModel->getCopyRepository();

        $q      = $copyRepository->createQueryBuilder($copyRepository->getTableAlias())->select('ec.id');
        $all    = $q->getQuery()->getArrayResult();
        $allIds = array_flip(array_column($all, 'id'));

        $emailCopies = \JsonMachine\JsonMachine::fromFile($dest);
        //iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        $i         = 0;
        $created   = 0;
        $skiped    = 0;
        $copies    = [];
        $notes     = [];

        $progress = ProgressBarHelper::init($output, count($allEmails));
        $progress->start();
        foreach ($emailCopies as $emailId => $emailCopy) {

            $progress->advance();
            // already got copy, skip it
            if (isset($allIds[$emailId])) {
                $skiped++;
                continue;
            }

            $copy = new Copy();
            $copy->setId($emailId);
            $copy->setBody($emailCopy);
            $copy->setSubject($allEmails[$emailId]['title']);
            $copy->setDateCreated($dateTimeConvertor->getDateTimeFromTime($allEmails[$emailId]['timestamp']));
            $copies[] = $copy;

            if (++$i % $batchSize === 0) {
                try {
                    $created += count($copies);
                    $copyRepository->saveEntities($copies);
                } catch (\Exception $exception) {
                    $note    = $exception->getMessage();
                    $notes[] = $note;
                    $logger->log($note);
                    continue;
                }
                $copies = [];
            }
        }

        $output->writeln('');
        $output->writeln('');

        $output->writeln(
            sprintf(
                'DONE: <info>'.$translator->trans(
                    'mautic.plugin.act.on.command.copy.process',
                    ['%counter%' => $created, '%skiped%' => $skiped]
                )
            )
        );

        $progress->finish();

        $validators->displayNotes($notes);

    }
}
