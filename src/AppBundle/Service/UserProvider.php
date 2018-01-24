<?php


namespace AppBundle\Service;


use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;

class UserProvider implements OAuthAwareUserProviderInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $user = $this->em->getRepository('AppBundle:User')
            ->findOneByEmail($response->getEmail());
        if ($user == null) {
            $user = new User();
            $user->setEmail($response->getEmail())
                ->setNickname($response->getNickname())
                ->setEmailValid(true)
            ;
            $this->em->persist($user);
            $this->em->flush();
        }
        return $user;
    }
}