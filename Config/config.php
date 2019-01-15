<?php

return [
    'name'        => 'Mautic Act-On bundle',
    'description' => '',
    'author'      => 'kuzmany.biz',
    'version'     => '1.0.0',
    'routes'      => [
        'main' => [
            'mautic_acton_email_copy'    => [
                'path'       => '/emails/acton/view/{idHash}',
                'controller' => 'MauticActOnBundle:Email:preview',
            ],
        ],
    ],
    'services'    => [
        'events' => [
            'mautic.plugin.act_on.timeline.subscriber' => [
                'class' => \MauticPlugin\MauticActOnBundle\EventListener\TimelineSubscriber::class,
            ],
        ],
        'models' => [
            'mautic.plugin.act.on.model.contacts' => [
                'class' => \MauticPlugin\MauticActOnBundle\Model\ContactsModel::class,
            ],
        ],
    ],
];
