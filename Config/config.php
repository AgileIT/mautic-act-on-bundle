<?php

return [
    'name'        => 'Mautic Act-On bundle',
    'description' => '',
    'author'      => 'kuzmany.biz',
    'version'     => '1.0.0',

    'services' => [
        'events' => [
            'mautic.plugin.act_on.timeline.subscriber'=>[
                'class'=> \MauticPlugin\MauticActOnBundle\EventListener\TimelineSubscriber::class,
                'arguments' => [
                    'mautic.lead.repository.lead_event_log',
                    'translator',
                ],

            ]
        ],
    ],
];
