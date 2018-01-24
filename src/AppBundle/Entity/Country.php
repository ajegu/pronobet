<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country
 *
 * @ORM\Table(name="country")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CountryRepository")
 */
class Country
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
     * @ORM\Column(name="alpha2", type="string", length=2)
     */
    private $alpha2;

    /**
     * @var string
     *
     * @ORM\Column(name="alpha3", type="string", length=3)
     */
    private $alpha3;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, unique=true)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="phone_number_code", type="smallint", nullable=true)
     */
    private $phoneNumberCode;

    /**
     * @var bool
     *
     * @ORM\Column(name="sorting", type="smallint", nullable=true)
     */
    private $sorting;

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
     * Set alpha2
     *
     * @param string $alpha2
     *
     * @return Country
     */
    public function setAlpha2($alpha2)
    {
        $this->alpha2 = $alpha2;

        return $this;
    }

    /**
     * Get alpha2
     *
     * @return string
     */
    public function getAlpha2()
    {
        return $this->alpha2;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Country
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
     * Set alpha3
     *
     * @param string $alpha3
     *
     * @return Country
     */
    public function setAlpha3($alpha3)
    {
        $this->alpha3 = $alpha3;

        return $this;
    }

    /**
     * Get alpha3
     *
     * @return string
     */
    public function getAlpha3()
    {
        return $this->alpha3;
    }

    /**
     * Set sorting
     *
     * @param integer $sorting
     *
     * @return Country
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;

        return $this;
    }

    /**
     * Get sorting
     *
     * @return integer
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * Set phoneNumberCode
     *
     * @param integer $phoneNumberCode
     *
     * @return Country
     */
    public function setPhoneNumberCode($phoneNumberCode)
    {
        $this->phoneNumberCode = $phoneNumberCode;

        return $this;
    }

    /**
     * Get phoneNumberCode
     *
     * @return integer
     */
    public function getPhoneNumberCode()
    {
        return $this->phoneNumberCode;
    }
}
