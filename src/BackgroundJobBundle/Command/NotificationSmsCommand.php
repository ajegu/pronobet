<?php

namespace BackgroundJobBundle\Command;

use AppBundle\Entity\SubscriptionStatus;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationSmsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('notification:sms')
            ->setDescription('Send sms notification for sport forecast publishing');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $awsService = $this->getContainer()->get('aws');
        $logger = $this->getContainer()->get('logger');
        $queueUrl = $this->getContainer()->getParameter('aws_sqs.notification_sms');
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $translator = $this->getContainer()->get('translator');
        $router = $this->getContainer()->get('router');

        $output->writeln('');
        $output->writeln('notification sms pending...');
        $env = $input->getOption('env');

        while (true) {
            $result = $awsService->receiveMessages($queueUrl);
            $message = $result->get('Messages');
            $sportForecastId = (int)$message[0]['Body'];
            $receiptHandle = $message[0]['ReceiptHandle'];

            if ($sportForecastId !== 0) {
                // get entity
                $sportForecast = $em->getRepository('AppBundle:SportForecast')->find($sportForecastId);
                $output->writeln('send email for sport forecast ID: ' . $sportForecast->getId());

                // get tipster subscriber
                $subscriptions = $em->getRepository('AppBundle:Subscription')
                    ->findBy([
                        'tipster' => $sportForecast->getTipster(),
                        'activate' => true,
                        'status' => SubscriptionStatus::Vip
                    ]);
                $output->writeln('numbers of users find: ' . count($subscriptions));



                $message = sprintf('%s %s %s !',
                    $sportForecast->getTipster()->getUser()->getNickname(),
                    $translator->trans('sms.sport_forecast'),
                    ($sportForecast->getIsVip() ? $translator->trans('label.vip') : $translator->trans('label.free')));

                $message .= chr(10);

                $message .= $router->generate(
                    'sport_forecast_show',
                    ['id' => $sportForecast->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                foreach ($subscriptions as $subscription) {
                    if ($subscription->getSmsNotification() === true) {
                        if ($env === 'prod') {

                            if ($subscription->getUser()->getPhoneNumber()) {
                                $phoneNumber = sprintf('+%s %s',
                                    $subscription->getUser()->getCountry()->getPhoneNumberCode(),
                                    $subscription->getUser()->getPhoneNumber()
                                    );
                                try {
                                    $awsService->sendSms($message, $phoneNumber);
                                } catch (\Exception $e) {
                                    $logger->critical('Cannot send SMS', [
                                        'service' => 'AWS SNS',
                                        'error' => $e->getMessage(),
                                        'sportForecastId' => $sportForecastId,
                                        'phoneNumber' => $phoneNumber
                                    ]);
                                }
                            } else {
                                $logger->info(
                                    sprintf('UserID: %s has activate sms notification without phone number', $subscription->getUser()->getId())
                                );
                            }

                        }

                        $output->writeln('sms sent to ' . $subscription->getUser()->getPhoneNumber());
                        $output->writeln($message);
                    }
                }

                $awsService->deleteMessage($queueUrl, $receiptHandle);

                if ($env === 'test' || $env === 'dev') {
                    break;
                }
            }

            sleep(1);
        }
    }

}
