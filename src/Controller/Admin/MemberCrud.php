<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Form\Admin\ContributionPaymentType;
use App\Form\Contribution\ContributionPeriodType;

use Doctrine\ORM\QueryBuilder;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ Field, IdField, BooleanField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, EmailField, AssociationField, MoneyField };
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Filters, Actions, Action };
use EasyCorp\Bundle\EasyAdminBundle\Filter\{ EntityFilter };
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use Symfony\Component\HttpFoundation\{ BinaryFileResponse, ResponseHeaderBag };
use DateTime;

class MemberCrud extends AbstractCrudController
{
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $response = $this->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        if (in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
            return $response;
        }
        $division = $this->getUser()->getDivision();
        $response->andWhere('entity.division = :division')->setParameter('division', $division);
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
            ->setSearchFields(['id', 'firstName', 'lastName', 'email', 'phone', 'city', 'postCode', 'currentMembershipStatus.name'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('division', 'Afdeling'))
            ->add(EntityFilter::new('currentMembershipStatus', 'Lidmaatschapstype'));
    }

    public function configureActions(Actions $actions): Actions {
        $action = Action::new('export', 'Exporteren', 'fa fa-file-excel')
            ->linkToCrudAction('export')
            ->setCssClass('btn btn-secondary')
            ->createAsGlobalAction();

        return $actions->add(Crud::PAGE_INDEX, $action);
    }

    public function export(AdminContext $adminContext): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Lidnr.');
        $sheet->setCellValue('B1', 'Voornaam');
        $sheet->setCellValue('C1', 'Achternaam');
        $sheet->setCellValue('D1', 'Geboortedatum');
        $sheet->setCellValue('E1', 'Inschrijfdataum');
        $sheet->setCellValue('F1', 'Groep');
        $sheet->setCellValue('G1', 'E-mailadres');
        $sheet->setCellValue('H1', 'Telefoonnr.');
        $sheet->setCellValue('I1', 'Adres');
        $sheet->setCellValue('J1', 'Plaats');
        $sheet->setCellValue('K1', 'Postcode');
        $sheet->setCellValue('L1', 'Landcode');
        $sheet->setCellValue('M1', 'IBAN');
        $sheet->setCellValue('N1', 'Contributiebedrag');
        $sheet->setCellValue('O1', 'Betaalperiode');
        $sheet->setCellValue('P1', 'Betaald');
        $sheet->setCellValue('Q1', 'Mollie CID');
        $sheet->setCellValue('R1', 'Mollie SID');
        $sheet->setCellValue('S1', 'Privacybeleid geaccepteerd');

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
            $members = $this->getDoctrine()->getRepository(Member::class)->findBy(['division' => $this->getUser()->getDivision()]);
        }

        $i = 2;
        foreach ($members as $member)
        {
            $sheet->setCellValue('A'. $i, $member->getId());
            $sheet->setCellValue('B'. $i, $member->getFirstName());
            $sheet->setCellValue('C'. $i, $member->getLastName());

            $sheet->setCellValue('D'. $i, $member->getDateOfBirth() ? Date::PHPToExcel($member->getDateOfBirth()) : '');
            $sheet->getStyle('D'. $i)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

            $sheet->setCellValue('E'. $i, $member->getRegistrationTime() ? Date::PHPToExcel($member->getRegistrationTime()): '');
            $sheet->getStyle('E'. $i)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

            $sheet->setCellValue('F'. $i, $member->getDivision() ? $member->getDivision()->getName() : '');
            $sheet->setCellValue('G'. $i, $member->getEmail());
            $sheet->setCellValue('H'. $i, $member->getPhone());
            $sheet->setCellValue('I'. $i, $member->getAddress());
            $sheet->setCellValue('J'. $i, $member->getCity());
            $sheet->setCellValue('K'. $i, $member->getPostCode());
            $sheet->setCellValue('L'. $i, $member->getCountry());
            $sheet->setCellValue('M'. $i, $member->getIBAN());
            $sheet->setCellValue('N'. $i, $member->getContributionPerPeriodInEuros());
            $sheet->setCellValue('O'. $i, $contributionPeriodNames[$member->getContributionPeriod()]);
            $sheet->setCellValue('P'. $i, $member->isContributionCompleted($now) ? 'Ja' : 'Nee');
            $sheet->setCellValue('Q'. $i, $member->getMollieCustomerId());
            $sheet->setCellValue('R'. $i, $member->getMollieSubscriptionId());
            $sheet->setCellValue('S'. $i, $member->getAcceptUsePersonalInformation() ? 'Ja' : 'Nee');

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
            DateField::new('dateOfBirth', 'Geboortedatum')->setDisabled(!$isAdmin)->hideOnIndex(),
            DateField::new('registrationTime', 'Inschrijfdatum')
                ->setFormat(DateTimeField::FORMAT_SHORT)
                ->hideOnIndex(),
            TextField::new('comments', 'Extra informatie'),
        );

        if ($isAdmin) {
            $fields[] = AssociationField::new('currentMembershipStatus', 'Lidmaatschapstype');
            $fields[] = AssociationField::new('division', 'Afdeling');
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
