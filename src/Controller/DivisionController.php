<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\{ Division };
use App\Form\Documents\{ UploadType, NewFolderType, MoveType };

class DivisionController extends AbstractController
{

    /**
     * @Route("/groep/{division<\w+>}", name="divison")
     */
    public function divisionController(Request $requset, $division): Response {
        $member = $this->getUser();
        $division = $this->getDoctrine()->getRepository(Division::class)->findOneBy([
            'name' => $division
        ]);
        if ($division->getContact()->getId() !== $member->getId()) {
            throw $this->createAccessDeniedException("Geen toegang!");
        }
        return $this->render('division/members.html.twig', [
            'members' => $division->getMembers()
        ]);
    }

}
