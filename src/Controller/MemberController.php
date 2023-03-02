<?php

namespace App\Controller;

use App\Form\{ MembershipApplicationType, MemberDetailsType, ChangePasswordType };
use App\Entity\{ Division, WorkGroup, Member, MembershipApplication, MemberDetailsRevision, Event};

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Customer;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Form\Extension\Core\Type\{ PasswordType, RepeatedType };
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Form\FormError;

use DateTime;
use DateInterval;

class MemberController extends AbstractController {

    private MollieApiClient $mollieApiClient;

    public function __construct(MailerInterface $mailer) {
        $this->mailer = $mailer;
    }

    public function memberAcceptPersonalDetails(Request $request): Response {
        $member = $this->getUser();
        $orgName = $this->getParameter('app.organizationName');
        $privacyPolicyUrl = $this->getParameter('app.privacyPolicyUrl');
        $form = $this->createFormBuilder($member)
            ->add('acceptUsePersonalInformation', null, [
                'label' => "Ik ga ermee akkoord dat $orgName mijn persoonsgegevens opslaat in haar ledenadministratie, zoals beschreven in het <a href='$privacyPolicyUrl'>privacybeleid</a>.",
                'label_html' => true,
                'required' => true,
                'error_bubbling' => true,
                'constraints' => [new IsTrue(['message' => 'Je moet akkoord gaan met het privacybeleid om verder te gaan.'])]
            ])
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('member_home');
        }

        return $this->render('user/privacy-policy.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/", name="member_home")
     */
    public function home(Request $request): Response {
        $member = $this->getUser();
        if (!$member->getAcceptUsePersonalInformation())
            return $this->memberAcceptPersonalDetails($request);

        $events = $this->getDoctrine()->getRepository(Event::class)->createQueryBuilder('e')
            ->where('e.division IS NULL or e.division = ?1')
            ->andWhere('e.timeEnd > ?2')
            ->setParameter(1, $member->getDivision())
            ->setParameter(2, new DateTime())
            ->orderBy('e.timeStart', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('user/home.html.twig', [
            'events' => $events
        ]);
    }

    /**
     * @Route("/aanmelden", name="member_apply")
     */
    public function apply(Request $request): Response {
        $membershipApplication = new MembershipApplication();
        $membershipApplication->setRegistrationTime(new \DateTime());
        $membershipApplication->setContributionPeriod(Member::PERIOD_QUARTERLY);
        $form = $this->createForm(MembershipApplicationType::class, $membershipApplication);

        $workGroupCount = $this->getDoctrine()->getRepository(WorkGroup::class)->createQueryBuilder('d')
                           ->where('d.canBeSelectedOnApplication = true')
                           ->getQuery()
                           ->getResult();
        $showGroups = true;
        $showWorkGroups = true;
        if (count($groupCount) === 0) {
            $showGroups = false;
        }
        if (count($workGroupCount) === 0) {
            $showWorkGroups = false;
        }
        $form = $this->createForm(MembershipApplicationType::class, $member, [
            'show_groups' => $showGroups,
            'show_work_groups' => $showWorkGroups,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($membershipApplication);
            $em->flush();

            $memberRepository = $this->getDoctrine()->getRepository(Member::class);
            $existingMember = $memberRepository->findOneByEmail($form['email']->getData());

            if ($existingMember !== null)
            {
                $form->addError(new FormError('Er is al een lid met dit e-mailadres.'));
            }
            else
            {
                $customer = $this->mollieApiClient->customers->create([
                    'name' => $membershipApplication->getFirstName() . ' ' . $membershipApplication->getLastName(),
                    'email' => $membershipApplication->getEmail()
                ]);

                $membershipApplication->setMollieCustomerId($customer->id);

                $em = $this->getDoctrine()->getManager();
                $em->persist($membershipApplication);
                $em->flush();

                $payment = $this->createPayment($customer, $membershipApplication->getContributionPerPeriodInEuros());

                return $this->redirect($payment->getCheckoutUrl(), 303);
            }
            $noreply = $this->getParameter('app.noReplyAddress');
            $organizationName = $this->getParameter('app.organizationName');
            $divisionEmail = $member->getPreferredDivision()->getEmail();
            $message = (new Email())
            ->subject("Bedankt voor je aanmelding bij $organizationName!")
            ->to(new Address($member->getEmail(), $member->getFullName()))
            ->from(new Address($noreply, $organizationName))
            ->html(
                $this->renderView('email/html/apply.html.twig', ['member' => $member])
            )
            ->text(
                $this->renderView('email/text/apply.txt.twig', ['member' => $member])
            );
            if ($divisionEmail != null) {
                $message->addCc(new Address($divisionEmail, $member->getPreferredDivision()->getName()));
            }
            $this->mailer->send($message);

            return $this->render('user/apply.html.twig', [
                'success' => true
            ]);
        }

        return $this->render('user/member/apply.html.twig', [
            'success' => false,
            'form' => $form->createView(),
            'showGroups' => $showGroups,
        ]);
    }

    private function createPayment(Customer $customer, float $contributionAmount) {
        $payment = $customer->createPayment([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($contributionAmount, 2, '.', '')
            ],
            'sequenceType' => 'first',
            'locale' => 'nl_NL',
            'description' => $this->getParameter('mollie_payment_description'),
            'redirectUrl' => $this->generateUrl('member_redirect', ['customerId' => $customer->id], UrlGeneratorInterface::ABSOLUTE_URL),
            'webhookUrl' => $this->generateUrl('member_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
        return $payment;
    }

    /**
     * @Route("/aanmelden/afronden/{customerId}", name="member_redirect")
     */
    public function handleRedirect(Request $request, string $customerId): Response
    {
        $membershipApplicationRepository = $this->getDoctrine()->getRepository(MembershipApplication::class);
        $membershipApplication = $membershipApplicationRepository->findOneByMollieCustomerId($customerId);

        if ($membershipApplication !== null && $membershipApplication->getPaid())
        {
            if ($request->query->has('check'))
            {
                return $this->json(['success' => true]);
            }

            return $this->render('user/member/finished.html.twig');
        }
        else
        {
            $payments = $this->mollieApiClient->customerPayments->listForId($customerId);
            $failedNoSuccess = false;
            foreach ($payments as $payment)
            {
                if ($payment->isCanceled() || $payment->isExpired())
                {
                    if ($request->query->has('check'))
                    {
                        return $this->json(['success' => true]);
                    }

                    $retryUrl = $this->generateUrl('member_retry', [
                        'customerId' => $customerId
                    ]);

                    return $this->render('user/member/failed.html.twig', [
                        'retryUrl' => $retryUrl
                    ]);
                }
            }

            if ($request->query->has('check'))
            {
                return $this->json(['success' => false]);
            }

            return $this->render('user/member/processing.html.twig');
        }
    }

    /**
     * @Route("/aanmelden/opnieuw/{customerId}", name="member_retry")
     */
    public function retryPayment(Request $request, string $customerId): Response
    {
        $supportMembershipApplicationRepository = $this->getDoctrine()->getRepository(MembershipApplication::class);
        $supportMembershipApplication = $supportMembershipApplicationRepository->findOneByMollieCustomerId($customerId);

        if ($supportMembershipApplication === null)
        {
            return $this->redirectToRoute('member_redirect', [
                'customerId' => $customerId
            ]);
        }
        else
        {
            $customer = $this->mollieApiClient->customers->get($customerId);
            $payment = $this->createPayment($customer, $supportMembershipApplication, $translator);

            return $this->redirect($payment->getCheckoutUrl(), 303);
        }
    }

    /**
     * @Route("/aanmelden/webhook", name="member_webhook")
     */
    public function webhook(Request $request, EntityManagerInterface $entityManager): Response
    {
        $paymentId = $request->request->get('id');
        $payment = $this->mollieApiClient->payments->get($paymentId);

        if ($payment->isPaid())
        {
            $customer = $this->mollieApiClient->customers->get($payment->customerId);

            $membershipApplicationRepository = $this->getDoctrine()->getRepository(MembershipApplication::class);
            $membershipApplication = $membershipApplicationRepository->findOneByMollieCustomerId($customer->id);
            if ($membershipApplication === null)
            {
                return $this->json(['success' => false], 404);
            }

            $membershipApplication->setPaid(true);

            $entityManager->flush();

            return $this->json(['success' => true]);
        }
        else
        {
            return $this->json(['success' => false]);
        }
    }

    /**
     * @Route("/gegevens", name="member_details")
     */
    public function details(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response {
        $member = $this->getUser();
        if (!$member->getAcceptUsePersonalInformation())
            return $this->memberAcceptPersonalDetails($request);

        $form = $this->createForm(MemberDetailsType::class, $member);
        $revision = new MemberDetailsRevision($member, true);
        $success = false;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($revision);
            $em->flush();
            $success = true;
        }

        $successPassword = false;

        $formPassword = $this->createForm(ChangePasswordType::class);
        $formPassword->handleRequest($request);
        if ($formPassword->isSubmitted() && $formPassword->isValid())
        {
            $valid = $passwordEncoder->isPasswordValid($member, $formPassword['currentPassword']->getData());
            if (!$valid)
            {
                $formPassword->addError(new FormError('Het opgegeven huidige wachtwoord is niet correct.'));
            }
            else
            {
                $passwordHash = $passwordEncoder->encodePassword($member, $formPassword['newPassword']->getData());
                $member->setPasswordHash($passwordHash);
                $this->getDoctrine()->getManager()->flush();
                $successPassword = true;
            }
        }

        return $this->render('user/details.html.twig', [
            'form' => $form->createView(),
            'formPassword' => $formPassword->createView(),
            'success' => $success,
            'successPassword' => $successPassword
        ]);
    }

}
