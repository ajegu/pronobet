<?php

namespace BackgroundJobBundle\Command;

use MangoPay\Money;
use MangoPay\PayInStatus;
use MangoPay\Transfer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WalletTransferCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('wallet:transfer')
            ->setDescription('...')
            ->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mangoPayService = $this->getContainer()->get('mangopay');
        $logger = $this->getContainer()->get('logger');
        $em = $this->getContainer()->get('doctrine')->getManager();

        $subscriptions = $em->getRepository('AppBundle:Subscription')
            ->findEndedSubscriptions();

        foreach ($subscriptions as $subscription) {

            $user = $subscription->getUser();
            $tipster = $subscription->getTipster();

            $transfer = new Transfer();
            $transfer->AuthorId = $user->getMangoPayId();
            $transfer->CreditedUserId = $tipster->getMangoPayId();
            $transfer->DebitedFunds = new Money();
            $transfer->DebitedFunds->Currency = 'EUR';
            $transfer->DebitedFunds->Amount = $subscription->getAmount() * 100;
            $transfer->Fees = new Money();
            $transfer->Fees->Currency = 'EUR';
            $transfer->Fees->Amount = $subscription->getFees() * 100;
            $transfer->DebitedWalletId = $user->getMangoPayWalletId();
            $transfer->CreditedWalletId = $tipster->getMangoPayWalletId();

            try {
                $createdTransfer = $mangoPayService->createTransfer($transfer);
            } catch (\Exception $e) {
                $logger->critical('MangoPay Payout failed!', [
                    'service' => 'MangoPay',
                    'error' => $e->getMessage()
                ]);
            }


            // desactivate subscription
            $subscription->setActivate(false)
                ->setUpdatedAt(new \DateTime());
            $em->persist($subscription);
            $em->flush();

            $output->writeln("transfer done! (ID: $createdTransfer->Id)");
        }
    }

}
