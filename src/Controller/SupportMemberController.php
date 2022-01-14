<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Request, Response };
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Form\FormError;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

use App\Form\SupportMembershipApplicationType;
use App\Entity\{ SupportMember, SupportMembershipApplication };
use Mollie\Api\MollieApiClient;
use DateTime;
use DateInterval;

class SupportMemberController extends AbstractController
{

    private MollieApiClient $mollieApiClient;

    public function __construct(MollieApiClient $mollieApiClient)
    {
        $this->mollieApiClient = $mollieApiClient;
    }

    private function createPayment(/**MollieCustomer*/ $customer, SupportMembershipApplication $supportMembershipApplication, TranslatorInterface $translator)
    {
        $payment = $customer->createPayment([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($supportMembershipApplication->getContributionPerPeriodInEuros(), 2, '.', '')
            ],
            'description' => $translator->trans('Steunlidmaatschap ROOD'),
            'sequenceType' => 'first',
            'redirectUrl' => $this->generateUrl('support_member_redirect', ['customerId' => $customer->id, '_locale' => $translator->getLocale()], UrlGeneratorInterface::ABSOLUTE_URL),
            'webhookUrl' => $this->generateUrl('support_member_webhook', ['_locale' => $translator->getLocale()], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
        return $payment;
    }

    /**
     * @Route("/steunlid-worden/{_locale}", name="support_member_apply", requirements={"_locale": "en|nl"}, defaults={"_locale": "nl"})
     */
    public function apply(Request $request, TranslatorInterface $translator): Response
    {
        $supportMembershipApplication = new SupportMembershipApplication;
        $form = $this->createForm(SupportMembershipApplicationType::class, $supportMembershipApplication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $supportMemberRepository = $this->getDoctrine()->getRepository(SupportMember::class);
            $existingSupportMember = $supportMemberRepository->findOneByEmail($form['email']->getData());

            if ($existingSupportMember !== null)
            {
                $form->addError(new FormError($translator->trans('Er is al een steunlid met dit e-mailadres.')));
            }
            else
            {
                $customer = $this->mollieApiClient->customers->create([
                    'name' => $supportMembershipApplication->getFullName(),
                    'email' => $supportMembershipApplication->getEmail()
                ]);

                $supportMembershipApplication->setMollieCustomerId($customer->id);

                $em = $this->getDoctrine()->getManager();
                $em->persist($supportMembershipApplication);
                $em->flush();

                $payment = $this->createPayment($customer, $supportMembershipApplication, $translator);

                return $this->redirect($payment->getCheckoutUrl(), 303);
            }
        }

        return $this->render('user/support_member/apply.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/steunlid-worden/afronden/{customerId}/{_locale}", name="support_member_redirect", requirements={"_locale": "en|nl"}, defaults={"_locale": "nl"})
     */
    public function handleRedirect(Request $request, string $customerId): Response
    {
        $supportMembershipApplicationRepository = $this->getDoctrine()->getRepository(SupportMembershipApplication::class);
        $supportMembershipApplication = $supportMembershipApplicationRepository->findOneByMollieCustomerId($customerId);

        if ($supportMembershipApplication === null)
        {
            $supportMemberRepository = $this->getDoctrine()->getRepository(SupportMember::class);
            $supportMember = $supportMemberRepository->findOneByMollieCustomerId($customerId);

            if ($supportMember === null)
            {
                throw $this->createNotFoundException();
            }

            if ($request->query->has('check'))
            {
                return $this->json(['success' => true]);
            }

            return $this->render('user/support_member/finished.html.twig');
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

                    $retryUrl = $this->generateUrl('support_member_retry', [
                        'customerId' => $customerId, '_locale' => $request->locale
                    ]);

                    return $this->render('user/support_member/failed.html.twig', [
                        'retryUrl' => $retryUrl
                    ]);
                }
            }

            if ($request->query->has('check'))
            {
                return $this->json(['success' => false]);
            }

            return $this->render('user/support_member/processing.html.twig');
        }
    }

    /**
     * @Route("/steunlid-worden/opnieuw/{customerId}/{_locale}", name="support_member_retry", requirements={"_locale": "en|nl"}, defaults={"_locale": "nl"})
     */
    public function retryPayment(Request $request, string $customerId, TranslatorInterface $translator): Response
    {
        $supportMembershipApplicationRepository = $this->getDoctrine()->getRepository(SupportMembershipApplication::class);
        $supportMembershipApplication = $supportMembershipApplicationRepository->findOneByMollieCustomerId($customerId);

        if ($supportMembershipApplication === null)
        {
            return $this->redirectToRoute('support_member_redirect', [
                'customerId' => $customerId, '_locale' => $request->locale
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
     * @Route("/steunlid-worden/webhook/{_locale}", name="support_member_webhook", requirements={"_locale": "en|nl"}, defaults={"_locale": "nl"})
     */
    public function webhook(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $paymentId = $request->request->get('id');
        $payment = $this->mollieApiClient->payments->get($paymentId);

        if ($payment->isPaid())
        {
            $customer = $this->mollieApiClient->customers->get($payment->customerId);

            $supportMembershipApplicationRepository = $this->getDoctrine()->getRepository(SupportMembershipApplication::class);
            $supportMembershipApplication = $supportMembershipApplicationRepository->findOneByMollieCustomerId($customer->id);
            if ($supportMembershipApplication === null)
            {
                return $this->json(['success' => false], 404);
            }

            $period = $supportMembershipApplication->getContributionPeriod();
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

            $startDate = (new DateTime)->add(new DateInterval($dateTimeIntervals[$period]));

            $subscription = $customer->createSubscription([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($supportMembershipApplication->getContributionPerPeriodInEuros(), 2, '.', '')
                ],
                'description' => $translator->trans('Steunlidmaatschap ROOD'),
                'interval' => $mollieIntervals[$period],
                'startDate' => $startDate->format('Y-m-d')
            ]);

            $supportMember = $supportMembershipApplication->createSupportember($subscription->id);

            $em = $this->getDoctrine()->getManager();
            $em->persist($supportMember);
            $em->remove($supportMembershipApplication);
            $em->flush();

            // Send confirmation email
            $message = (new Email())
                ->subject($translator->trans('Welkom als steunlid bij {{ afdelingssite }}'))
                ->to(new Address($supportMember->getEmail(), $supportMember->getFirstName() .' '. $supportMember->getLastName()))
                ->from(new Address('{{ afdelingsmail }}', '{{ afdelingsnaam}}}'))
                ->html(
                    $this->renderView('email/html/welcome_support-' . $request->locale . '.html.twig', ['supportMember' => $supportMember])
                )
                ->text(
                    $this->renderView('email/text/welcome_support-' . $request->locale . '.txt.twig', ['supportMember' => $supportMember])
                );
            $mailer->send($message);

            return $this->json(['success' => true]);
        }
        else
        {
            return $this->json(['success' => false]);
        }
    }

}
