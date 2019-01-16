<?php

namespace MauticPlugin\MauticActOnBundle\Command\Contacts;

use Doctrine\ORM\EntityManager;
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

class Contacts
{
    private $path;

    public function __construct($path, $to, OutputInterface $output, TranslatorInterface $translator)
    {
        $this->path = $path;

        $users = \JsonMachine\JsonMachine::fromFile($this->path);
        $keys  = [];
        foreach ($users as $name => $user) {
            if (in_array($name, ['headers'])) {
                foreach ($user as $u) {
                    $keys[] = $u;
                }
            } else {
                break;
            }
        }
        $users = \JsonMachine\JsonMachine::fromFile($this->path, '/data');

        $i = 0;
        file_put_contents($to, '');
        $handle = fopen($to, 'r+');
        foreach ($users as $name => $user) {
            $contact           = array_combine($keys, $user);
            $mauticContactData = $this->transformActOnDataToMautic($contact);
            // check duplicate, If not exist create new entity
            fputcsv($handle, $mauticContactData);
            $i++;
        }
        fclose($handle);

        return $output->writeln(
                '<info>'.$translator->trans(
                    'mautic.plugin.act.on.command.export.lines',
                    ['%lines%' => $i, '%to%' => $to]
                ).'</info>'
        );
    }

    /**
     * Transform all keys to Mautic keys for cotnact
     *
     * @param $contact
     *
     * @return array
     */
    private function transformActOnDataToMautic($contact)
    {
        $mauticContact = [];
        $allowedFields = [
            'email'     => 'E-mail Address',
            'act_on_id' => '_contact_id_',
            //'firstname' => 'FirstName',
            //'lastname'  => 'LastName',
            //'phone'     => 'Phone',
            //'country'   => 'Country',
        ];
        foreach ($contact as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields                       = array_flip($allowedFields);
                $mauticContact[$fields[$key]] = $value;
            }
        }

        return $mauticContact;
    }
}
