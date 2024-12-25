<?php

namespace MauticPlugin\MauticRoundRobinBundle\Event;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\EventCollector\ActionCollector;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RoundRobinOwnersAction extends ActionCollector
{
    /**
     * Unique internal name of this action.
     * Must be unique across all Mautic actions.
     */
    public function getName()
    {
        return 'round_robin_assign_owners';
    }

    /**
     * Visible label for this action in the campaign builder.
     */
    public function getLabel()
    {
        return 'Round Robin Owners';
    }

    /**
     * The event type (e.g. “action”).
     */
    public function getEventType()
    {
        return 'action';
    }

    /**
     * A short description for the action (optional).
     */
    public function getDescription()
    {
        return 'Assign owners in a round-robin fashion and optionally send notifications.';
    }

    /**
     * Icon for the campaign builder UI (optional).
     */
    public function getIcon()
    {
        return 'fa-user-friends';  // pick an icon from FontAwesome
    }

    /**
     * Build the form that appears in the campaign builder action configuration.
     */
    public function buildForm(FormBuilderInterface $builder, array $options, TranslatorInterface $translator, $action = null, $entity = null)
    {
        // 1. Build a multiple-select of Mautic users
        $builder->add(
            'assigned_owners',
            'user_list',  // Mautic provides some user-list fields, or you can use ‘choice’ type with data from the user repo
            [
                'label'      => 'Select Owners to Rotate',
                'multiple'   => true,
                'required'   => true,
                'expanded'   => false,
            ]
        );

        // 2. Toggle to send or not send notifications
        $builder->add(
            'send_notification',
            'choice',
            [
                'choices'     => [
                    1 => 'Yes',
                    0 => 'No',
                ],
                'label'       => 'Send email to newly assigned owner?',
                'placeholder' => false,
                'required'    => true,
                'expanded'    => true,
            ]
        );
    }

    /**
     * Called by the campaign execution engine for each contact in the campaign that hits this action.
     */
    public function execute(CampaignExecutionEvent $event, $configuration)
    {
        // The contact we’re operating on
        $lead = $event->getLead();

        // Array of owners (user IDs) from the config
        $ownerIds = (!empty($configuration['assigned_owners'])) 
            ? (array) $configuration['assigned_owners'] 
            : [];

        if (empty($ownerIds)) {
            // no owners selected; do nothing
            return;
        }

        // 1. Determine which owner is next in the round-robin sequence
        //    We’ll keep it simple by storing an index in a custom local store or a file. 
        //    Alternatively, you could store it in a plugin DB table. 
        //    The key requirement: do NOT use Mautic contact fields or a webhook.

        // This is a simplistic approach: you might keep track in a static property or 
        // retrieve from your plugin’s custom DB. For demonstration:
        static $currentIndex = 0;

        // Pick the owner
        $ownerId = $ownerIds[$currentIndex];
        // Move index to the next
        $currentIndex++;
        if ($currentIndex >= count($ownerIds)) {
            $currentIndex = 0;
        }

        // 2. Assign to the contact
        /** @var \Mautic\UserBundle\Entity\UserRepository $userRepo */
        $userRepo = $event->getCampaignModel()->getEntityManager()->getRepository(User::class);
        /** @var User $owner */
        $owner = $userRepo->find($ownerId);
        if (!$owner) {
            // Owner no longer exists; skip
            return;
        }

        $lead->setOwner($owner);

        // 3. Persist changes to the contact in the DB
        $em = $event->getCampaignModel()->getEntityManager();
        $em->persist($lead);
        $em->flush();

        // 4. If “send_notification” is set to “Yes”, send an email
        if (!empty($configuration['send_notification'])) {
            if ($configuration['send_notification'] == 1) {
                // Use Mautic’s mail helper / transport to send an email
                $this->sendOwnerNotification($owner, $lead);
            }
        }

        // Mark the campaign event as successful
        $event->setResult(true);
    }

    /**
     * Example function to send an email to the newly assigned owner.
     */
    protected function sendOwnerNotification(User $owner, $lead)
    {
        // You can use Mautic’s internal MailHelper or SwiftMailer to send a simple email
        // For example:
        $toEmail = $owner->getEmail();

        if (!$toEmail) {
            return;
        }

        // Construct a simple message 
        $subject = 'New contact assigned: ' . $lead->getEmail();
        $body    = sprintf(
            "Hello %s,\n\nA new contact (%s) has been assigned to you in Mautic.\n\nRegards,\nMautic System",
            $owner->getFirstName(),
            $lead->getEmail()
        );

        // If you have MauticFactory or mail helper injection, you can do something like:
        // $this->mailer->sendPlainText($toEmail, $subject, $body);
        // or build a more robust email with tokens, etc.

        // As a placeholder, you might do:
        $transport = \Swift_SmtpTransport::newInstance();
        $mailer    = \Swift_Mailer::newInstance($transport);

        $message = (new \Swift_Message())
            ->setSubject($subject)
            ->setFrom(['no-reply@mautic.local' => 'Mautic'])
            ->setTo($toEmail)
            ->setBody($body, 'text/plain');

        $mailer->send($message);
    }
}
