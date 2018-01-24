<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Bookmaker
 *
 * @ORM\Table(name="bookmaker")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BookmakerRepository")
 *
 * @UniqueEntity(fields = "name")
 *
 * @Vich\Uploadable
 */
class Bookmaker
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
     * @ORM\Column(name="name", type="string", length=100, unique=true)
     */
    private $name;


    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=255, nullable=true)
     */
    private $logo;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="s3_medias", fileNameProperty="logo")
     */
    private $logoFile;

    /**
     * @var int
     *
     * @ORM\Column(name="bonus", type="integer")
     */
    private $bonus;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="website_link", type="string", length=255)
     */
    private $websiteLink;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_link", type="string", length=255, nullable=true)
     */
    private $adLink;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_link", type="string", length=255, nullable=true)
     */
    private $facebookLink;

    /**
     * @var string
     *
     * @ORM\Column(name="twitter_link", type="string", length=255, nullable=true)
     */
    private $twitterLink;

    /**
     * @var string
     *
     * @ORM\Column(name="youtube_link", type="string", length=255, nullable=true)
     */
    private $youtubeLink;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean")
     */
    private $visible;

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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="SportForecast", mappedBy="bookmaker")
     */
    private $sportForecasts;

    /**
     * Bookmaker constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->sportForecasts = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
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
     * Set name
     *
     * @param string $name
     *
     * @return Bookmaker
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set logo
     *
     * @param string $logo
     *
     * @return Bookmaker
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set bonus
     *
     * @param integer $bonus
     *
     * @return Bookmaker
     */
    public function setBonus($bonus)
    {
        $this->bonus = $bonus;

        return $this;
    }

    /**
     * Get bonus
     *
     * @return int
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Bookmaker
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
     * Set websiteLink
     *
     * @param string $websiteLink
     *
     * @return Bookmaker
     */
    public function setWebsiteLink($websiteLink)
    {
        $this->websiteLink = $websiteLink;

        return $this;
    }

    /**
     * Get websiteLink
     *
     * @return string
     */
    public function getWebsiteLink()
    {
        return $this->websiteLink;
    }

    /**
     * Set adLink
     *
     * @param string $adLink
     *
     * @return Bookmaker
     */
    public function setAdLink($adLink)
    {
        $this->adLink = $adLink;

        return $this;
    }

    /**
     * Get adLink
     *
     * @return string
     */
    public function getAdLink()
    {
        return $this->adLink;
    }

    /**
     * Set facebookLink
     *
     * @param string $facebookLink
     *
     * @return Bookmaker
     */
    public function setFacebookLink($facebookLink)
    {
        $this->facebookLink = $facebookLink;

        return $this;
    }

    /**
     * Get facebookLink
     *
     * @return string
     */
    public function getFacebookLink()
    {
        return $this->facebookLink;
    }

    /**
     * Set twitterLink
     *
     * @param string $twitterLink
     *
     * @return Bookmaker
     */
    public function setTwitterLink($twitterLink)
    {
        $this->twitterLink = $twitterLink;

        return $this;
    }

    /**
     * Get twitterLink
     *
     * @return string
     */
    public function getTwitterLink()
    {
        return $this->twitterLink;
    }

    /**
     * Set youtubeLink
     *
     * @param string $youtubeLink
     *
     * @return Bookmaker
     */
    public function setYoutubeLink($youtubeLink)
    {
        $this->youtubeLink = $youtubeLink;

        return $this;
    }

    /**
     * Get youtubeLink
     *
     * @return string
     */
    public function getYoutubeLink()
    {
        return $this->youtubeLink;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     *
     * @return Bookmaker
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Bookmaker
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
     * @return Bookmaker
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
     * @param File|UploadedFile $image
     */
    public function setLogoFile(File $image = null)
    {
        $this->logoFile = $image;
        if ($image) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    /**
     * @return file|null
     */
    public function getLogoFile()
    {
        return $this->logoFile;
    }



    /**
     * Add sportForecast
     *
     * @param \AppBundle\Entity\SportForecast $sportForecast
     *
     * @return Bookmaker
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
}
