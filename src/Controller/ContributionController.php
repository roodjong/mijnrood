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
    public function payContribution(Request $request, MollieApiClient $mollieApiClient): Response {
        $member = $this->getUser();

        if ($member->isContributionPaidAutomatically()) {
            return $this->redirectToRoute('member_details');
        }

        $molliePayment = $mollieApiClient->payments->create([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($member->getContributionPerPeriodInEuros(), 2, '.', '')
            ],
            'description' => 'Contributie voor lid '. $member->getId(),
            'redirectUrl' => $this->generateUrl('member_contribution_paid', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'webhookUrl' => $this->generateUrl('member_contribution_mollie_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);

        $contributionPayment = new ContributionPayment();
        $contributionPayment->setAmountInCents($member->getContributionPerPeriodInCents());
        $contributionPayment->setMolliePaymentId($molliePayment->id);
        $contributionPayment->setStatus(ContributionPayment::STATUS_PENDING);
        $contributionPayment->setPeriodYear((int) date('Y'));
        $contributionPayment->setPaymentTime(new DateTime);

        $month = (int) date('m');
        switch($member->getContributionPeriod()) {
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

        $member->addContributionPayment($contributionPayment);

        $this->getDoctrine()->getManager()->flush();

        return $this->redirect($molliePayment->getCheckoutUrl(), 303);
    }

    /**
     * @Route("/api/webhook/mollie-contribution", name="member_contribution_mollie_webhook")
     */
    public function webhook(Request $request, MollieApiClient $mollieApiClient): Response {
        $molliePaymentId = $request->request->get('id');
        $contributionPayment = $this->getDoctrine()->getRepository(ContributionPayment::class)->findOneByMolliePaymentId($molliePaymentId);
        if ($contributionPayment === null)
            throw $this->createNotFoundException('Betaling niet gevonden.');

        $molliePayment = $mollieApiClient->payments->get($molliePaymentId);
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
                break;
            default:
                $contributionPayment->setStatus(ContributionPayment::STATUS_PENDING);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->json(['status' => 'success']);
    }

    /**
     * @Route("/contributie-betaald", name="member_contribution_paid")
     */
    public function contributionPaid(Request $request): Response {
        $member = $this->getUser();

        return $this->render('user/contribution/paid.html.twig');
    }

}
