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
