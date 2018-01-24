<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Subscription
 *
 * @ORM\Table(name="subscription")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SubscriptionRepository")
 */
class Subscription
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
     * @var String
     *
     * @ORM\Column(name="status", type="string", length=100)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="activate", type="boolean")
     */
    private $activate;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="fees", type="float")
     */
    private $fees;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="finished_at", type="datetime", nullable=true)
     */
    private $finishedAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="email_notification", type="boolean")
     */
    private $emailNotification;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sms_notification", type="boolean")
     */
    private $smsNotification;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;


    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="subscriptions")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var Tipster
     *
     * @ORM\ManyToOne(targetEntity="Tipster", inversedBy="subscriptions")
     * @ORM\JoinColumn(name="tipster_id", referencedColumnName="id")
     */
    private $tipster;

    /**
     * @var Payment
     *
     * @ORM\OneToOne(targetEntity="Payment", mappedBy="subscription")
     *
     */
    private $payment;


    /**
     * Subscription constructor.
     */
    public function __construct(Tipster $tipster, User $user)
    {
        $this->user = $user;
        $this->tipster = $tipster;
        $this->createdAt = new \DateTime();
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Subscription
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set finishedAt
     *
     * @param \DateTime $finishedAt
     *
     * @return Subscription
     */
    public function setFinishedAt($finishedAt)
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    /**
     * Get finishedAt
     *
     * @return \DateTime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Subscription
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set tipster
     *
     * @param \AppBundle\Entity\Tipster $tipster
     *
     * @return Subscription
     */
    public function setTipster(\AppBundle\Entity\Tipster $tipster = null)
    {
        $this->tipster = $tipster;

        return $this;
    }

    /**
     * Get tipster
     *
     * @return \AppBundle\Entity\Tipster
     */
    public function getTipster()
    {
        return $this->tipster;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Subscription
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return Subscription
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set fees
     *
     * @param float $fees
     *
     * @return Subscription
     */
    public function setFees($fees)
    {
        $this->fees = $fees;

        return $this;
    }

    /**
     * Get fees
     *
     * @return float
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Set emailNotification
     *
     * @param boolean $emailNotification
     *
     * @return Subscription
     */
    public function setEmailNotification($emailNotification)
    {
        $this->emailNotification = $emailNotification;

        return $this;
    }

    /**
     * Get emailNotification
     *
     * @return boolean
     */
    public function getEmailNotification()
    {
        return $this->emailNotification;
    }

    /**
     * Set smsNotification
     *
     * @param boolean $smsNotification
     *
     * @return Subscription
     */
    public function setSmsNotification($smsNotification)
    {
        $this->smsNotification = $smsNotification;

        return $this;
    }

    /**
     * Get smsNotification
     *
     * @return boolean
     */
    public function getSmsNotification()
    {
        return $this->smsNotification;
    }

    /**
     * Set payment
     *
     * @param \AppBundle\Entity\Payment $payment
     *
     * @return Subscription
     */
    public function setPayment(\AppBundle\Entity\Payment $payment = null)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Get payment
     *
     * @return \AppBundle\Entity\Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Subscription
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set activate
     *
     * @param boolean $activate
     *
     * @return Subscription
     */
    public function setActivate($activate)
    {
        $this->activate = $activate;

        return $this;
    }

    /**
     * Get activate
     *
     * @return boolean
     */
    public function getActivate()
    {
        return $this->activate;
    }
}
