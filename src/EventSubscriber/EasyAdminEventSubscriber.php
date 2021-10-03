<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Controller\DirectAdminEmailController;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\{
        BeforeCrudActionEvent, BeforeEntityUpdatedEvent,
        AfterEntityPersistedEvent, AfterEntityUpdatedEvent,
        AfterEntityDeletedEvent
};
use \DrewM\MailChimp\MailChimp;
use App\Entity\{ Member, MemberDetailsRevision, Email };

class EasyAdminEventSubscriber implements EventSubscriberInterface {

    private DirectAdminEmailController $emails;
    private EntityManagerInterface $em;
    private $memberDetailRevision;

    public function __construct(EntityManagerInterface $em, DirectAdminEmailController $emails) {
        $this->emails = $emails;
        $this->em = $em;
    }

    public static function getSubscribedEvents() {
        return [
            BeforeCrudActionEvent::class => [['beforeCrud']],
            BeforeEntityUpdatedEvent::class => [['beforeEntityUpdate']],
            AfterEntityUpdatedEvent::class => [['afterEntityUpdate']],
            AfterEntityPersistedEvent::class => [['afterEntityPersist']],
            AfterEntityDeletedEvent::class => [['afterEntityDeleted']]
        ];
    }

    public function beforeCrud(BeforeCrudActionEvent $event) {
        $instance = $event->getAdminContext()->getEntity()->getInstance();

        if ($instance instanceof Member)
            $this->revision = new MemberDetailsRevision($instance, false);
    }

    public function afterEntityPersist(AfterEntityPersistedEvent $event) {
        $instance = $event->getEntityInstance();
        if ($instance instanceof Email) {
            // create email
            $this->emails->createEmail(
                $instance->getDomainName(),
                $instance->getUser(),
                $instance->getPassword()
            );
        }
    }

    public function beforeEntityUpdate(BeforeEntityUpdatedEvent $event) {
        $instance = $event->getEntityInstance();
        if ($instance instanceof Member && $this->revision->hasChanged($instance))
            $this->em->persist($this->revision);
    }

    public function afterEntityUpdate(AfterEntityUpdatedEvent $event) {
        $instance = $event->getEntityInstance();
        if ($instance instanceof Email) {
            if ($instance->getChangePassword()) {
                $this->emails->changePassword(
                    $instance->getDomain(),
                    $instance->getUser(),
                    $instance->getChangePassword()
                );
            }
        }
    }


    public function afterEntityDeleted(AfterEntityUpdatedEvent $event) {
        $instance = $event->getEntityInstance();
        if ($instance instanceof Member) {
            $subscriberHash = MailChimp::subscriberHash($instance->getEmail());
            $mailChimp = new MailChimp('abc123abc123abc123abc123abc123-us1');
        }
    }

}
