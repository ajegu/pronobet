<?php

namespace BackgroundJobBundle\Command;

use AppBundle\Entity\SubscriptionStatus;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NotificationEmailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('notification:email')
            ->setDescription('Send email notification for sport forecast publishing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $awsService = $this->getContainer()->get('aws');
        $logger = $this->getContainer()->get('logger');
        $queueUrl = $this->getContainer()->getParameter('aws_sqs.notification_email');
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $templating = $this->getContainer()->get('templating');
        $translator = $this->getContainer()->get('translator');
        $mailer = $this->getContainer()->get('mailer');
        $output->writeln('');
        $output->writeln('notification email pending...');
        $env = $input->getOption('env');

        while(true) {
            $result = $awsService->receiveMessages($queueUrl);
            $message = $result->get('Messages');
            $sportForecastId = (int) $message[0]['Body'];
            $receiptHandle = $message[0]['ReceiptHandle'];

            // start process
            if ($sportForecastId !== 0) {
                // get entity
                $sportForecast = $em->getRepository('AppBundle:SportForecast')->find($sportForecastId);
                $output->writeln('send email for sport forecast ID: ' . $sportForecast->getId());

                // get tipster subscriber
                $subscriptions = $em->getRepository('AppBundle:Subscription')
                    ->findBy([
                        'tipster' => $sportForecast->getTipster()
                    ]);
                $output->writeln('numbers of users find: ' . count($subscriptions));

                $text = $sportForecast->getIsVip() ? $translator->trans('subject.vip_sport_forecast') : $translator->trans('subject.free_sport_forecast');

                $subject = sprintf('%s - %s #%s de %s !',
                    $this->getContainer()->getParameter('app_name'),
                    $text,
                    $sportForecast->getId(),
                    $sportForecast->getTipster()->getUser()->getNickname()
                );

                $body = $templating->render('@BackgroundJob/Emails/sport_forecast.html.twig', [
                    'sportForecast' => $sportForecast
                ]);

                foreach ($subscriptions as $subscription) {

                    if ($sportForecast->getIsVip() === false || $subscription->getEmailNotification() === true) {
                        $message = new \Swift_Message();
                        $message->setFrom('testing@prono-bet.com')
                            ->setTo($subscription->getUser()->getEmail())
                            ->setSubject($subject)
                            ->setBody(
                                $body,
                                'text/html'
                            );

                        $mailer->send($message);
                        $output->writeln('email sent to ' . $subscription->getUser()->getEmail());
                    }
                }

                $transport = $mailer->getTransport();

                if ($transport instanceof \Swift_Transport_SpoolTransport) {
                    $spool = $transport->getSpool();
                    $sent = $spool->flushQueue($this->getContainer()->get('swiftmailer.transport.real'));
                    $logger->info(sprintf('%s email sent for sportForecastID: %s', $sent, $sportForecastId));
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
