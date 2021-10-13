<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Form\Extension\Core\Type\{ PasswordType, RepeatedType };
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Mollie\Api\MollieApiClient;
use App\Form\MemberDetailsType;
use DateTime;
use App\Entity\{ Member, MembershipApplication, MemberDetailsRevision, Event};
use App\Form\MembershipApplicationType;
use Symfony\Component\Validator\Constraints\IsTrue;

class MemberController extends AbstractController {

    public function memberAcceptPersonalDetails(Request $request): Response {
        $member = $this->getUser();
        $form = $this->createFormBuilder($member)
            ->add('acceptUsePersonalInformation', null, [
                'label' => 'Ik ga ermee akkoord dat ROOD mijn persoonsgegevens opslaat in haar ledenadministratie, zoals beschreven in het <a href="https://roodjongindesp.nl/privacybeleid">privacybeleid</a>.',
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
        $form = $this->createForm(MembershipApplicationType::class, $member);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($member);
            $em->flush();

            return $this->render('user/apply.html.twig', [
                'success' => true
            ]);
        }

        return $this->render('user/apply.html.twig', [
            'success' => false,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/gegevens", name="member_details")
     */
    public function details(Request $request): Response {
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

        return $this->render('user/details.html.twig', [
            'form' => $form->createView(),
            'success' => $success
        ]);
    }

}
