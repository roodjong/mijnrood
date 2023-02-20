<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Form\Admin\ContributionPaymentType;
use App\Form\Contribution\ContributionPeriodType;


use Doctrine\ORM\QueryBuilder;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\{EntityDto, SearchDto};
use EasyCorp\Bundle\EasyAdminBundle\Field\{ Field, IdField, BooleanField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, EmailField, AssociationField, MoneyField };
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Filters, Actions, Action };
use EasyCorp\Bundle\EasyAdminBundle\Filter\{ ChoiceFilter, EntityFilter };
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;

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
	    ->setDefaultSort(['id' => 'ASC'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('division'))
            ->add(EntityFilter::new('currentMembershipStatus'))
        ;
    }

    public function configureActions(Actions $actions): Actions {
        $action = Action::new('export', 'Exporteren', 'fa fa-file-excel')
            ->linkToCrudAction('export')
            ->setCssClass('btn btn-secondary')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $action);
    }

    public function export(AdminContext $adminContext)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Lidnr.');
        $sheet->setCellValue('B1', 'Voornaam');
        $sheet->setCellValue('C1', 'Tussenvoegsel');
        $sheet->setCellValue('D1', 'Achternaam');
        $sheet->setCellValue('E1', 'Geboortedatum');
        $sheet->setCellValue('F1', 'Inschrijfdataum');
        $sheet->setCellValue('G1', 'Afdeling');
        $sheet->setCellValue('H1', 'E-mailadres');
        $sheet->setCellValue('I1', 'Telefoonnr.');
        $sheet->setCellValue('J1', 'Adres');
        $sheet->setCellValue('K1', 'Plaats');
        $sheet->setCellValue('L1', 'Postcode');
        $sheet->setCellValue('M1', 'Landcode');
        $sheet->setCellValue('N1', 'Lidmaatschapsstatus');
        $sheet->setCellValue('O1', 'IBAN');
        $sheet->setCellValue('P1', 'Contributiebedrag');
        $sheet->setCellValue('Q1', 'Betaalperiode');
        $sheet->setCellValue('R1', 'Betaald');
        $sheet->setCellValue('S1', 'Mollie CID');
        $sheet->setCellValue('T1', 'Mollie SID');
        $sheet->setCellValue('U1', 'Privacybeleid geaccepteerd');

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
            $membershipStatus = "";
            if ($member->getCurrentMembershipStatus() !== null) {
                $membershipStatus = $member->getCurrentMembershipStatus()->getName();
            }
            $sheet->setCellValue('A'. $i, $member->getId());
            $sheet->setCellValue('B'. $i, $member->getFirstName());
            $sheet->setCellValue('C'. $i, $member->getMiddleName());
            $sheet->setCellValue('D'. $i, $member->getLastName());

            $sheet->setCellValue('E'. $i, $member->getDateOfBirth() ? Date::PHPToExcel($member->getDateOfBirth()) : '');
            $sheet->getStyle('E'. $i)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

            $sheet->setCellValue('F'. $i, $member->getRegistrationTime() ? Date::PHPToExcel($member->getRegistrationTime()): '');
            $sheet->getStyle('G'. $i)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

            $sheet->setCellValue('G'. $i, $member->getDivision() ? $member->getDivision()->getName() : '');
            $sheet->setCellValue('H'. $i, $member->getEmail());
            $sheet->setCellValue('I'. $i, $member->getPhone());
            $sheet->setCellValue('J'. $i, $member->getAddress());
            $sheet->setCellValue('K'. $i, $member->getCity());
            $sheet->setCellValue('L'. $i, $member->getPostCode());
            $sheet->setCellValue('M'. $i, $member->getCountry());
            $sheet->setCellValue('N'. $i, $membershipStatus);
            $sheet->setCellValue('O'. $i, $member->getIBAN());
            $sheet->setCellValue('P'. $i, $member->getContributionPerPeriodInEuros());
            $sheet->setCellValue('Q'. $i, $contributionPeriodNames[$member->getContributionPeriod()]);
            $sheet->setCellValue('R'. $i, $member->isContributionCompleted($now) ? 'Ja' : 'Nee');
            $sheet->setCellValue('S'. $i, $member->getMollieCustomerId());
            $sheet->setCellValue('T'. $i, $member->getMollieSubscriptionId());
            $sheet->setCellValue('U'. $i, $member->getAcceptUsePersonalInformation() ? 'Ja' : 'Nee');

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
        $fields = [
            IdField::new('id', 'Lidnummer')
                ->setRequired(false)
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Wordt automatisch bepaald']]),

            TextField::new('firstName', 'Voornaam'),
            TextField::new('middleName', 'Tussenvoegsel')->setRequired(false),
            TextField::new('lastName', 'Achternaam'),
            DateField::new('dateOfBirth', 'Geboortedatum')
                ->hideOnIndex(),
            DateField::new('registrationTime', 'Inschrijfdatum')
                ->setFormat(DateTimeField::FORMAT_SHORT)
                ->hideOnIndex(),
            AssociationField::new('workGroups', 'Werkgroepen'),
        ];

        if (in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
            $fields[] = AssociationField::new('currentMembershipStatus', 'Lidmaatschapstype');
            $fields[] = AssociationField::new('division', 'Afdeling');
            $fields[] = BooleanField::new('isAdmin', 'Toegang tot administratie')
                ->hideOnIndex();
        }
        array_push($fields,
            FormField::addPanel('Contactinformatie'),
            EmailField::new('email', 'E-mailadres'),
            TextField::new('phone', 'Telefoonnummer'),
            TextField::new('address', 'Adres')->hideOnIndex(),
            TextField::new('city', 'Plaats'),
            TextField::new('postCode', 'Postcode')->hideOnIndex(),
            TextField::new('country', 'Landcode')
                ->hideOnIndex()
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Twee-letterige landcode']]),

            FormField::addPanel('Contributie'),
            TextField::new('iban', 'IBAN-rekeningnummer')->hideOnIndex(),
            Field::new('contributionPeriod', 'Betalingsperiode')
                ->setFormType(ContributionPeriodType::class)
                ->hideOnIndex(),
            MoneyField::new('contributionPerPeriodInCents', 'Bedrag')
                ->setCurrency('EUR')
                ->hideOnIndex(),
            CollectionField::new('contributionPayments', 'Betalingen')
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
