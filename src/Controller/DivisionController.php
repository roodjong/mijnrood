<?php

namespace App\Controller;

use DateTime;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\{ Division };
use App\Form\Documents\{ UploadType, NewFolderType, MoveType };

class DivisionController extends AbstractController
{

    /**
     * @Route("/groep/{division}", name="division")
     */
    public function divisionController(Request $requset, $division): Response {
        $member = $this->getUser();
        $division = $this->getDoctrine()->getRepository(Division::class)->findOneBy([
            'name' => $division
        ]);
        if ($division->getContact()->getId() !== $member->getId() && !$this->getUser()->isAdmin()) {
            throw $this->createAccessDeniedException("Geen toegang!");
        }

        $newMemberDate = new DateTime('NOW -1 month');

        return $this->render('division/members.html.twig', [
            'division' => $division,
            'members' => $division->getMembers(),
            'newMemberDate' => $newMemberDate
        ]);
    }

}
