<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Tipster
 *
 * @ORM\Table(name="tipster")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TipsterRepository")
 *
 * @Vich\Uploadable
 *
 */
class Tipster
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
     * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     */
    private $picture;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="s3_medias", fileNameProperty="picture")
     */
    private $pictureFile;

    /**
     * @var string
     *
     * @ORM\Column(name="cover", type="string", length=255, nullable=true)
     */
    private $cover;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="s3_medias", fileNameProperty="cover")
     */
    private $coverFile;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(name="fee", type="float")
     */
    private $fee;

    /**
     * @var float
     *
     * @ORM\Column(name="commission", type="float")
     */
    private $commission;


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
     * @ORM\OneToOne(targetEntity="User", inversedBy="tipster")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="SportForecast", mappedBy="tipster")
     */
    private $sportForecasts;

    /**
     * @var Array
     */
    private $stats;

    /**
     * @var string
     *
     * @ORM\Column(name="mangopay_id", type="string", length=20, nullable=true)
     */
    private $mangoPayId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="mangopay_created_at", type="datetime", nullable=true)
     */
    private $mangoPayCreatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="mangopay_wallet_id", type="string", length=20, nullable=true)
     */
    private $mangoPayWalletId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="mangopay_bank_account_id", type="string", length=20, nullable=true)
     */
    private $mangoPayBankAccountId;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Subscription", mappedBy="tipster")
     */
    private $subscriptions;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BankWire", mappedBy="tipster")
     */
    private $bankWires;

    /**
     * @var boolean
     *
     * @ORM\Column(name="check_kyc", type="boolean")
     */
    private $checkKYC;

    /**
     * @var string
     *
     * @ORM\Column(name="mangopay_identity_proof_id", type="string", length=20, nullable=true)
     */
    private $mangoPayIdentityProofId;

    /**
     * @var string
     *
     * @ORM\Column(name="mangopay_address_proof_id", type="string", length=20, nullable=true)
     */
    private $mangoPayAddressProofId;


    public function __construct(User $user )
    {
        $this->user = $user;
        $this->createdAt = new \DateTime();
        $this->sportForecasts = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->bankWires = new ArrayCollection();
        $this->checkKYC = false;
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
     * Set picture
     *
     * @param string $picture
     *
     * @return Tipster
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * Get picture
     *
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * Set cover
     *
     * @param string $cover
     *
     * @return Tipster
     */
    public function setCover($cover)
    {
        $this->cover = $cover;

        return $this;
    }

    /**
     * Get cover
     *
     * @return string
     */
    public function getCover()
    {
        return $this->cover;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Tipster
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set fee
     *
     * @param float $fee
     *
     * @return Tipster
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Get fee
     *
     * @return float
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Tipster
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Tipster
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
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Tipster
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
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Tipster
     */
    public function setPictureFile(File $image = null)
    {
        $this->pictureFile = $image;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getPictureFile()
    {
        return $this->pictureFile;
    }

    /**
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Tipster
     */
    public function setCoverFile(File $image = null)
    {
        $this->coverFile = $image;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getCoverFile()
    {
        return $this->coverFile;
    }

    /**
     * Add sportForecast
     *
     * @param \AppBundle\Entity\SportForecast $sportForecast
     *
     * @return Tipster
     */
    public function addSportForecast(\AppBundle\Entity\SportForecast $sportForecast)
    {
        $this->sportForecasts[] = $sportForecast;

        return $this;
    }

    /**
     * Remove sportForecast
     *
     * @param \AppBundle\Entity\SportForecast $sportForecast
     */
    public function removeSportForecast(\AppBundle\Entity\SportForecast $sportForecast)
    {
        $this->sportForecasts->removeElement($sportForecast);
    }

    /**
     * Get sportForecasts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSportForecasts()
    {
        return $this->sportForecasts;
    }

    /**
     * @return Array
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @param $stats
     */
    public function setStats($stats)
    {
        $this->stats = $stats;
    }

    /**
     * Add subscription
     *
     * @param \AppBundle\Entity\Subscription $subscription
     *
     * @return Tipster
     */
    public function addSubscription(\AppBundle\Entity\Subscription $subscription)
    {
        $this->subscriptions[] = $subscription;

        return $this;
    }

    /**
     * Remove subscription
     *
     * @param \AppBundle\Entity\Subscription $subscription
     */
    public function removeSubscription(\AppBundle\Entity\Subscription $subscription)
    {
        $this->subscriptions->removeElement($subscription);
    }

    /**
     * Get subscriptions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * Set commission
     *
     * @param float $commission
     *
     * @return Tipster
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;

        return $this;
    }

    /**
     * Get commission
     *
     * @return float
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * Set mangoPayId
     *
     * @param string $mangoPayId
     *
     * @return Tipster
     */
    public function setMangoPayId($mangoPayId)
    {
        $this->mangoPayId = $mangoPayId;

        return $this;
    }

    /**
     * Get mangoPayId
     *
     * @return string
     */
    public function getMangoPayId()
    {
        return $this->mangoPayId;
    }

    /**
     * Set mangoPayCreatedAt
     *
     * @param \DateTime $mangoPayCreatedAt
     *
     * @return Tipster
     */
    public function setMangoPayCreatedAt($mangoPayCreatedAt)
    {
        $this->mangoPayCreatedAt = $mangoPayCreatedAt;

        return $this;
    }

    /**
     * Get mangoPayCreatedAt
     *
     * @return \DateTime
     */
    public function getMangoPayCreatedAt()
    {
        return $this->mangoPayCreatedAt;
    }

    /**
     * Set mangoPayBankAccountId
     *
     * @param string $mangoPayBankAccountId
     *
     * @return Tipster
     */
    public function setMangoPayBankAccountId($mangoPayBankAccountId)
    {
        $this->mangoPayBankAccountId = $mangoPayBankAccountId;

        return $this;
    }

    /**
     * Get mangoPayBankAccountId
     *
     * @return string
     */
    public function getMangoPayBankAccountId()
    {
        return $this->mangoPayBankAccountId;
    }

    /**
     * Set mangoPayWalletId
     *
     * @param string $mangoPayWalletId
     *
     * @return Tipster
     */
    public function setMangoPayWalletId($mangoPayWalletId)
    {
        $this->mangoPayWalletId = $mangoPayWalletId;

        return $this;
    }

    /**
     * Get mangoPayWalletId
     *
     * @return string
     */
    public function getMangoPayWalletId()
    {
        return $this->mangoPayWalletId;
    }

    /**
     * Add bankWire
     *
     * @param \AppBundle\Entity\BankWire $bankWire
     *
     * @return Tipster
     */
    public function addBankWire(\AppBundle\Entity\BankWire $bankWire)
    {
        $this->bankWires[] = $bankWire;

        return $this;
    }

    /**
     * Remove bankWire
     *
     * @param \AppBundle\Entity\BankWire $bankWire
     */
    public function removeBankWire(\AppBundle\Entity\BankWire $bankWire)
    {
        $this->bankWires->removeElement($bankWire);
    }

    /**
     * Get bankWires
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBankWires()
    {
        return $this->bankWires;
    }

    /**
     * Set checkKYC
     *
     * @param boolean $checkKYC
     *
     * @return Tipster
     */
    public function setCheckKYC($checkKYC)
    {
        $this->checkKYC = $checkKYC;

        return $this;
    }

    /**
     * Get checkKYC
     *
     * @return boolean
     */
    public function getCheckKYC()
    {
        return $this->checkKYC;
    }

    /**
     * Set mangoPayIdentityProofId
     *
     * @param string $mangoPayIdentityProofId
     *
     * @return Tipster
     */
    public function setMangoPayIdentityProofId($mangoPayIdentityProofId)
    {
        $this->mangoPayIdentityProofId = $mangoPayIdentityProofId;

        return $this;
    }

    /**
     * Get mangoPayIdentityProofId
     *
     * @return string
     */
    public function getMangoPayIdentityProofId()
    {
        return $this->mangoPayIdentityProofId;
    }

    /**
     * Set mangoPayAddressProofId
     *
     * @param string $mangoPayAddressProofId
     *
     * @return Tipster
     */
    public function setMangoPayAddressProofId($mangoPayAddressProofId)
    {
        $this->mangoPayAddressProofId = $mangoPayAddressProofId;

        return $this;
    }

    /**
     * Get mangoPayAddressProofId
     *
     * @return string
     */
    public function getMangoPayAddressProofId()
    {
        return $this->mangoPayAddressProofId;
    }
}
