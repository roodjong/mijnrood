<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Form\Admin\ContributionPaymentType;
use App\Form\Contribution\ContributionPeriodType;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ Field, IdField, BooleanField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, EmailField, AssociationField, MoneyField };
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Filters, Actions, Action };
use EasyCorp\Bundle\EasyAdminBundle\Filter\{ ChoiceFilter, EntityFilter };
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

use Symfony\Component\HttpFoundation\{ BinaryFileResponse, ResponseHeaderBag };
use DateTime;

class MemberCrud extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Member::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('lid')
            ->setEntityLabelInPlural('Leden')
            ->setSearchFields(['id', 'firstName', 'lastName', 'email', 'phone', 'city', 'postCode'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('division'))
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
        $members = $this->getDoctrine()->getRepository(Member::class)->findAll();

        $i = 2;
        foreach ($members as $member)
        {
            $sheet->setCellValue('A'. $i, $member->getId());
            $sheet->setCellValue('B'. $i, $member->getFirstName());
            $sheet->setCellValue('C'. $i, $member->getLastName());
            $sheet->setCellValue('D'. $i, $member->getDateOfBirth() ? Date::PHPToExcel($member->getDateOfBirth()) : '');
            $sheet->setCellValue('E'. $i, $member->getRegistrationTime() ? Date::PHPToExcel($member->getRegistrationTime()): '');
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
            'Export MijnROOD Ledendatabase.xlsx'
        );
        return $response;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'Lidnummer')
                ->setRequired(false)
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Wordt automatisch bepaald']]),

            TextField::new('firstName', 'Voornaam'),
            TextField::new('lastName', 'Achternaam'),
            DateField::new('dateOfBirth', 'Geboortedatum')
                ->hideOnIndex(),
            DateField::new('registrationTime', 'Inschrijfdatum')
                ->setFormat(DateTimeField::FORMAT_SHORT)
                ->hideOnIndex(),
            AssociationField::new('division', 'Groep'),
            BooleanField::new('isAdmin', 'Toegang tot administratie')
                ->hideOnIndex(),

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
        ];
    }

}