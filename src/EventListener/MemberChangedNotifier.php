<?php
namespace App\EventListener;

use App\Entity\Member;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;

#[AsDoctrineListener(event: 'preUpdate',  method: 'preUpdate', entity: Member::class)]
class MemberChangedNotifier
{
    private $orgName;
    private $adminEmail;
    private $noReply;
    private $mailer;

    public function __construct(string $orgName, string $adminEmail, string $noReply, MailerInterface $mailer) {
        $this->orgName = $orgName;
        $this->adminEmail = $adminEmail;
        $this->noReply = $noReply;
        $this->mailer = $mailer;
    }
    // the entity listener methods receive two arguments:
    // the entity instance and the lifecycle event
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        if ($event->hasChangedField('email')) {
            $newEmail = $event->getNewValue('email');
            $oldEmail = $event->getOldValue('email');
                $message = (new Email())
                    ->subject('Emailadres van lid gewijzigd')
                    ->to(new Address($this->adminEmail))
                    ->from(new Address($this->noReply, $this->orgName))
                    ->text("Lid heeft email gewijzigd van $oldEmail naar $newEmail");
                $this->mailer->send($message);
        }
    }
}
