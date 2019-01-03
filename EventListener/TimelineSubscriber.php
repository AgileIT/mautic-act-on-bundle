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


use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\EventListener\TimelineEventLogTrait;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TimelineSubscriber implements EventSubscriberInterface
{
    use TimelineEventLogTrait;

    /**
     * @var LeadEventLogRepository
     */
    private $eventLogRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(LeadEventLogRepository $eventLogRepository, TranslatorInterface $translator)
    {
        $this->eventLogRepository = $eventLogRepository;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE   => 'onTimelineGenerate',
        ];
    }

    /**
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {

        $this->addEvents(
            $event,
            'acton',
            'acton',
            'fa-pie-chart',
            'MauticActOnBundle',
            'segment'
        );
    }


}
