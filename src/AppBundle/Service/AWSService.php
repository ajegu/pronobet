<?php

namespace AppBundle\Service;


use Aws\Credentials\Credentials;
use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;

class AWSService
{
    private $credentials;
    private $region;
    private $version;
    private $senderId;
    private $sqsClient;

    /**
     * AWSService constructor.
     * @param $key
     * @param $secret
     * @param $region
     * @param $version
     * @param $senderId
     */
    public function __construct($key, $secret, $region, $version, $senderId)
    {
        $this->credentials = new Credentials($key, $secret);
        $this->region = $region;
        $this->version = $version;
        $this->senderId = $senderId;
    }

    /**
     * @return SnsClient
     */
    private function createSnsClient()
    {
        $snsClient = new SnsClient([
            'credentials' => $this->credentials,
            'region' => $this->region,
            'version' => 'latest'
        ]);

        return $snsClient;

    }

    /**
     * @param $message
     * @param $phoneNumber
     * @return \Aws\Result
     */
    public function sendSms($message, $phoneNumber)
    {
        $snsClient = $this->createSnsClient();
        $result = $snsClient->publish([
            'SenderID' => $this->senderId,
            'SMSType' => 'Promotional',
            'Message' => $message,
            'PhoneNumber' => $phoneNumber
        ]);

        return $result;
    }

    /**
     * @return SqsClient
     */
    public function createSqsClient()
    {
        if ($this->sqsClient === null) {
            $this->sqsClient = new SqsClient([
                'credentials' => $this->credentials,
                'region' => $this->region,
                'version' => $this->version
            ]);
        }
        return $this->sqsClient;
    }

    /**
     * @param $queueUrl
     */
    public function purgeQueue($queueUrl)
    {
        $sqsClient = $this->createSqsClient();
        $sqsClient->purgeQueue([
            'QueueUrl' => $queueUrl
        ]);
    }

    /**
     * @param $message
     * @param $queueUrl
     * @param int $delay
     */
    public function sendMessage($message, $queueUrl, $delay = 0)
    {

        $sqsClient = $this->createSqsClient();

        $sqsClient->sendMessage([
            'QueueUrl' => $queueUrl,
            'MessageBody' => $message,
            'DelaySeconds' => $delay,
        ]);
    }

    /**
     * @param $queueUrl
     * @return \Aws\Result
     */
    public function receiveMessages($queueUrl)
    {
        $sqsClient = $this->createSqsClient();

        $result = $sqsClient->receiveMessage([
            'QueueUrl' => $queueUrl
        ]);

        return $result;
    }

    /**
     * @param $queueUrl
     * @param $receiptHandle
     */
    public function deleteMessage($queueUrl, $receiptHandle)
    {
        $sqsClient = $this->createSqsClient();

        $sqsClient->deleteMessage([
            'QueueUrl' => $queueUrl,
            'ReceiptHandle' => $receiptHandle,
        ]);
    }
}