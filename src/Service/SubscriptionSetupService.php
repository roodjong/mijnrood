<?php

declare(strict_types = 1);

namespace App\Service;

use App\Entity\Member;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\Customer;
use Mollie\Api\Resources\Subscription;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;

class SubscriptionSetupService
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ParameterBagInterface $params,
        private readonly UrlGeneratorInterface $router,
    )
    {
    }

    public function generateDescription(Member $member): string
    {
        $projectRoot = $this->params->get('kernel.project_dir');
        $org_config = Yaml::parseFile($projectRoot . '/config/instances/' . $this->params->get('app.organizationID') . '.yaml');

        $description = $org_config['mollie_payment_description'];
        $description = str_replace('{{organisation_name}}', $this->params->get("app.organizationName"), $description);
        return str_replace('{{member_number}}', (string)$member->getId(), $description);
    }

    /**
     * @throws ApiException
     */
    public function createSubscription(Member $member, Customer $customer): Subscription
    {
        $mollieIntervals = [
            Member::PERIOD_MONTHLY => '1 month',
            Member::PERIOD_QUARTERLY => '3 months',
            Member::PERIOD_ANNUALLY => '1 year'
        ];
        $dateTimeIntervals = [
            Member::PERIOD_MONTHLY => 'P1M',
            Member::PERIOD_QUARTERLY => 'P3M',
            Member::PERIOD_ANNUALLY => 'P1Y'
        ];

        $startDate = new DateTime();

        // Assuming quaterly interval:
        // December-February -> March 25th
        // March-May -> June 25th
        // June-August -> September 25th
        // September-November -> December 25th
        // Note: PHP transforms month 0 like 2025-0-1 into 2024-12-1, which is why the floor here works
        $startDate->setDate((int)date('Y'), (int)floor(date('m') / 3) * 3, 25);
        $startDate->add(new \DateInterval($dateTimeIntervals[$member->getContributionPeriod()]));

        if ($member->getContributionPeriod() == Member::PERIOD_QUARTERLY) {
            // We add another two months for two reasons:
            //
            // * In the current calculation it's possible that someone signs up
            //   on November 30th and pays their initial dues, and then their
            //   subscription starts in December, paying two months back to
            //   back. This is a bit unfair.
            // * The RSP has a lot of paying members in the
            //   March-June-September-December and the January-April-July-October
            //   cycles, but almost none in the February-May-August-November
            //   cycle. Moving by another two months puts us in this cycle and
            //   spreads the paying members out more evenly.
            //
            // Thus, if you sign up between June 1st and August 31th, your next
            // payment will be on November 25th (keep in mind an initial
            // payment has already been made on signup).
            //
            // TODO: In the future we want to look at the actual signup date of
            // the member, as the time between signup and approval (and thus
            // subscription start) can vary, and this is more fair and error
            // proof. See: https://github.com/roodjong/mijnrood/issues/237
            $startDate->add(new \DateInterval('P2M'));
        }

        $description = $this->generateDescription($member);

        $subscription = $customer->createSubscription([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($member->getContributionPerPeriodInEuros(), 2, '.', '')
            ],
            'interval' => $mollieIntervals[$member->getContributionPeriod()],
            'description' => $description,
            'startDate' => $startDate->format('Y-m-d'),
            'webhookUrl' => $this->router->generate('member_contribution_mollie_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
        $member->setMollieSubscriptionId($subscription->id);
        $this->doctrine->getManager()->flush();
        return $subscription;
    }
}
