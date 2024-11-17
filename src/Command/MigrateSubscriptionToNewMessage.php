<?php

declare(strict_types = 1);

namespace App\Command;

use App\Entity\Member;
use App\Service\SubscriptionSetupService;
use Doctrine\ORM\EntityManagerInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Subscription;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class MigrateSubscriptionToNewMessage extends Command
{
    protected static $defaultName = 'app:migrate-subscription-to-new-message';
    protected static $defaultDescription = 'Update all Mollie subscriptions to use the currently configured payment description';

    public function __construct(
        private readonly MollieApiClient $mollieApiClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly SubscriptionSetupService $subscriptionService,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Updating Mollie subscription to new message');

        try {
            $this->confirmChangeCount($input, $output);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        $memberRepository = $this->entityManager->getRepository(Member::class);

        $allSubscriptions = $this->mollieApiClient->subscriptions->iterator();
        foreach ($allSubscriptions as $subscription) {
            /** @var Subscription $subscription */
            if (!$subscription->isActive()) {
                continue;
            }
            $output->writeln('Updating subscription ' . $subscription->description);
            /** @var Member $member */
            $members = $memberRepository->findBy(['mollieSubscriptionId' => $subscription->id]);
            if (count($members) === 0) {
                $output->writeln('<comment>Could not find member for subscription ' . $subscription->id . '! (skipping)</comment>');
                continue;
            }
            $member = $members[0];
            $newDescription = $this->subscriptionService->generateDescription($member);
            $subscription->description = $newDescription;
            try {
                $subscription->update();
            } catch (ApiException $exception) {
                $output->writeln('<error>' . $exception->getMessage() . '</error>');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    private function confirmChangeCount(InputInterface $input, OutputInterface $output): void
    {
        $allSubscriptions = $this->mollieApiClient->subscriptions->iterator();
        $amount = $allSubscriptions->count();
        $helper = $this->getHelper('question');
        $question = new Question("You are about to change $amount descriptions, to continue confirm the amount: ", false);
        $answer = (int)$helper->ask($input, $output, $question);
        if ($answer === $amount) {
            return;
        }
        throw new \Exception('Did not confirm');
    }
}
