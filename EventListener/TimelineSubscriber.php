<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticActOnBundle\EventListener;


use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

class TimelineSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => 'onTimelineGenerate',
        ];
    }

    /**
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $translations = [
            's'  => 'email.sent',
            'os'  => 'email.read',
            'fs'  => 'form.submitted',
        ];

        $eventTypes = [
            'email.sent'  => 'mautic.plugin.act.on.timeline.s',
            'email.read' => 'mautic.plugin.act.on.timeline.os',
            'form.submitted' => 'mautic.plugin.act.on.timeline.fs',
        ];

        $eventIcons = [
            'email.sent'  => 'fa-envelope',
            'email.read' => 'fa-envelope-o',
            'form.submitted' => 'fa-pencil-square-o',
        ];

        /** @var LeadEventLogRepository $eventLogRepo */
        $eventLogRepo = $this->em->getRepository('MauticLeadBundle:LeadEventLog');
        foreach ($eventTypes as $type => $label) {
            $name = $this->translator->trans($label);
            $event->addEventType($type, $name);
            $logs         = $eventLogRepo->getEvents(
                $event->getLead(),
                'MauticActOnBundle',
                'activities',
                $type,
                $event->getQueryOptions()
            );
            if (isset($logs['results'])) {
                foreach ($logs['results'] as $log) {
                    $properties    = \GuzzleHttp\json_decode($log['properties'], true);
                    $eventTypeKey  = $log['action'];
                    $eventTypeName = $this->translator->trans($eventTypes[$eventTypeKey]);
                    $eventLabel    = $properties['What'];
                    $eventLabel = [
                        'label' => $eventLabel,
                        'href'  => $this->router->generate(
                            'mautic_acton_email_copy',
                            ['idHash' => $properties['id']]
                        ),
                        'isExternal' => true,
                    ];

                    $event->addEvent(
                        [
                            'event'           => $eventTypeKey,
                            'eventId'         => $eventTypeKey.$log['id'],
                            'eventType'       => $eventTypeName,
                            'eventLabel'      => $eventLabel,
                            'timestamp'       => $log['date_added'],
                            'icon'            => $eventIcons[$eventTypeKey],
                            'extra'           => $log,
                            'contactId'       => $log['lead_id'],
                        ]
                    );
                }
            }
        }


    }


}
