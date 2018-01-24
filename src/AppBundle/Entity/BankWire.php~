<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BankWire
 *
 * @ORM\Table(name="bank_wire")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BankWireRepository")
 */
class BankWire
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
     * @ORM\Column(name="mango_pay_payout_id", type="string", length=100)
     */
    private $mangoPayPayoutId;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Tipster
     *
     * @ORM\ManyToOne(targetEntity="Tipster", inversedBy="bankWires")
     * @ORM\JoinColumn(name="tipster_id", referencedColumnName="id")
     */
    private $tipster;


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
     * Set mangoPayPayoutId
     *
     * @param string $mangoPayPayoutId
     *
     * @return BankWire
     */
    public function setMangoPayPayoutId($mangoPayPayoutId)
    {
        $this->mangoPayPayoutId = $mangoPayPayoutId;

        return $this;
    }

    /**
     * Get mangoPayPayoutId
     *
     * @return string
     */
    public function getMangoPayPayoutId()
    {
        return $this->mangoPayPayoutId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return BankWire
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
     * Set type
     *
     * @param string $type
     *
     * @return BankWire
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set tipster
     *
     * @param \AppBundle\Entity\Tipster $tipster
     *
     * @return BankWire
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
}
