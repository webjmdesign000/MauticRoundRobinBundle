<?php

return [
    'name'        => 'MauticRoundRobinBundle',
    'description' => 'Owner round robin for Mautic',
    'version'     => '1.0.0',
    'author'      => 'webjmDesign000',

    'routes' => [
    ],

    'services'   => [
        'events'       => [
            'plugin.round_robin.campaign_subscriber' => [
                'class'     => \MauticPlugin\MauticRoundRobinBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.helper.templating',                 
                ],
            ],
        ],
        'forms'        => [
        ],
        'models'       => [
        ],
        'integrations' => [        
        ],
        'others'       => [          
        ],
        'controllers'  => [
        ],
        'commands'     => [
        ],
    ],
    'parameters' => [
    ],
];
