<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Member;
use App\Form\MemberDetailsType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class MemberController extends AbstractController
{
    /**
     * @Route("/", name="member_home")
     */
    public function home(): Response
    {
        return $this->render('user/home.html.twig', [
        ]);
    }

    /**
     * @Route("/gegevens", name="member_details")
     */
    public function personalInformation(Request $request): Response {
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
