<?php

namespace App\Controller\Admin;

use App\Entity\SupportMember;
use App\Form\Admin\ContributionPaymentType;
use App\Form\Contribution\ContributionPeriodType;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ Field, IdField, BooleanField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, EmailField, AssociationField, MoneyField };
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Filters, Actions, Action };
use EasyCorp\Bundle\EasyAdminBundle\Filter\{ ChoiceFilter, EntityFilter };
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use Mollie\Api\MollieApiClient;

use Symfony\Component\HttpFoundation\{ BinaryFileResponse, ResponseHeaderBag };
use DateTime;

class SupportMemberCrud extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return SupportMember::class;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            MollieApiClient::class
        ]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Steunlid')
            ->setEntityLabelInPlural('Steunleden')
            ->setSearchFields(['id', 'firstName', 'lastName', 'email', 'phone', 'city', 'postCode'])
        ;
    }

    public function configureActions(Actions $actions): Actions {
        $action = Action::new('export', 'Exporteren', 'fa fa-file-excel')
            ->linkToCrudAction('export')
            ->setCssClass('btn btn-secondary')
            ->createAsGlobalAction();

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, $action);
    }

    public function export(AdminContext $adminContext)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Steunlidnr.');
        $sheet->setCellValue('B1', 'Voornaam');
        $sheet->setCellValue('C1', 'Achternaam');
        $sheet->setCellValue('D1', 'Geboortedatum');
        $sheet->setCellValue('E1', 'Inschrijfdataum');
        $sheet->setCellValue('F1', 'E-mailadres');
        $sheet->setCellValue('G1', 'Telefoonnr.');
        $sheet->setCellValue('H1', 'Adres');
        $sheet->setCellValue('I1', 'Plaats');
        $sheet->setCellValue('J1', 'Postcode');
        $sheet->setCellValue('K1', 'Landcode');
        $sheet->setCellValue('L1', 'IBAN');
        $sheet->setCellValue('M1', 'Contributiebedrag');
        $sheet->setCellValue('N1', 'Betaalperiode');
        $sheet->setCellValue('O1', 'Mollie CID');
        $sheet->setCellValue('P1', 'Mollie SID');

        $contributionPeriodNames = [
            SupportMember::PERIOD_MONTHLY => 'Maandelijks',
            SupportMember::PERIOD_QUARTERLY => 'Per kwartaal',
            SupportMember::PERIOD_ANNUALLY => 'Jaarlijks'
        ];
        $now = new DateTime;
        $supportMembers = $this->getDoctrine()->getRepository(SupportMember::class)->findAll();

        $i = 2;
        foreach ($supportMembers as $supportMember)
        {
            $sheet->setCellValue('A'. $i, $supportMember->getId());
            $sheet->setCellValue('B'. $i, $supportMember->getFirstName());
            $sheet->setCellValue('C'. $i, $supportMember->getLastName());

            $sheet->setCellValue('D'. $i, $supportMember->getDateOfBirth() ? Date::PHPToExcel($supportMember->getDateOfBirth()) : '');
            $sheet->getStyle('D'. $i)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

            $sheet->setCellValue('E'. $i, $supportMember->getRegistrationTime() ? Date::PHPToExcel($supportMember->getRegistrationTime()): '');
            $sheet->getStyle('E'. $i)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

            $sheet->setCellValue('F'. $i, $supportMember->getEmail());
            $sheet->setCellValue('G'. $i, $supportMember->getPhone());
            $sheet->setCellValue('H'. $i, $supportMember->getAddress());
            $sheet->setCellValue('I'. $i, $supportMember->getCity());
            $sheet->setCellValue('J'. $i, $supportMember->getPostCode());
            $sheet->setCellValue('K'. $i, $supportMember->getCountry());
            $sheet->setCellValue('L'. $i, $supportMember->getIBAN());
            $sheet->setCellValue('M'. $i, $supportMember->getContributionPerPeriodInEuros());
            $sheet->setCellValue('N'. $i, $contributionPeriodNames[$supportMember->getContributionPeriod()]);
            $sheet->setCellValue('O'. $i, $supportMember->getMollieCustomerId());
            $sheet->setCellValue('P'. $i, $supportMember->getMollieSubscriptionId());

            $i++;
        }

        foreach (range('A','R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = tempnam(sys_get_temp_dir(), 'mnrdexp');

        $writer->save($filename);
        $response = new BinaryFileResponse($filename);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'Export MijnROOD Steunledenlijst.xlsx'
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
            // TextField::new('iban', 'IBAN-rekeningnummer')->hideOnIndex(),
            Field::new('contributionPeriod', 'Betalingsperiode')
                ->setFormType(ContributionPeriodType::class)
                ->hideOnIndex(),
            MoneyField::new('contributionPerPeriodInCents', 'Bedrag')
                ->setCurrency('EUR')
                ->hideOnIndex(),
        ];
    }

    public function updateEntity(EntityManagerInterface $em, $entity): void
    {
        $mollieApi = $this->get(MollieApiClient::class);
        $subscription = $mollieApi->subscriptions->getForId($entity->getMollieCustomerId(), $entity->getMollieSubscriptionId());

        if ($subscription)
        {
            $period = $entity->getContributionPeriod();
            $mollieIntervals = [
                0 => '1 month',
                1 => '3 months',
                2 => '1 year'
            ];
            $dateTimeIntervals = [
                0 => 'P1M',
                1 => 'P3M',
                2 => 'P1Y'
            ];

            $update = false;
            if ($subscription->interval != $mollieIntervals[$period])
            {
                $subscription->interval = $mollieIntervals[$period];
                $update = true;
            }

            $valueFormatted = number_format($entity->getContributionPerPeriodInEuros(), 2, '.', '');
            if ($valueFormatted !== $subscription->amount->value)
            {
                $subscription->amount->value = $valueFormatted;
                $update = true;
            }

            if ($update)
                $subscription->update();
        }
        parent::updateEntity($em, $entity);
    }

    public function deleteEntity(EntityManagerInterface $em, $entity): void
    {
        $mollieApi = $this->get(MollieApiClient::class);
        $subscription = $mollieApi->subscriptions->getForId($entity->getMollieCustomerId(), $entity->getMollieSubscriptionId());

        if ($subscription)
        {
            $subscription->cancel();
        }
        parent::deleteEntity($em, $entity);
    }

}
