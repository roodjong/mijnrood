<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Form\Extension\Core\Type\{ PasswordType, RepeatedType };
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Mollie\Api\MollieApiClient;
use Swift_Mailer, Swift_Message;
use App\Form\Contribution\{ PreferencesType };
use App\Entity\{ ContributionPayment, Member };
use DateTime;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContributionController extends AbstractController
{

    /**
     * @Route("/contributie-instellingen", name="member_contribution_preferences")
     */
    public function preferences(Request $request): Response {
        $member = $this->getUser();

        $form = $this->createForm(PreferencesType::class, $member);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('member_details');
        }

        return $this->render('user/contribution/preferences.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/contributie-betalen", name="member_contribution_pay")
     */
    public function payContribution(Request $request, MollieApiClient $mollieApiClient, bool $automaticCollection = false): Response {
        $member = $this->getUser();

        // Already has subscription?
        if ($member->isContributionPaidAutomatically())
        {
            return $this->redirectToRoute('member_details');
        }

        // Create payment
        $customer = $this->getOrCreateMollieCustomer($mollieApiClient);

        $redirectUrl = $automaticCollection
            ? $this->generateUrl('member_contribution_automatic_collection_enabled', [], UrlGeneratorInterface::ABSOLUTE_URL)
            : $this->generateUrl('member_contribution_paid', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ;

        if ($automaticCollection)
        {
            $member->setCreateSubscriptionAfterPayment(true);
        }

        // Create mollie payment
        $molliePayment = $customer->createPayment([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($member->getContributionPerPeriodInEuros(), 2, '.', '')
            ],
            'sequenceType' => 'first',
            'locale' => 'nl_NL',
            'description' => $this->getParameter('mollie_payment_description'),
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => $this->generateUrl('member_contribution_mollie_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);

        // Save contribution payment in database
        $contributionPayment = $this->createContributionPayment($member->getContributionPeriod(), $molliePayment);
        $member->addContributionPayment($contributionPayment);
        $this->getDoctrine()->getManager()->flush();

        // If first payment for automatic collection, show info screen
        if ($automaticCollection)
        {
            return $this->render('user/contribution/first-payment.html.twig', [
                'checkoutUrl' => $molliePayment->getCheckoutUrl()
            ]);
        }

        // Otherwise redirect to payment screen immediately
        return $this->redirect($molliePayment->getCheckoutUrl(), 303);
    }

    /**
     * @Route("/api/webhook/mollie-contribution", name="member_contribution_mollie_webhook")
     */
    public function webhook(Request $request, MollieApiClient $mollieApiClient): Response {
        $em = $this->getDoctrine()->getManager();

        $molliePaymentId = $request->request->get('id');
        $molliePayment = $mollieApiClient->payments->get($molliePaymentId);
        $customer = $mollieApiClient->customers->get($molliePayment->customerId);

        $contributionPayment = $this->getDoctrine()->getRepository(ContributionPayment::class)->findOneByMolliePaymentId($molliePaymentId);

        // If the payment comes from a subscription, it is not yet registered in the database
        if ($contributionPayment === null) {
            $member = $this->getDoctrine()->getRepository(Member::class)->findOneByMollieSubscriptionId($molliePayment->subscriptionId);
            $contributionPayment = $this->createContributionPayment($member->getContributionPeriod(), $molliePayment);
            $member->addContributionPayment($contributionPayment);
        } else {
            $member = $contributionPayment->getMember();
        }

        switch (true) {
            case $molliePayment->hasRefunds():
            case $molliePayment->hasChargebacks():
                $contributionPayment->setStatus(ContributionPayment::STATUS_REFUNDED);
                break;
            case $molliePayment->isExpired():
            case $molliePayment->isCanceled():
                $contributionPayment->setStatus(ContributionPayment::STATUS_FAILED);
                break;
            case $molliePayment->isPaid():
                $contributionPayment->setStatus(ContributionPayment::STATUS_PAID);

                if ($member->getCreateSubscriptionAfterPayment()) {
                    $this->setupSubscription($member, $customer, $mollieApiClient);
                    $member->setCreateSubscriptionAfterPayment(false);
                }
                break;
            default:
                $contributionPayment->setStatus(ContributionPayment::STATUS_PENDING);
        }

        $em->flush();

        return $this->json(['status' => 'success']);
    }

    /**
     * @Route("/automatische-incasso", name="member_contribution_automatic_collection")
     */
    public function automaticCollection(Request $request): Response {
        return $this->render('user/contribution/automatic-collection.html.twig');
    }

    /**
     * @Route("/automatische-incasso/inschakelen", name="member_contribution_automatic_collection_enable")
     */
    public function enableAutomaticCollection(Request $request, MollieApiClient $mollieApiClient): Response {
        $em = $this->getDoctrine()->getManager();
        $member = $this->getUser();

        // Already has subscription
        if ($member->getMollieSubscriptionId() !== null)
        {
            return $this->redirectToRoute('member_contribution_automatic_collection');
        }

        // 1. Get customer for Member (create if not exists)
        $customer = $this->getOrCreateMollieCustomer($mollieApiClient);

        // 2. If no mandate exists, create first payment with createSubscriptionAfterPayment = true
        if (!$customer->hasValidMandate())
        {
            return $this->payContribution($request, $mollieApiClient, true);
        }

        // 3. If a mandate does exist, set up subscription
        $this->setupSubscription($member, $customer, $mollieApiClient);

        // 4. Show automatic collection enabled screen
        return $this->redirectToRoute('member_contribution_automatic_collection');
    }

    /**
     * @Route("/automatische-incasso/uitschakelen", name="member_contribution_automatic_collection_disable")
     */
    public function disableAutomaticCollection(Request $request, MollieApiClient $mollieApiClient): Response {
        $em = $this->getDoctrine()->getManager();
        $member = $this->getUser();

        // No subscription?
        if ($member->getMollieSubscriptionId() === null)
        {
            return $this->redirectToRoute('member_contribution_automatic_collection');
        }

        $customer = $mollieApiClient->customers->get($member->getMollieCustomerId());
        $subscription = $mollieApiClient->subscriptions->getFor($customer, $member->getMollieSubscriptionId());
        $subscription->cancel();
        $member->setMollieSubscriptionId(null);
        $em->flush();

        return $this->redirectToRoute('member_contribution_automatic_collection');
    }

    /**
     * @Route("/contributie-betaald", name="member_contribution_paid")
     */
    public function contributionPaid(Request $request): Response {
        $member = $this->getUser();

        return $this->render('user/contribution/paid.html.twig');
    }

    /**
     * @Route("/automatische-incasso/ingeschakeld", name="member_contribution_automatic_collection_enabled")
     */
    public function automaticCollectionEnabled(Request $request): Response {
        $member = $this->getUser();
        //
        // if (!$member->isContributionPaidAutomatically())
        // {
        //     return $this->redirectToRoute('member_contribution_automatic_collection');
        // }

        return $this->render('user/contribution/automatic-collection-enabled.html.twig');
    }

    /** @Route("/mollie-admin") */
    public function mollieinfo(Request $request, MollieApiClient $mollieApiClient): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $html = '<h1>Mollie Admin</h1>';
        if (!$request->query->has('customerId'))
        {
            $html .= '<h2>Customers</h2>';
            foreach ($mollieApiClient->customers->page() as $customer)
            {
                $html .= '<a href="?customerId='.$customer->id.'">'.$customer->name.'</a><br />';
            }
            return new Response($html);
        }

        $customer = $mollieApiClient->customers->get($request->query->get('customerId'));
        $html .= '<h2>'.$customer->name.'</h2>';
        $html .= '<a href="?">Back</a><hr />';

        if ($request->query->has('subscriptionId'))
        {
            $subscription = $mollieApiClient->subscriptions->getFor($customer, $request->query->get('subscriptionId'));
            $html .= '<h3>'.htmlentities($subscription->description).'</h3>';
            $html .= 'Status: '.$subscription->status.'<br />';

            if (!$request->query->has('action'))
            {
                if (!$subscription->isCanceled())
                {
                    $html .= ' <a href="?customerId='.$customer->id.'&subscriptionId='.$subscription->id.'&action=cancel">Cancel subscription</a><br />';
                }

                $html .= '<h3>Payments</h3>';
                foreach ($subscription->payments() as $payment)
                {
                    $html .= $payment->id.': '.$payment->amount->currency.' '.$payment->amount->value.' ('.$payment->status.')<br />';
                }

                return new Response($html);
            }

            switch($request->get('action'))
            {
                case 'cancel':
                    $subscription->cancel();
                    $html .= 'Subscription cancelled';
                    break;
                default:
                    $html .= 'Invalid action';
                    break;
            }

            return new Response($html);
        }
        elseif ($request->query->has('mandateId'))
        {
            $mandate = $mollieApiClient->mandates->getFor($customer, $request->query->get('mandateId'));
            $html .= '<h3>'.htmlentities($mandate->method).'</h3>';
            $html .= 'Status: '.$mandate->status.'<br />';

            if (!$request->query->has('action'))
            {
                if ($mandate->status != 'invalid')
                {
                    $html .= ' <a href="?customerId='.$customer->id.'&mandateId='.$mandate->id.'&action=cancel">Cancel mandate</a><br />';
                }
                return new Response($html);
            }

            switch($request->get('action'))
            {
                case 'cancel':
                    $mandate->revoke();
                    $html .= 'Mandate revoked';
                    break;
                default:
                    $html .= 'Invalid action';
                    break;
            }

            return new Response($html);
        }
        else
        {
            $html .= '<h3>Subscriptions</h3>';
            foreach($customer->subscriptions() as $subscription)
            {
                $html .= $subscription->id.': <a href="?customerId='.$customer->id.'&subscriptionId='.$subscription->id.'">'.htmlentities($subscription->description).' ('.$subscription->status.')</a><br />';
            }
            $html .= '<h3>Mandates</h3>';
            foreach($customer->mandates() as $mandate)
            {
                $html .= '<a href="?customerId='.$customer->id.'&mandateId='.$mandate->id.'">'.$mandate->id.'</a> ('.$mandate->status.')<br />';
            }
            $html .= '<h3>Payments</h3>';
            foreach($customer->payments() as $payment)
            {
                $html .= $payment->id.': '.$payment->amount->currency.' '.$payment->amount->value.' ('.$payment->status.', '.$payment->sequenceType.')<br />';
            }
            return new Response($html);
        }
    }

    private function getOrCreateMollieCustomer(MollieApiClient $mollieApiClient) {
        $member = $this->getUser();

        // Return existing customer
        $mollieCustomerId = $member->getMollieCustomerId();
        if ($mollieCustomerId !== null)
        {
            return $mollieApiClient->customers->get($mollieCustomerId);
        }

        // Create new customer
        $customer = $mollieApiClient->customers->create([
            'name' => $member->getFullName(),
            'email' => $member->getEmail()
        ]);
        $member->setMollieCustomerId($customer->id);

        $this->getDoctrine()->getManager()->flush();
        return $customer;
    }

    private function createContributionPayment(int $contributionPeriod, $molliePayment) {
        // Create contribution payment
        $contributionPayment = new ContributionPayment();
        $contributionPayment->setAmountInCents(round($molliePayment->amount->value * 100));
        $contributionPayment->setStatus(ContributionPayment::STATUS_PENDING);
        $contributionPayment->setPaymentTime(new DateTime);
        $contributionPayment->setMolliePaymentId($molliePayment->id);

        // Set correct year and period information
        $contributionPayment->setPeriodYear((int) date('Y'));

        $month = (int) date('m');
        switch($contributionPeriod) {
            case Member::PERIOD_MONTHLY:
                $contributionPayment->setPeriodMonthStart($month);
                $contributionPayment->setPeriodMonthEnd($month);
                break;
            case Member::PERIOD_QUARTERLY:
                $quarter = ceil($month / 3);
                $contributionPayment->setPeriodMonthStart(($quarter * 3) - 2);
                $contributionPayment->setPeriodMonthEnd($quarter * 3);
                break;
            case Member::PERIOD_ANNUALLY:
                $contributionPayment->setPeriodMonthStart(1);
                $contributionPayment->setPeriodMonthEnd(12);
                break;
        }

        return $contributionPayment;
    }

    private function setupSubscription(Member $member, $customer, MollieApiClient $mollieApiClient) {
        $startDate = new DateTime('next year 1 january');
        $subscription = $customer->createSubscription([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($member->getContributionPerPeriodInEuros(), 2, '.', '')
            ],
            'interval' => '12 months',
            'description' => $this->getParameter('mollie_payment_description'),
            'startDate' => $startDate->format('Y-m-d'),
            'webhookUrl' => $this->generateUrl('member_contribution_mollie_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
        $member->setMollieSubscriptionId($subscription->id);
        $this->getDoctrine()->getManager()->flush();
    }

}