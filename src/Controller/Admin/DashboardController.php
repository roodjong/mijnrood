<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Dashboard, MenuItem };
use App\Entity\{ Member, SupportMember, MembershipApplication, Division, Email, EmailDomain, Event };
use App\Entity\Membership\MembershipStatus;
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
        $items = [
            MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),

            MenuItem::section('Website')->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('Evenementen', 'fa fa-calendar', Event::class)->setPermission('ROLE_ADMIN'),

            MenuItem::section('Administratie'),
            MenuItem::linkToCrud('Leden', 'fa fa-users', Member::class),
            MenuItem::linkToCrud('Steunleden', 'fa fa-users', SupportMember::class)->setPermission('ROLE_ADMIN'),
        ];

        $membership_applications = MenuItem::linkToCrud('Aanmeldingen', 'fa fa-user-plus', MembershipApplication::class);
        if (!$this->getParameter('app.enableDivisionContactsCanApproveNewMembers')) {
            $membership_applications->setPermission('ROLE_ADMIN');
        }

        array_push($items,
            $membership_applications,
            MenuItem::linkToCrud('Groepen', 'fa fa-building', Division::class)->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('Lidmaatschapstypes', 'fa fa-building', MembershipStatus::class)->setPermission('ROLE_ADMIN'),

            MenuItem::section('Technisch')->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('E-mailadressen', 'fa fa-at', Email::class)->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('E-maildomeinen', 'fa fa-globe', EmailDomain::class)->setPermission('ROLE_ADMIN'),
            MenuItem::section(''),
            MenuItem::linkToRoute('Home', 'fa fa-arrow-left', 'member_home'),
            MenuItem::linkToRoute('Statistieken', 'fa fa-bar-chart', 'admin_statistics')->setPermission('ROLE_ADMIN'),
            MenuItem::linkToLogout('Uitloggen', 'fa fa-lock')
        );

        return $items;
    }
}
