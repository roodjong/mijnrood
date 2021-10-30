<?php

namespace App\Controller\Admin;

use DateTime;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\SupportMember;
use App\Form\Documents\{ UploadType, NewFolderType, MoveType };

class StatisticsController extends AbstractController
{

    /**
     * @Route("/admin/statistics", name="admin_statistics")
     */
    public function statisticsController(Request $request): Response {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $contributionSupportMembers = $this->getDoctrine()->getRepository(SupportMember::class)->sumByContributionPerMonth() / 100;
        return $this->render('admin/statistics.html.twig',
                             ['contributionSupportMembers' => $contributionSupportMembers]);
    }

}
