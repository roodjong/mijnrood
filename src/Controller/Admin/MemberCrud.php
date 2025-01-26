<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Entity\SupportMember;
use App\Form\Admin\ContributionPaymentType;
use App\Form\Contribution\ContributionPeriodType;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ Field, IdField, BooleanField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, EmailField, AssociationField, MoneyField };
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Filters, Actions, Action };
use EasyCorp\Bundle\EasyAdminBundle\Filter\{ DateTimeFilter, EntityFilter };
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use Mollie\Api\MollieApiClient;

use Symfony\Component\HttpFoundation\{ BinaryFileResponse, ResponseHeaderBag, Response };
use DateTime;

class MemberCrud extends AbstractCrudController
{
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $response = $this->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
            $response->andWhere('entity.division IN (:division)')->setParameter('division', $this->getUser()->getManagedDivisions());
        }

        return $response;
    }

    public static function getEntityFqcn(): string
    {
        return Member::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('lid')
            ->setEntityLabelInPlural('Leden')
            ->setSearchFields(['id', 'firstName', 'lastName', 'email', 'phone', 'city', 'postCode', 'currentMembershipStatus.name', 'dateOfBirth'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('division', 'Afdeling'))
            ->add(EntityFilter::new('currentMembershipStatus', 'Lidmaatschapstype'))
            ->add(DateTimeFilter::new('dateOfBirth', 'GeboorteDatum'));
    }

    public function configureActions(Actions $actions): Actions {
        $action = Action::new('export', 'Exporteren', 'fa fa-file-excel')
            ->linkToCrudAction('export')
            ->setCssClass('btn btn-secondary')
            ->createAsGlobalAction();
        $actions->add(Crud::PAGE_INDEX, $action);
        $contributionEnabled = $this->getParameter('app.contributionEnabled');
        if ($contributionEnabled) {
            $actionCancelMollie =
                Action::new('cancel', 'Contributiebetaling stopzetten', 'fa fa-dollar-sign')
                    ->linkToCrudAction('cancelMembership')
                    ->setCssClass('btn btn-secondary');
            $actions->add(Crud::PAGE_EDIT, $actionCancelMollie);
        }
        $actionConvertMember = Action::new('convert', 'Lid omzetten naar steunlid', 'fa fa-exchange-alt')
            ->linkToCrudAction('convertMemberToSupportMember');
        $actions
            ->add(Crud::PAGE_EDIT, $actionConvertMember);
        return $actions;
    }

    public function cancelMembership(AdminContext      $adminContext,
                                     MollieApiClient   $mollieApiClient,
                                     AdminUrlGenerator $adminUrlGenerator): Response
    {
        $member = $adminContext->getEntity()->getInstance();
        $redirectUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_EDIT)
            ->setEntityId($member->getId())
            ->generateUrl();

        if ($member->getMollieSubscriptionId() === null)
        {
            $this->addFlash('warning', 'Contributiebetaling is al gestopt.');
            return $this->redirect($redirectUrl);
        }
        $customer = $mollieApiClient->customers->get($member->getMollieCustomerId());
        $subscription = $mollieApiClient->subscriptions->getFor($customer, $member->getMollieSubscriptionId());
        $subscription->cancel();
        $member->setMollieSubscriptionId(null);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        $this->addFlash('success', 'Contributiebetaling succesvol stopgezet.');
        return $this->redirect($redirectUrl);
    }

    public function convertMemberToSupportMember(AdminContext $adminContext, AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager): Response
    {
        /**
         * @var Member
         */
        $member = $adminContext->getEntity()->getInstance();

        // check if current user is allowed and the selected member is allowed to be removed and converted
        $redirectUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_EDIT)
            ->setEntityId($member->getId())
            ->generateUrl();

        $supportMember = new SupportMember();
        $supportMember->setOriginalId($member->getId());
        $supportMember->setFirstName($member->getFirstName());
        $supportMember->setLastName($member->getLastName());
        $supportMember->setEmail($member->getEmail());
        $supportMember->setPhone($member->getPhone());
        $supportMember->setIban($member->getIban());
        $supportMember->setAddress($member->getAddress());
        $supportMember->setCity($member->getCity());
        $supportMember->setPostCode($member->getPostCode());
        $supportMember->setCountry($member->getCountry());
        $supportMember->setDateOfBirth($member->getDateOfBirth());
        $supportMember->setRegistrationTime(new DateTime());
        $supportMember->setOriginalRegistrationTime($member->getRegistrationTime());

        // Should this become a new one, because i assume message is different, don't know where it is set
        $supportMember->setMollieCustomerId($member->getMollieCustomerId());
        $supportMember->setMollieSubscriptionId($member->getMollieSubscriptionId());
        $supportMember->setContributionPeriod($member->getContributionPeriod());
        $supportMember->setContributionPerPeriodInCents($member->getContributionPerPeriodInCents());

        $entityManager->persist($supportMember);
        
        // remove/deactivate member
        $entityManager->remove($member);
        $entityManager->flush();

        // The highlight stays on the member tab.
        $redirectUrl = $adminUrlGenerator
            ->setController(SupportMemberCrud::class)
            ->setAction(Crud::PAGE_EDIT)
            ->setEntityId($supportMember->getId())
            ->generateUrl();

        return $this->redirect($redirectUrl);
    }

    public function export(AdminContext $adminContext): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Lidnr.');
        $sheet->setCellValue('B1', 'Voornaam');
        $sheet->setCellValue('C1', 'Tussenvoegsel');
        $sheet->setCellValue('D1', 'Achternaam');
        $sheet->setCellValue('E1', 'Geboortedatum');
        $sheet->setCellValue('F1', 'Inschrijfdataum');
        $sheet->setCellValue('G1', 'Groep');
        $sheet->setCellValue('H1', 'E-mailadres');
        $sheet->setCellValue('I1', 'Telefoonnr.');
        $sheet->setCellValue('J1', 'Adres');
        $sheet->setCellValue('K1', 'Plaats');
        $sheet->setCellValue('L1', 'Postcode');
        $sheet->setCellValue('M1', 'Landcode');
        $sheet->setCellValue('N1', 'IBAN');
        $sheet->setCellValue('O1', 'Contributiebedrag');
        $sheet->setCellValue('P1', 'Betaalperiode');
        $sheet->setCellValue('Q1', 'Betaald');
        $sheet->setCellValue('R1', 'Mollie CID');
        $sheet->setCellValue('S1', 'Mollie SID');
        $sheet->setCellValue('T1', 'Privacybeleid geaccepteerd');
        $sheet->setCellValue('U1', 'Lidmaatschapstype');

        $contributionPeriodNames = [
            Member::PERIOD_MONTHLY => 'Maandelijks',
            Member::PERIOD_QUARTERLY => 'Per kwartaal',
            Member::PERIOD_ANNUALLY => 'Jaarlijks'
        ];
        $now = new DateTime;
        if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            $members = $this->getDoctrine()->getRepository(Member::class)->findAll();
        }
        else {
            // Just using the division objects should work, but for some reason
            // it gave an error that the PersistentCollection could not be cast
            // to int. So just collecting the id's first fixes this.
            $divisions = [];
            foreach ($this->getUser()->getManagedDivisions() as $division) {
                $divisions[] = $division->getId();
            }

            $members = $this->getDoctrine()->getRepository(Member::class)->findBy(['division' => $divisions]);
        }

        $i = 2;
        foreach ($members as $member)
        {
            $sheet->setCellValue('A'. $i, $member->getId());
            $sheet->setCellValue('B'. $i, $member->getFirstName());
            $sheet->setCellValue('C'. $i, $member->getMiddleName());
            $sheet->setCellValue('D'. $i, $member->getLastName());

            $sheet->setCellValue('E'. $i, $member->getDateOfBirth() ? Date::PHPToExcel($member->getDateOfBirth()) : '');
            $sheet->getStyle('E'. $i)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

            $sheet->setCellValue('F'. $i, $member->getRegistrationTime() ? Date::PHPToExcel($member->getRegistrationTime()): '');
            $sheet->getStyle('F'. $i)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

            $sheet->setCellValue('G'. $i, $member->getDivision() ? $member->getDivision()->getName() : '');
            $sheet->setCellValue('H'. $i, $member->getEmail());
            $sheet->setCellValue('I'. $i, $member->getPhone());
            $sheet->setCellValue('J'. $i, $member->getAddress());
            $sheet->setCellValue('K'. $i, $member->getCity());
            $sheet->setCellValue('L'. $i, $member->getPostCode());
            $sheet->setCellValue('M'. $i, $member->getCountry());
            $sheet->setCellValue('N'. $i, $member->getIBAN());
            $sheet->setCellValue('O'. $i, $member->getContributionPerPeriodInEuros());
            $sheet->setCellValue('P'. $i, $contributionPeriodNames[$member->getContributionPeriod()]);
            $sheet->setCellValue('Q'. $i, $member->isContributionCompleted($now) ? 'Ja' : 'Nee');
            $sheet->setCellValue('R'. $i, $member->getMollieCustomerId());
            $sheet->setCellValue('S'. $i, $member->getMollieSubscriptionId());
            $sheet->setCellValue('T'. $i, $member->getAcceptUsePersonalInformation() ? 'Ja' : 'Nee');
            $sheet->setCellValue('U'. $i, $member->getCurrentMembershipStatus() ? $member->getCurrentMembershipStatus()->getName() : '');

            $i++;
        }


        foreach (range('A','S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = tempnam(sys_get_temp_dir(), 'mnrdexp');

        $writer->save($filename);
        $response = new BinaryFileResponse($filename);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'Export Ledendatabase.xlsx'
        );
        return $response;
    }

    public function configureFields(string $pageName): iterable
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $fields = [
            IdField::new('id', 'Lidnummer')
                ->setDisabled(!$isAdmin)
                ->setRequired(false)
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Wordt automatisch bepaald']]),

            TextField::new('firstName', 'Voornaam')->setDisabled(!$isAdmin),
        ];

        if ($this->getParameter('app.useMiddleName')) {
            $fields[] = TextField::new('middleName', 'Tussenvoegsel')->setDisabled(!$isAdmin)->setRequired(false);
        }

        array_push($fields,
            TextField::new('lastName', 'Achternaam')->setDisabled(!$isAdmin),
            DateField::new('dateOfBirth', 'Geboortedatum'),
            DateField::new('registrationTime', 'Inschrijfdatum')
                ->setFormat(DateTimeField::FORMAT_SHORT)
                ->hideOnIndex(),
            TextField::new('comments', 'Extra informatie'),
            AssociationField::new('currentMembershipStatus', 'Lidmaatschapstype'),
            AssociationField::new('division', 'Afdeling')
        );

        if ($isAdmin) {
            $fields[] = BooleanField::new('isAdmin', 'Toegang tot administratie')
                ->hideOnIndex();
        }
        array_push($fields,
            FormField::addPanel('Contactinformatie'),
            EmailField::new('email', 'E-mailadres')->setDisabled(!$isAdmin),
            TextField::new('phone', 'Telefoonnummer')->setDisabled(!$isAdmin),
            TextField::new('address', 'Adres')->setDisabled(!$isAdmin)->hideOnIndex(),
            TextField::new('city', 'Plaats')->setDisabled(!$isAdmin),
            TextField::new('postCode', 'Postcode')->hideOnIndex()->setDisabled(!$isAdmin),
            TextField::new('country', 'Landcode')->setDisabled(!$isAdmin)
                ->hideOnIndex()
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Twee-letterige landcode']]),

            FormField::addPanel('Contributie'),
            TextField::new('iban', 'IBAN-rekeningnummer')->setDisabled(!$isAdmin)->hideOnIndex(),
            Field::new('contributionPeriod', 'Betalingsperiode')
                ->setDisabled(!$isAdmin)->setFormType(ContributionPeriodType::class)->hideOnIndex(),
            MoneyField::new('contributionPerPeriodInCents', 'Bedrag')
                ->setDisabled(!$isAdmin)->setCurrency('EUR')->hideOnIndex(),
            CollectionField::new('contributionPayments', 'Betalingen')
                ->setDisabled(!$isAdmin)
                ->setEntryIsComplex(false)
                ->setEntryType(ContributionPaymentType::class)
                ->setFormTypeOptions([
                    'block_prefix' => 'collection_table',
                    'entry_options' => ['block_prefix' => 'collection_table_entry'],
                    'allow_add' => true,
                    'allow_delete' => false
                ])
                ->hideOnIndex()
        );
        return $fields;
    }

}
