<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Request, Response };
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Form\SupportMembershipApplicationType;
use App\Entity\{ SupportMember, SupportMembershipApplication };
use Mollie\Api\MollieApiClient;
use Swift_Mailer, Swift_Message;
use DateTime;
use DateInterval;

class SupportMemberController extends AbstractController
{

    /**
     * @Route("/steunlid-worden", name="support_member_apply")
     */
    public function apply(Request $request, MollieApiClient $mollieApiClient): Response
    {
        $supportMembershipApplication = new SupportMembershipApplication;
        $form = $this->createForm(SupportMembershipApplicationType::class, $supportMembershipApplication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $customer = $mollieApiClient->customers->create([
                'name' => $supportMembershipApplication->getFullName(),
                'email' => $supportMembershipApplication->getEmail()
            ]);

            $supportMembershipApplication->setMollieCustomerId($customer->id);

            $em = $this->getDoctrine()->getManager();
            $em->persist($supportMembershipApplication);
            $em->flush();

            $payment = $customer->createPayment([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($supportMembershipApplication->getContributionPerPeriodInEuros(), 2, '.', '')
                ],
                'description' => 'Steunlidmaatschap ROOD',
                'sequenceType' => 'first',
                'redirectUrl' => $this->generateUrl('support_member_redirect', ['customerId' => $customer->id], UrlGeneratorInterface::ABSOLUTE_URL),
                'webhookUrl' => $this->generateUrl('support_member_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

            return $this->redirect($payment->getCheckoutUrl(), 303);
        }

        return $this->render('user/support_member/apply.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/steunlid-worden/afronden/{customerId}", name="support_member_redirect")
     */
    public function handleRedirect(Request $request, MollieApiClient $mollieApiClient, string $customerId): Response
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
            if ($request->query->has('check'))
            {
                return $this->json(['success' => false]);
            }

            return $this->render('user/support_member/processing.html.twig');
        }
    }

    /**
     * @Route("/steunlid-worden/webhook", name="support_member_webhook")
     */
    public function webhook(Request $request, MollieApiClient $mollieApiClient, Swift_Mailer $mailer): Response
    {
        $paymentId = $request->request->get('id');
        $payment = $mollieApiClient->payments->get($paymentId);

        if ($payment->isPaid())
        {
            $customer = $mollieApiClient->customers->get($payment->customerId);

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
                'description' => 'Steunlidmaatschap ROOD',
                'interval' => $mollieIntervals[$period],
                'startDate' => $startDate->format('Y-m-d')
            ]);

            $supportMember = $supportMembershipApplication->createSupportember($subscription->id);

            $em = $this->getDoctrine()->getManager();
            $em->persist($supportMember);
            $em->remove($supportMembershipApplication);
            $em->flush();

            // Send confirmation email
            $message = (new Swift_Message())
                ->setSubject('Welkom als steunlid bij ROOD, jong in de SP')
                ->setTo([$supportMember->getEmail() => $supportMember->getFirstName() .' '. $supportMember->getLastName()])
                ->setFrom(['noreply@roodjongindesp.nl' => 'ROOD, jong in de SP'])
                ->setBody(
                    $this->renderView('email/html/welcome_support.html.twig', ['supportMember' => $supportMember]),
                    'text/html'
                )
                ->addPart(
                    $this->renderView('email/text/welcome_support.txt.twig', ['supportMember' => $supportMember]),
                    'text/plain'
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
