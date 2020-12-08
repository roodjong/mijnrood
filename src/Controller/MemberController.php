<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Form\Extension\Core\Type\{ PasswordType, RepeatedType };
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Mollie\Api\MollieApiClient;
use Swift_Mailer, Swift_Message;
use App\Form\MemberDetailsType;
use DateTime;
use App\Entity\{ Member, MemberDetailsRevision, Event};

class MemberController extends AbstractController {

    /**
     * @Route("/", name="member_home")
     */
    public function home(): Response {
        $member = $this->getUser();

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
    public function details(Request $request): Response {
        $member = $this->getUser();

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
