<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Form\Extension\Core\Type\{ PasswordType, RepeatedType };
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Mollie\Api\MollieApiClient;
use App\Form\{ MemberDetailsType, ChangePasswordType };
use DateTime;
use App\Entity\{ Division, WorkGroup, Member, MembershipApplication, MemberDetailsRevision, Event};
use App\Form\MembershipApplicationType;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Form\FormError;

class MemberController extends AbstractController {

    public function __construct(MailerInterface $mailer) {
        $this->mailer = $mailer;
    }

    public function memberAcceptPersonalDetails(Request $request): Response {
        $member = $this->getUser();
        $orgName = $this->getParameter('app.organizationName');
        $privacyPolicyUrl = $this->getParameter('app.privacyPolicyUrl');
        $form = $this->createFormBuilder($member)
            ->add('acceptUsePersonalInformation', null, [
                'label' => "Ik ga ermee akkoord dat $orgName mijn persoonsgegevens opslaat in haar ledenadministratie, zoals beschreven in het <a href='$privacyPolicyUrl'>privacybeleid</a>.",
                'label_html' => true,
                'required' => true,
                'error_bubbling' => true,
                'constraints' => [new IsTrue(['message' => 'Je moet akkoord gaan met het privacybeleid om verder te gaan.'])]
            ])
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('member_home');
        }

        return $this->render('user/privacy-policy.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/", name="member_home")
     */
    public function home(Request $request): Response {
        $member = $this->getUser();
        if (!$member->getAcceptUsePersonalInformation())
            return $this->memberAcceptPersonalDetails($request);

        $events = $this->getDoctrine()->getRepository(Event::class)->createQueryBuilder('e')
            ->where('e.division IS NULL or e.division = ?1')
            ->andWhere('e.timeEnd > ?2')
            ->setParameter(1, $member->getDivision())
            ->setParameter(2, new DateTime())
            ->getQuery()
            ->getResult();

        return $this->render('user/home.html.twig', [
            'events' => $events
        ]);
    }

    /**
     * @Route("/aanmelden", name="member_apply")
     */
    public function apply(Request $request): Response {
        $member = new MembershipApplication();
        $member->setRegistrationTime(new \DateTime());
        $groupCount = $this->getDoctrine()->getRepository(Division::class)->createQueryBuilder('d')
                           ->where('d.canBeSelectedOnApplication = true')
                           ->getQuery()
                           ->getResult();

        $workGroupCount = $this->getDoctrine()->getRepository(WorkGroup::class)->createQueryBuilder('d')
                           ->where('d.canBeSelectedOnApplication = true')
                           ->getQuery()
                           ->getResult();
        $showGroups = true;
        $showWorkGroups = true;
        if (count($groupCount) === 0) {
            $showGroups = false;
        }
        if (count($workGroupCount) === 0) {
            $showWorkGroups = false;
        }
        $form = $this->createForm(MembershipApplicationType::class, $member, [
            'show_groups' => $showGroups,
            'show_work_groups' => $showWorkGroups,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($member);
            $em->flush();

            $noreply = $this->getParameter('app.noReplyAddress');
            $organizationName = $this->getParameter('app.organizationName');
            $divisionEmail = $member->getPreferredDivision()->getEmail();
            $message = (new Email())
            ->subject("Bedankt voor je aanmelding bij $organizationName!")
            ->to(new Address($member->getEmail(), $member->getFullName()))
            ->from(new Address($noreply, $organizationName))
            ->html(
                $this->renderView('email/html/apply.html.twig', ['member' => $member])
            )
            ->text(
                $this->renderView('email/text/apply.txt.twig', ['member' => $member])
            );
            if ($divisionEmail != null) {
                $message->addCc(new Address($divisionEmail, $member->getPreferredDivision()->getName()));
            }
            $this->mailer->send($message);

            return $this->render('user/apply.html.twig', [
                'success' => true
            ]);
        }

        return $this->render('user/apply.html.twig', [
            'success' => false,
            'form' => $form->createView(),
            'showGroups' => $showGroups,
        ]);
    }

    /**
     * @Route("/gegevens", name="member_details")
     */
    public function details(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response {
        $member = $this->getUser();
        if (!$member->getAcceptUsePersonalInformation())
            return $this->memberAcceptPersonalDetails($request);

        $form = $this->createForm(MemberDetailsType::class, $member);
        $revision = new MemberDetailsRevision($member, true);
        $success = false;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($revision);
            $em->flush();
            $success = true;
        }

        $successPassword = false;

        $formPassword = $this->createForm(ChangePasswordType::class);
        $formPassword->handleRequest($request);
        if ($formPassword->isSubmitted() && $formPassword->isValid())
        {
            $valid = $passwordEncoder->isPasswordValid($member, $formPassword['currentPassword']->getData());
            if (!$valid)
            {
                $formPassword->addError(new FormError('Het opgegeven huidige wachtwoord is niet correct.'));
            }
            else
            {
                $passwordHash = $passwordEncoder->encodePassword($member, $formPassword['newPassword']->getData());
                $member->setPasswordHash($passwordHash);
                $this->getDoctrine()->getManager()->flush();
                $successPassword = true;
            }
        }

        return $this->render('user/details.html.twig', [
            'form' => $form->createView(),
            'formPassword' => $formPassword->createView(),
            'success' => $success,
            'successPassword' => $successPassword
        ]);
    }

}
