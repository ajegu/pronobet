<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Count;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @UniqueEntity(fields = "email")
 * @UniqueEntity(fields = "nickname")
 */
class User implements UserInterface, \Serializable
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
     * @ORM\Column(name="nickname", type="string", length=100, unique=true)
     */
    private $nickname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, unique=true)
     */
    private $email;

    /**
     * @var string
     *
     */
    private $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=64, nullable=true)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=20, nullable=true)
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=20, nullable=false)
     */
    private $role;

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
     * @var bool
     */
    private $confirm;

    /**
     * @var bool
     */
    private $partners;


    /**
     * @var string
     *
     */
    private $salt;

    /**
     * @var bool
     *
     * @ORM\Column(name="email_valid", type="boolean")
     */
    private $emailValid;

    /**
     * @var string
     *
     * @ORM\Column(name="reset_password_token", type="string", length=100, nullable=true)
     */
    private $resetPasswordToken;

    /**
     * @var Tipster
     *
     * @ORM\OneToOne(targetEntity="Tipster", mappedBy="user")
     *
     */
    private $tipster;

    /**
     * @var ArrayCollection
     *
     *  @ORM\OneToMany(targetEntity="Comment", mappedBy="user")
     */
    private $comments;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Subscription", mappedBy="user")
     */
    private $subscriptions;

    /**
     * @var Nationality
     *
     * @ORM\ManyToOne(targetEntity="Nationality")
     * @ORM\JoinColumn(name="nationality_id", referencedColumnName="id")
     */
    private $nationality;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=100, nullable=true)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=100, nullable=true)
     */
    private $lastName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="datetime", nullable=true)
     */
    private $birthday;

    /**
     * @var string
     *
     * @ORM\Column("address_line_1", type="string", length = 100, nullable=true)
     */
    private $addressLine1;

    /**
     * @var string
     *
     * @ORM\Column("address_line_2", type="string", length = 100, nullable=true)
     */
    private $addressLine2;

    /**
     * @var string
     *
     * @ORM\Column("postal_code", type="string", length = 10, nullable=true)
     */
    private $postalCode;

    /**
     * @var string
     *
     * @ORM\Column("city", type="string", length = 50, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column("occupation", type="string", length = 100, nullable=true)
     */
    private $occupation;

    /**
     * @var int
     *
     * @ORM\Column(name="income_range", type="integer", nullable=true)
     */
    private $incomeRange;


    /**
     * @var int
     *
     * @ORM\Column(name="mangopay_id", type="integer", nullable=true)
     */
    private $mangoPayId;

    /**
     * @var string
     *
     * @ORM\Column(name="mangopay_wallet_id", type="string", length=20, nullable=true)
     */
    private $mangoPayWalletId;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->emailValid = false;
        $this->createdAt = new \DateTime();
        $this->role = 'ROLE_MEMBER';
        $this->comments = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->email;
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
     * Trick for email identification
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * Set nickname
     *
     * @param string $nickname
     *
     * @return User
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Get nickname
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
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
     * Get plainPassword
     *
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Set plainPassword
     *
     * @param $password
     *
     * @return User
     */
    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set phoneNumber
     *
     * @param string $phoneNumber
     *
     * @return User
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber
     *
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * Get roles
     *
     * @return string
     */
    public function getRoles()
    {
        return array($this->role);
    }

    /**
     * Set salt
     *
     * @param string $salt
     *
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return null;
    }


    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->email,
            $this->password,
            // see section on salt below
            // $this->salt,
        ));
    }

    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->email,
            $this->password,
            // see section on salt below
            // $this->salt
            ) = unserialize($serialized);
    }

    /**
     * Set resetPasswordToken
     *
     * @param string $resetPasswordToken
     *
     * @return User
     */
    public function setResetPasswordToken($resetPasswordToken)
    {
        $this->resetPasswordToken = $resetPasswordToken;

        return $this;
    }

    /**
     * Get resetPasswordToken
     *
     * @return string
     */
    public function getResetPasswordToken()
    {
        return $this->resetPasswordToken;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return User
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
     * @return User
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
     * Set role
     *
     * @param string $role
     *
     * @return User
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set emailValid
     *
     * @param boolean $emailValid
     *
     * @return User
     */
    public function setEmailValid($emailValid)
    {
        $this->emailValid = $emailValid;

        return $this;
    }

    /**
     * Get emailValid
     *
     * @return boolean
     */
    public function getEmailValid()
    {
        return $this->emailValid;
    }
    

    /**
     * Set tipster
     *
     * @param \AppBundle\Entity\Tipster $tipster
     *
     * @return User
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
     * Add comment
     *
     * @param \AppBundle\Entity\Comment $comment
     *
     * @return User
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

    /**
     * Add subscription
     *
     * @param \AppBundle\Entity\Subscription $subscription
     *
     * @return User
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
     * @return mixed
     */
    public function getConfirm()
    {
        return $this->confirm;
    }

    /**
     * @param $confirm
     * @return $this
     */
    public function setConfirm($confirm)
    {
        $this->confirm = $confirm;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPartners()
    {
        return $this->partners;
    }

    /**
     * @param $partners
     * @return $this
     */
    public function setPartners($partners)
    {
        $this->partners = $partners;
        return $this;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     *
     * @return User
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set nationality
     *
     * @param \AppBundle\Entity\Nationality $nationality
     *
     * @return User
     */
    public function setNationality(\AppBundle\Entity\Nationality $nationality = null)
    {
        $this->nationality = $nationality;

        return $this;
    }

    /**
     * Get nationality
     *
     * @return \AppBundle\Entity\Nationality
     */
    public function getNationality()
    {
        return $this->nationality;
    }

    /**
     * Set country
     *
     * @param \AppBundle\Entity\Country $country
     *
     * @return User
     */
    public function setCountry(\AppBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \AppBundle\Entity\Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Check the user invoice informations.
     * Return true if all infos are OK.
     *
     * @return bool
     */
    public function checkUserInvoice()
    {
        if ($this->firstName === null) {
            return false;
        }

        if ($this->lastName === null) {
            return false;
        }

        if ($this->birthday === null) {
            return false;
        }

        if ($this->nationality === null) {
            return false;
        }

        if ($this->country === null) {
            return false;
        }

        return true;
    }

    /**
     * Set mangoPayId
     *
     * @param integer $mangoPayId
     *
     * @return User
     */
    public function setMangoPayId($mangoPayId)
    {
        $this->mangoPayId = $mangoPayId;

        return $this;
    }

    /**
     * Get mangoPayId
     *
     * @return integer
     */
    public function getMangoPayId()
    {
        return $this->mangoPayId;
    }

    /**
     * Set mangoPayWalletId
     *
     * @param string $mangoPayWalletId
     *
     * @return User
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
     * Set addressLine1
     *
     * @param string $addressLine1
     *
     * @return User
     */
    public function setAddressLine1($addressLine1)
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    /**
     * Get addressLine1
     *
     * @return string
     */
    public function getAddressLine1()
    {
        return $this->addressLine1;
    }

    /**
     * Set addressLine2
     *
     * @param string $addressLine2
     *
     * @return User
     */
    public function setAddressLine2($addressLine2)
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    /**
     * Get addressLine2
     *
     * @return string
     */
    public function getAddressLine2()
    {
        return $this->addressLine2;
    }

    /**
     * Set postalCode
     *
     * @param string $postalCode
     *
     * @return User
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get postalCode
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return User
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set occupation
     *
     * @param string $occupation
     *
     * @return User
     */
    public function setOccupation($occupation)
    {
        $this->occupation = $occupation;

        return $this;
    }

    /**
     * Get occupation
     *
     * @return string
     */
    public function getOccupation()
    {
        return $this->occupation;
    }

    /**
     * Set incomeRange
     *
     * @param integer $incomeRange
     *
     * @return User
     */
    public function setIncomeRange($incomeRange)
    {
        $this->incomeRange = $incomeRange;

        return $this;
    }

    /**
     * Get incomeRange
     *
     * @return integer
     */
    public function getIncomeRange()
    {
        return $this->incomeRange;
    }
}
