<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SportBet
 *
 * @ORM\Table(name="sport_bet")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SportBetRepository")
 */
class SportBet
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
     * @ORM\Column(name="winner", type="string", length=100)
     */
    private $winner;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="played_at", type="datetime", nullable=false)
     */
    private $playedAt;

    /**
     * @var float
     *
     * @ORM\Column(name="rating", type="float")
     */
    private $rating;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_won", type="boolean")
     */
    private $isWon;

    /**
     * @var string
     *
     * @ORM\Column(name="analysis", type="text", nullable=true)
     */
    private $analysis;

    /**
     * @var integer
     *
     * @ORM\Column(name="confidence_index", type="integer")
     */
    private $confidenceIndex;

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
     * @var SportForecast
     *
     * @ORM\ManyToOne(targetEntity="SportForecast", inversedBy="sportBets")
     * @ORM\JoinColumn(name="sport_forecast_id", referencedColumnName="id")
     */
    private $sportForecast;

    /**
     * @var Sport
     *
     * @ORM\ManyToOne(targetEntity="Sport", inversedBy="sportBets")
     * @ORM\JoinColumn(name="sport_id", referencedColumnName="id")
     */
    private $sport;

    /**
     * @var Championship
     *
     * @ORM\ManyToOne(targetEntity="Championship", inversedBy="sportBets")
     * @ORM\JoinColumn(name="championship_id", referencedColumnName="id")
     */
    private $championship;

    /**
     * @var bool
     *
     * @ORM\Column(name="cancelled", type="boolean")
     */
    private $cancelled;

    /**
     * SportBet constructor.
     */
    public function __construct(SportForecast $sportForecast)
    {
        $this->sportForecast = $sportForecast;
        $this->createdAt = new \DateTime();
        $this->isWon = false;
        $this->cancelled = false;
        $this->confidenceIndex = 0;
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
     * Set playedAt
     *
     * @param \DateTime $playedAt
     *
     * @return SportBet
     */
    public function setPlayedAt($playedAt)
    {
        $this->playedAt = $playedAt;

        return $this;
    }

    /**
     * Get playedAt
     *
     * @return \DateTime
     */
    public function getPlayedAt()
    {
        return $this->playedAt;
    }

    /**
     * Set rating
     *
     * @param float $rating
     *
     * @return SportBet
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return float
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set isWon
     *
     * @param boolean $isWon
     *
     * @return SportBet
     */
    public function setIsWon($isWon)
    {
        $this->isWon = $isWon;

        return $this;
    }

    /**
     * Get isWon
     *
     * @return bool
     */
    public function getIsWon()
    {
        return $this->isWon;
    }

    /**
     * Set analysis
     *
     * @param string $analysis
     *
     * @return SportBet
     */
    public function setAnalysis($analysis)
    {
        $this->analysis = $analysis;

        return $this;
    }

    /**
     * Get analysis
     *
     * @return string
     */
    public function getAnalysis()
    {
        return $this->analysis;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return SportBet
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
     * @return SportBet
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
     * Set sportForecast
     *
     * @param \AppBundle\Entity\SportForecast $sportForecast
     *
     * @return SportBet
     */
    public function setSportForecast(\AppBundle\Entity\SportForecast $sportForecast = null)
    {
        $this->sportForecast = $sportForecast;

        return $this;
    }

    /**
     * Get sportForecast
     *
     * @return \AppBundle\Entity\SportForecast
     */
    public function getSportForecast()
    {
        return $this->sportForecast;
    }

    /**
     * Set sport
     *
     * @param \AppBundle\Entity\Sport $sport
     *
     * @return SportBet
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
     * Set championship
     *
     * @param \AppBundle\Entity\Championship $championship
     *
     * @return SportBet
     */
    public function setChampionship(\AppBundle\Entity\Championship $championship = null)
    {
        $this->championship = $championship;

        return $this;
    }

    /**
     * Get championship
     *
     * @return \AppBundle\Entity\Championship
     */
    public function getChampionship()
    {
        return $this->championship;
    }

    /**
     * Set winner
     *
     * @param string $winner
     *
     * @return SportBet
     */
    public function setWinner($winner)
    {
        $this->winner = $winner;

        return $this;
    }

    /**
     * Get winner
     *
     * @return string
     */
    public function getWinner()
    {
        return $this->winner;
    }

    /**
     * Check played date
     *
     * @return bool
     */
    public function checkDate()
    {
        if ($this->playedAt < new \DateTime()) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isEditable()
    {
        return $this->sportForecast->isEditable();
    }

    /**
     * Set confidenceIndex
     *
     * @param integer $confidenceIndex
     *
     * @return SportBet
     */
    public function setConfidenceIndex($confidenceIndex)
    {
        $this->confidenceIndex = $confidenceIndex;

        return $this;
    }

    /**
     * Get confidenceIndex
     *
     * @return integer
     */
    public function getConfidenceIndex()
    {
        return $this->confidenceIndex;
    }

    /**
     * Set cancelled
     *
     * @param boolean $cancelled
     *
     * @return SportBet
     */
    public function setCancelled($cancelled)
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    /**
     * Get cancelled
     *
     * @return boolean
     */
    public function getCancelled()
    {
        return $this->cancelled;
    }
}
