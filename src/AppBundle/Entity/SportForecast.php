<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * SportForecast
 *
 * @ORM\Table(name="sport_forecast")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SportForecastRepository")
 *
 * @Vich\Uploadable
 */
class SportForecast
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
     * @ORM\Column(name="title", type="string", length=100, nullable=true)
     */
    private $title;

    /**
     * @var float
     *
     * @ORM\Column(name="betting", type="float", nullable=true)
     */
    private $betting;

    /**
     * @var string
     *
     * @ORM\Column(name="ticket", type="string", length=255, nullable=true)
     */
    private $ticket;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="s3_medias", fileNameProperty="ticket")
     */
    private $ticketFile;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_vip", type="boolean")
     */
    private $isVip;


    /**
     * @var bool
     *
     * @ORM\Column(name="is_validate", type="boolean")
     */
    private $isValidate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="published_at", type="datetime", nullable=true)
     */
    private $publishedAt;

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
     * @var Tipster
     *
     * @ORM\ManyToOne(targetEntity="Tipster", inversedBy="sportForecasts")
     * @ORM\JoinColumn(name="tipster_id", referencedColumnName="id")
     */
    private $tipster;

    /**
     * @var Bookmaker
     *
     * @ORM\ManyToOne(targetEntity="Bookmaker", inversedBy="sportForecasts")
     * @ORM\JoinColumn(name="bookmaker_id", referencedColumnName="id")
     */
    private $bookmaker;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="SportBet", mappedBy="sportForecast", cascade={"persist", "remove"})
     */
    private $sportBets;

    /**
     * @var ArrayCollection
     *
     *  @ORM\OneToMany(targetEntity="Comment", mappedBy="sportForecast")
     */
    private $comments;


    /**
     * SportForecast constructor.
     */
    public function __construct(Tipster $tipster)
    {
        $this->tipster = $tipster;
        $this->sportBets = new ArrayCollection();
        $this->isValidate = false;
        $this->createdAt = new \DateTime();
        $this->comments = new ArrayCollection();
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
     * Set isVip
     *
     * @param boolean $isVip
     *
     * @return SportForecast
     */
    public function setIsVip($isVip)
    {
        $this->isVip = $isVip;

        return $this;
    }

    /**
     * Get isVip
     *
     * @return bool
     */
    public function getIsVip()
    {
        return $this->isVip;
    }


    /**
     * Set isValidate
     *
     * @param boolean $isValidate
     *
     * @return SportForecast
     */
    public function setIsValidate($isValidate)
    {
        $this->isValidate = $isValidate;

        return $this;
    }

    /**
     * Get isValidate
     *
     * @return bool
     */
    public function getIsValidate()
    {
        return $this->isValidate;
    }

    /**
     * Set publishedAt
     *
     * @param \DateTime $publishedAt
     *
     * @return SportForecast
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * Get publishedAt
     *
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return SportForecast
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
     * @return SportForecast
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
     * Set tipster
     *
     * @param \AppBundle\Entity\Tipster $tipster
     *
     * @return SportForecast
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
     * Set ticket
     *
     * @param string $ticket
     *
     * @return SportForecast
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * Get ticket
     *
     * @return string
     */
    public function getTicket()
    {
        return $this->ticket;
    }


    /**
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return SportForecast
     */
    public function setTicketFile(File $image = null)
    {
        $this->ticketFile = $image;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getTicketFile()
    {
        return $this->ticketFile;
    }

    /**
     * Set betting
     *
     * @param float $betting
     *
     * @return SportForecast
     */
    public function setBetting($betting)
    {
        $this->betting = $betting;

        return $this;
    }

    /**
     * Get betting
     *
     * @return float
     */
    public function getBetting()
    {
        return $this->betting;
    }

    /**
     * Add sportBet
     *
     * @param \AppBundle\Entity\SportBet $sportBet
     *
     * @return SportForecast
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

    /**
     * Set title
     *
     * @param string $title
     *
     * @return SportForecast
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get isValid
     *
     * @return bool
     */
    public function isPublishable()
    {
        if (count($this->getSportBets()) > 0) {
            foreach ($this->getSportBets() as $sportBet) {
                if ($sportBet->getplayedAt() < new \DateTime()) {
                    return false;
                }
            }
        } else {
            return false;
        }

        if ($this->publishedAt !== null) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getGlobalRating()
    {
        $rating = 1;
        foreach ($this->getSportBets() as $sportBet) {
            if ($sportBet->getCancelled() === false) {
                $rating *= $sportBet->getRating();
            }
        }
        return round($rating, 2);
    }

    /**
     * @return int
     */
    public function getWinning()
    {
        return round($this->getGlobalRating() * $this->betting, 2);
    }

    /**
     * @return bool
     */
    public function isEditable()
    {
        if ($this->publishedAt !== null) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isValid()
    {

        if ($this->isValidate === true) {
            return false;
        }

        if ($this->isEditable() == false) {
            $validable = true;
            $now = new \DateTime();
            foreach ($this->getSportBets() as $sportBet) {
                if ($sportBet->getPlayedAt() > $now) {
                    $validable = false;
                    break;
                }
            }
            return $validable;
        }
        return false;
    }

    public function calculateBankroll()
    {
        if ($this->isCancelled() === false) {
            $isWon = $this->isWon();
            if ($isWon == false) {
                $bankroll = $this->tipster->getBankroll() - $this->betting;
                if ($bankroll < 0) {
                    $bankroll = 0;
                }
            } else {
                $bankroll = $this->tipster->getBankroll() + $this->getWinning();
            }
        }

        return $bankroll;
    }

    public function isWon()
    {
        $isWon = true;
        foreach ($this->getSportBets() as $sportBet) {
            if ($sportBet->getCancelled() === false) {
                if ($sportBet->getIsWon() === false) {
                    $isWon = false;
                    break;
                }
            }
        }
        return $isWon;
    }

    /**
     * Add comment
     *
     * @param \AppBundle\Entity\Comment $comment
     *
     * @return SportForecast
     */
    public function addComment(\AppBundle\Entity\Comment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \AppBundle\Entity\Comment $comment
     */
    public function removeComment(\AppBundle\Entity\Comment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    public function averageConfidenceIndex()
    {
        $av = 0;
        $sportBets = $this->getSportBets();
        foreach ($sportBets as $sportBet) {
            $av += $sportBet->getConfidenceIndex();
        }

        return round($av / count($sportBets));
    }

    /**
     * Set bookmaker
     *
     * @param \AppBundle\Entity\Bookmaker $bookmaker
     *
     * @return SportForecast
     */
    public function setBookmaker(\AppBundle\Entity\Bookmaker $bookmaker = null)
    {
        $this->bookmaker = $bookmaker;

        return $this;
    }

    /**
     * Get bookmaker
     *
     * @return \AppBundle\Entity\Bookmaker
     */
    public function getBookmaker()
    {
        return $this->bookmaker;
    }

    /**
     * Check if all sport bet were cancelled
     */
    public function isCancelled()
    {
        foreach ($this->sportBets as $sportBet) {
            if ($sportBet->getCancelled() === false) {
                return false;
            }
        }

        return true;
    }
}
