<?php

namespace MauticPlugin\MauticRoundRobinBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class RoundRobinIntegration extends AbstractIntegration
{
    public function getName()
    {
        return 'RoundRobinOwnersIntegration';
    }

    public function getDisplayName()
    {
        return 'Round Robin Owners';
    }

    public function getAuthType()
    {
        // No authentication required
        return 'none';
    }

    public function getRequiredKeyFields()
    {
        return [];
    }

    // If you want to support features like sending leads to an external API, 
    // you can override other methods. For now, this is enough to show up in Plugins.
}
