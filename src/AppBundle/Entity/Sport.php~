<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Sport
 *
 * @ORM\Table(name="sport")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SportRepository")
 *
 * @UniqueEntity(fields = "name")
 *
 * @Vich\Uploadable
 *
 */
class Sport
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
     * @ORM\Column(name="icon", type="string", length=255, nullable=true)
     */
    private $icon;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="s3_medias", fileNameProperty="icon")
     */
    private $iconFile;

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
     * @ORM\OneToMany(targetEntity="Championship", mappedBy="sport")
     */
    private $championships;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="SportBet", mappedBy="sport")
     */
    private $sportBets;


    /**
     * Sport constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->championships = new ArrayCollection();
        $this->sportBets = new ArrayCollection();
    }

    /**
     * @return string
     */
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
     * @return Sport
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
     * Set icon
     *
     * @param string $icon
     *
     * @return Sport
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Sport
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
     * @return Sport
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
     * Add championship
     *
     * @param \AppBundle\Entity\Championship $championship
     *
     * @return Sport
     */
    public function addChampionship(\AppBundle\Entity\Championship $championship)
    {
        $this->championships[] = $championship;

        return $this;
    }

    /**
     * Remove championship
     *
     * @param \AppBundle\Entity\Championship $championship
     */
    public function removeChampionship(\AppBundle\Entity\Championship $championship)
    {
        $this->championships->removeElement($championship);
    }

    /**
     * Get championships
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChampionships()
    {
        return $this->championships;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     *
     * @return Sport
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Sport
     */
    public function setIconFile(File $image = null)
    {
        $this->iconFile = $image;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getIconFile()
    {
        return $this->iconFile;
    }


    /**
     * Add sportBet
     *
     * @param \AppBundle\Entity\SportBet $sportBet
     *
     * @return Sport
     */
    public function addSportBet(\AppBundle\Entity\SportBet $sportBet)
    {
        $this->sportBets[] = $sportBet;

        return $this;
    }

    /**
     * Remove sportBet
     *
     * @param \AppBundle\Entity\SportBet $sportBet
     */
    public function removeSportBet(\AppBundle\Entity\SportBet $sportBet)
    {
        $this->sportBets->removeElement($sportBet);
    }

    /**
     * Get sportBets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSportBets()
    {
        return $this->sportBets;
    }
}
