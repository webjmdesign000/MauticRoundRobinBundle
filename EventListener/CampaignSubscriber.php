<?php

namespace MauticPlugin\MauticRoundRobinBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\CampaignEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MauticPlugin\MauticRoundRobinBundle\Event\RoundRobinOwnersAction;

class CampaignSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        // Create an instance of your custom action
        $roundRobinAction = new RoundRobinOwnersAction();

        // Add it to the campaign actions list
        $event->addAction($roundRobinAction);
    }
}
