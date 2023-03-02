<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Annotation\Route;

class ExternalAuthCheckController extends AbstractController {
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/auth/check_admin", name="auth_check")
     */
    public function home(Request $request): Response {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $response = new Response("OK", 200);
            return $response->send();
        } else {
            $response = new Response("Unauthorized", 401);
            return $response->send();
        }
    }
}
