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
        $startDate->setDate((int)date('Y'), (int)floor(date('m') / 3) * 3, 1);
        $startDate->add(new \DateInterval($dateTimeIntervals[$member->getContributionPeriod()]));

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
