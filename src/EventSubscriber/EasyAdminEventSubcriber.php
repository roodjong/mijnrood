<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;

class EasyAdminEventSubscriber implements EventSubscriberInterface {

    private AdminContextProvider $provider;

    public function __construct(AdminContextProvider $provider) {
        $this->provider = $provider;
    }

    public static function getSubscribedEvents() {
        return [
            BeforeCrudActionEvent::class => ['before']
        ];
    }

    public function before(BeforeCrudActionEvent $event) {
        dump($provider->getContext()->getEntity()->getInstance());
        exit('e');
    }

}
