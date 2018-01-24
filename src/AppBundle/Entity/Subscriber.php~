<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Subscriber
 *
 * @ORM\Table(name="subscriber")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SubscriberRepository")
 */
class Subscriber
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, unique=true)
     */
    private $email;

    /**
     * @var bool
     *
     * @ORM\Column(name="email_valid", type="boolean")
     */
    private $emailValid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="subscribed_at", type="datetime")
     */
    private $subscribedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="unsubscribed_at", type="datetime", nullable=true)
     */
    private $unsubscribedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="partners", type="boolean")
     */
    private $partners;

    /**
     * Subscriber constructor.
     */
    public function __construct()
    {
        $this->subscribedAt = new \DateTime();
        $this->emailValid = false;
        $this->partners = false;
    }


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Subscriber
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set emailValid
     *
     * @param boolean $emailValid
     *
     * @return Subscriber
     */
    public function setEmailValid($emailValid)
    {
        $this->emailValid = $emailValid;

        return $this;
    }

    /**
     * Get emailValid
     *
     * @return bool
     */
    public function getEmailValid()
    {
        return $this->emailValid;
    }

    /**
     * Set subscribedAt
     *
     * @param \DateTime $subscribedAt
     *
     * @return Subscriber
     */
    public function setSubscribedAt($subscribedAt)
    {
        $this->subscribedAt = $subscribedAt;

        return $this;
    }

    /**
     * Get subscribedAt
     *
     * @return \DateTime
     */
    public function getSubscribedAt()
    {
        return $this->subscribedAt;
    }

    /**
     * Set unsubscribedAt
     *
     * @param \DateTime $unsubscribedAt
     *
     * @return Subscriber
     */
    public function setUnsubscribedAt($unsubscribedAt)
    {
        $this->unsubscribedAt = $unsubscribedAt;

        return $this;
    }

    /**
     * Get unsubscribedAt
     *
     * @return \DateTime
     */
    public function getUnsubscribedAt()
    {
        return $this->unsubscribedAt;
    }

    /**
     * Set partners
     *
     * @param boolean $partners
     *
     * @return Subscriber
     */
    public function setPartners($partners)
    {
        $this->partners = $partners;

        return $this;
    }

    /**
     * Get partners
     *
     * @return bool
     */
    public function getPartners()
    {
        return $this->partners;
    }

}
