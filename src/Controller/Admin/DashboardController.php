<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Dashboard, MenuItem };
use App\Entity\{ Member, Division, Email };
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Asset\Packages;

class DashboardController extends AbstractDashboardController
{

    public function __construct(Packages $packages) {
        $this->packages= $packages;
    }

    /**
     * @Route("/admin", name="admin_dashboard")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(): Response
    {
        return $this->render('admin/admin_dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            // the name visible to end users
            ->setTitle('<img style="width: 100px" src="'.$this->packages->getUrl('assets/image/rood-sp.svg').'" alt="ROOD" />')

            // // the path defined in this method is passed to the Twig asset() function
            // ->setFaviconPath('favicon.svg')

            ->setTextDirection('ltr')
        ;
    }

    public function configureCrud(): Crud {
        return Crud::new()
            ->addFormTheme('admin/form_themes/collection_table.html.twig')
        ;
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),

            MenuItem::linkToCrud('Leden', 'fa fa-users', Member::class),
            MenuItem::linkToCrud('Groepen', 'fa fa-building', Division::class),
            MenuItem::linkToCrud('E-mailadressen', 'fa fa-at', Email::class),
            MenuItem::linkToLogout('Uitloggen', 'fa fa-lock')
        ];
    }
}
