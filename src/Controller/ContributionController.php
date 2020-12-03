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
use App\Entity\{ Member, MemberDetailsRevision };

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
        } else {
            $molliePayment = $mollieApiClient->payments->create([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($member->getContributionPerPeriodInEuros(), 2, '.', '')
                ],
                'description' => 'Contributie voor lid '. $member->getId(),
                'redirectUrl' => $this->generateUrl('member_contribution_paid'),
                'webhookUrl' => $this->generateUrl('member_contributino_mollie_webhook')
            ]);

            $contributionPayment = new ContributionPayment();
            $contributionPayment->setAmountInCents($member->getContributionPerPeriodInCents());
            $contributionPayment->setMolliePaymentId($molliePayment->id);

            $member->addContributionPayment($contributionPayment);

            $this->getDoctrine()->getManager()->flush();

            return $this->redirect($molliePayment->getCheckoutUrl(), [], 303);
        }
    }

    /**
     * @Route("/api/webhook/mollie-contribution", name="member_contribution_mollie_webhook")
     */
    public function webhook(Request $request, MollieApiClient $mollieApiClient): Response {
        $molliePaymentId = $request->body->get('id');
        $contributionPayment = $this->getDoctrine()->getRepository(ContributionPayment::class)->findOneByMolliePaymentId($molliePaymentId);
        if ($contributionPayment === null)
            throw $this->createNotFoundException('Betaling niet gevonden.');

        $molliePayment = $mollieApiClient->payments->get($molliePaymentId);
        $contributionPayment->setIsPaid($molliePayment->isPaid());
        $contributionPayment->setIsFailed($molliePayment->isFailed() || $molliePayment->isExpired() || $molliePayment->isCancelled() || $molliePayment->hasChargebacks());
        $contributionPayment->setIsRefunded($molliePayment->hasRefunds());

        return $this->json(['status' => 'success']);
    }

    /**
     * @Route("/contributie-betaald", name="member_contribution_paid")
     */
    public function contributionPaid(Request $request): Response {
        $member = $this->getUser();


    }

}
