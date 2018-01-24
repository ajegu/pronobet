<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Championship
 *
 * @ORM\Table(name="championship")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ChampionshipRepository")
 *
 * @UniqueEntity(
 *     fields= {"name", "sport"}
 * )
 *
 */
class Championship
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

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
     * @ORM\ManyToOne(targetEntity="Sport", inversedBy="championships")
     * @ORM\JoinColumn(name="sport_id", referencedColumnName="id")
     */
    private $sport;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="SportBet", mappedBy="championship")
     */
    private $sportBets;

    /**
     * Championship constructor.
     */
    public function __construct(Sport $sport)
    {
        $this->sport = $sport;
        $this->createdAt = new \DateTime();
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
     * @return Championship
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Championship
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
     * @return Championship
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
     * Set sport
     *
     * @param \AppBundle\Entity\Sport $sport
     *
     * @return Championship
     */
    public function setSport(\AppBundle\Entity\Sport $sport = null)
    {
        $this->sport = $sport;

        return $this;
    }

    /**
     * Get sport
     *
     * @return \AppBundle\Entity\Sport
     */
    public function getSport()
    {
        return $this->sport;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     *
     * @return Championship
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
     * Add sportBet
     *
     * @param \AppBundle\Entity\SportBet $sportBet
     *
     * @return Championship
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
