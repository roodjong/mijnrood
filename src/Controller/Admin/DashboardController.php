<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Dashboard, MenuItem };
use App\Entity\{ Member, SupportMember, MembershipApplication, Division, Email, EmailDomain, Event, WorkGroup };
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
     */
    public function index(): Response
    {
        return $this->render('admin/admin_dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        $logoPath = $this->getParameter('app.orgLogo');
        $orgName = $this->getParameter('app.organizationName');
        return Dashboard::new()
            // the name visible to end users
            ->setTitle('<img style="width: 100px" src="'.$this->packages->getUrl($logoPath).'" alt="' . $orgName . '" />')

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

            MenuItem::section('Website'),
            MenuItem::linkToCrud('Evenementen', 'fa fa-calendar', Event::class),

            MenuItem::section('Administratie'),
            MenuItem::linkToCrud('Leden', 'fa fa-users', Member::class),
            MenuItem::linkToCrud('Steunleden', 'fa fa-users', SupportMember::class),
            MenuItem::linkToCrud('Aanmeldingen', 'fa fa-user-plus', MembershipApplication::class),
            MenuItem::linkToCrud('Afdelingen', 'fa fa-building', Division::class),
            MenuItem::linkToCrud('Werkgroepen', 'fa fa-building', WorkGroup::class),

            MenuItem::section('Technisch'),
            MenuItem::linkToCrud('E-mailadressen', 'fa fa-at', Email::class),
            MenuItem::linkToCrud('E-maildomeinen', 'fa fa-globe', EmailDomain::class),

            MenuItem::section(''),
            MenuItem::linkToRoute('Home', 'fa fa-arrow-left', 'member_home'),
            MenuItem::linkToRoute('Statistieken', 'fa fa-bar-chart', 'admin_statistics'),
            MenuItem::linkToLogout('Uitloggen', 'fa fa-lock')
        ];
    }
}
