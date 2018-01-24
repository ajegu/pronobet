<?php

namespace FakeBundle\Command;

use AppBundle\Entity\Bookmaker;
use AppBundle\Entity\Championship;
use AppBundle\Entity\Country;
use AppBundle\Entity\Nationality;
use AppBundle\Entity\Sport;
use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use Aws\S3\S3Client;
use ForecastBundle\Service\TipsterService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FakePopulateCommand extends ContainerAwareCommand
{
    private $em;

    protected function configure()
    {
        $this
            ->setName('fake:populate')
            ->setDescription('this command populate the database with fake data')
            ->addArgument('entity', InputArgument::OPTIONAL, 'specify the entity to populate')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();

        $entity = $input->getArgument('entity');

        switch ($entity) {
            case 'admin':
                $this->createAdmin($output);
                break;
            case 'member':
                $this->createMembers($output);
                break;
            case 'sport':
                $this->createSports($output);
                break;
            case 'championship':
                $this->createChampionships($output);
                break;
            case 'sport_forecast':
                $this->generateSportForecasts($output);
                break;
            case 'bookmaker':
                $this->createBookmakers($output);
                break;
            case 'country':
                $this->importCountry($output);
                break;
            default:
                $this->createAdmin($output);
                $this->createMembers($output);
                $this->createSports($output);
                $this->createChampionships($output);
                $this->createBookmakers($output);
                $this->generateSportForecasts($output);
                $this->importCountry($output);
                break;
        }

        if ($input->getOption('option')) {
            // ...
        }

        $output->writeln(chr(10) . 'Execution end');
    }

    private function createAdmin(OutputInterface $output)
    {
        $output->writeln(chr(10) . 'Admin import');

        $email = $this->getContainer()->getParameter('test_admin_account_email');
        $nickname = $this->getContainer()->getParameter('test_admin_account_nickname');
        $plainPassword = $this->getContainer()->getParameter('test_admin_account_password');

        $admin = new User();
        $admin->setEmail($email)
            ->setNickname($nickname)
            ->setRole('ROLE_ADMIN');

        $encoder = $this->getContainer()->get('security.password_encoder');
        $password = $encoder->encodePassword($admin, $plainPassword);
        $admin->setPassword($password);

        $this->em->persist($admin);
        $this->em->flush($admin);
    }

    private function createMembers(OutputInterface $output)
    {
        $output->writeln(chr(10) . 'Members import');

        //$file = file_get_contents('https://randomapi.com/api/6de6abfedb24f889e0b5f675edc50deb?fmt=raw&sole');
        //$users = json_decode($file);

        $users = [];
        $users[] = (object)[
            'email' => 'user-free@prono-bet.com',
            'first' => 'user-free'
        ];
        $users[] = (object)[
            'email' => 'user-vip@prono-bet.com',
            'first' => 'user-vip'
        ];

        $progress = new ProgressBar($output, count($users));

        $cpt = 0;

        foreach ($users as $user) {
            $member = new User();
            $member->setEmail($user->email);
            $member->setNickname($user->first . rand(1, 100));

            $encoder = $this->getContainer()->get('security.password_encoder');
            $password = $encoder->encodePassword($member, $user->email);
            $member->setPassword($password);

            if (rand(0, 5) === 5) {
                $member->setRole('ROLE_SUBSCRIBER');
            }

            $this->em->persist($member);
            $this->em->flush($member);

            $progress->advance();
            $cpt++;

            if ($cpt > 50) {
                break;
            }
        }

        $progress->finish();
    }

    private function createSports(OutputInterface $output)
    {

        $awsS3 = $this->getContainer()->get('aws_s3.client');
        $localPath = __DIR__ . '/../Resources/images/Sport/';

        $output->writeln(chr(10) . 'Sports import');

        $sports = [
            [
                'name' => 'Football',
                'visible' => true,
                'icon' => 'football.png'
            ], [
                'name' => 'Basketball',
                'visible' => true,
                'icon' => 'basketball.png'
            ], [
                'name' => 'Tennis',
                'visible' => true,
                'icon' => 'tennis.png'
            ], [
                'name' => 'Golf',
                'visible' => false,
                'icon' => 'golf.png'
            ], [
                'name' => 'Baseball',
                'visible' => true,
                'icon' => 'baseball.png'
            ]];

        $progress = new ProgressBar($output, count($sports));

        foreach ($sports as $data) {
            $sport = new Sport();
            $sport->setName($data['name'])
                ->setIcon($data['icon'])
                ->setVisible($data['visible']);

            $this->em->persist($sport);
            $this->em->flush($sport);

            $awsS3->putObject([
                'Bucket' => $this->getContainer()->getParameter('aws_s3.bucket_medias'),
                'Key' =>  $sport->getIcon(),
                'SourceFile' => $localPath . $sport->getIcon(),
                'ACL'          => 'public-read',
            ]);

            $progress->advance();
        }

        $progress->finish();
    }

    private function createChampionships(OutputInterface $output)
    {
        $output->writeln(chr(10) . 'Championships import');

        $filePath = __DIR__ . '/../Resources/csv/championships.csv';

        if (($handle = fopen($filePath, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $sport = $this->em->getRepository('AppBundle:Sport')
                    ->findOneByName($data[0]);

                if ($sport !== null) {
                    $championship = new Championship($sport);
                    $championship->setName($data[1])
                        ->setVisible($data[2]);

                    $this->em->persist($championship);
                    $this->em->flush($championship);
                }
            }
            fclose($handle);
        }

    }

    public function createBookmakers(OutputInterface $output)
    {
        $awsS3 = $this->getContainer()->get('aws_s3.client');
        $localPath = __DIR__ . '/../Resources/images/Bookmaker/';

        $bookmakers = [
            [
                'name' => 'BetClic',
                'logo' => 'betclic.jpg',
                'bonus' => 0,
                'webSiteLink' => 'https://www.betclic.fr/',
                'visible' => true,
                'description' => 'Betclic est un site web français de paris sportifs créé en 2005 et basé à Malte'
            ], [
                'name' => 'Winamax',
                'logo' => 'winamax.png',
                'bonus' => 0,
                'webSiteLink' => 'https://www.winamax.fr/',
                'visible' => true,
                'description' => 'Winamax est un site web français de poker et paris sportifs en ligne.'
            ], [
                'name' => 'Parions Sport',
                'logo' => 'parionssport.png',
                'bonus' => 0,
                'webSiteLink' => 'https://www.enligne.parionssport.fdj.fr/',
                'visible' => true,
                'description' => 'ParionsSport En Ligne - anciennement ParionsWeb - le site de paris sportifs en ligne de FDJ'
            ]
        ];

        $output->writeln(chr(10) . 'Bookmaker import');
        $progress = new ProgressBar($output, count($bookmakers));

        foreach ($bookmakers as $data) {
            $bookmaker = new Bookmaker();
            $bookmaker->setName($data['name'])
                ->setLogo($data['logo'])
                ->setBonus($data['bonus'])
                ->setWebsiteLink($data['webSiteLink'])
                ->setVisible($data['visible'])
                ->setDescription($data['description']);

            $this->em->persist($bookmaker);
            $this->em->flush();

            $awsS3->putObject([
                'Bucket' => $this->getContainer()->getParameter('aws_s3.bucket_medias'),
                'Key' =>  $bookmaker->getLogo(),
                'SourceFile' => $localPath . $bookmaker->getLogo(),
                'ACL'          => 'public-read',
            ]);

            $progress->advance();
        }
        $progress->finish();
    }

    public function generateSportForecasts(OutputInterface $output)
    {
        $output->writeln(chr(10) . 'Sport Forecast generating');

        $total = 100;

        $awsS3 = $this->getContainer()->get('aws_s3.client');
        $localPath = __DIR__ . '/../Resources/images/SportForecast/';
        $s3Tickets = [];


        $tipsters = ['pro-prono', 'best_tips', 'Allprono', 'free-win'];

        $localTipsterPath = __DIR__ . '/../Resources/images/Tipster/';
        //$coverWebPath = $this->getContainer()->get('kernel')->getRootDir() . '/../web/medias/images/tipsters/covers/';
        //$pictureWebPath = $this->getContainer()->get('kernel')->getRootDir() . '/../web/medias/images/tipsters/pictures/';
/*
        if (!file_exists($coverWebPath)) {
            mkdir($coverWebPath, 0777, true);
        }

        if (!file_exists($pictureWebPath)) {
            mkdir($pictureWebPath, 0777, true);
        }
*/
        $tickets = ['combi_01.jpg', 'combi_02.jpg', 'simple_01.jpg', 'rjprono_01.jpg', 'iscopronos_01.jpg', '', '', '', ''];

        $sports = [
            [
                'name' => 'Football',
                'championships' => [
                    '', '', '', 'Bulgarie Premier L.', 'Croatie 1. HNL', 'Pologne Ekstraklasa', 'Angl. Premier League', 'Ligue 1', 'Ligue 2'
                ],
                'winners' => [
                    'Arsenal', 'Beveren', 'Standard Liège', 'Piast Gliwice', 'Zaglebie Lubin', 'Olimpija Ljubljana', 'AC Horsens'
                ],
                'titles' => [
                    '', '', '', 'USM Alger - MC Oran', 'Nordsjaelland - SønderjyskE', 'Brann-Bergen - Sandefjord', 'Vålerenga - Kristiansund', 'Arsenal - Sunderland'
                ]
            ], [
                'name' => 'Tennis',
                'championships' => [
                    '', '', '', 'Rome WTA', 'Rome Masters ATP', 'Roland-Garros F.', 'Roland-Garros H.', 'Wimbledon H.'
                ],
                'winners' => [
                    'David Ferrer', 'Pablo Carreno-Busta', 'Ernesto Escobedo', 'Kristina Mladenovic', 'Lauren Davis', 'Nick Kyrgios', 'Aljaz Bedene', 'Tomas Berdych', 'Katerina Siniakova'
                ],
                'titles' => [
                    '', '', '', 'Katerina Siniakova - Svetlana Kuznetsova', 'Timea Bacsinszky - Timea Babos', 'Florian Mayer - John Isner', 'Tomas Berdych - Carlos Berlocq', 'Aljaz Bedene - Novak Djokovic', 'Nick Kyrgios - Roberto Bautista-Agut'
                ]
            ], [
                'name' => 'Baseball',
                'championships' => [
                    '', 'Major League'
                ],
                'winners' => [
                    'TOR Blue Jays', 'CLE Indians', 'MIA Marlins', 'DET Tigers', 'MIN Twins', 'SEA Mariners', 'SF Giants'
                ],
                'titles' => [
                    '', '', 'CLE Indians (D. Salazar) - TB Rays (J. Odorizzi)', 'PIT Pirates (C. Kuhl) - WAS Nationals (S. Strasburg)', 'MIA Marlins (T. Koehler) - HOU Astros (D. Keuchel)', 'DET Tigers (M. Boyd) - BAL Orioles (W. Miley)', 'TEX Rangers (Y. Darvish) - PHI Phillies (J. Eickhoff)', 'MIN Twins (P. Hughes) - COL Rockies (K. Freeland)'
                ]
            ], [
                'name' => 'Basketball',
                'championships' => [
                    '', '', 'Allemagne Bundesliga', 'Italie Serie A', 'Italie Serie A2', 'Espagne LEB', 'NBA'
                ],
                'winners' => [
                    'Golden State Warriors', 'Pistoia', 'Reggio Emilia', 'Dinamo Sassari', 'C.B. Breogan Lugo', 'Alba Berlin', 'EnBW Ludwigsburg', 'Telekom Baskets Bonn'
                ],
                'titles' => [
                    '', '', 'Telekom Baskets Bonn - Brose Bamberg', 'EnBW Ludwigsburg - Ratiopharm Ulm', 'Alba Berlin - Bayern Munich', 'C.B. Breogan Lugo - Palma Air Europa', 'Golden State Warriors - San Antonio Spurs'
                ]
            ], [
                'name' => 'Hockey sur glace',
                'championships' => [
                    '', '', 'Championnat du Monde', 'NHL', 'Suède Hockeyligan'
                ],
                'winners' => [
                    'Biélorussie', 'Suède', 'République Tchèque', 'Canada', 'Allemagne', 'Nashville Predators'
                ],
                'titles' => [
                    '', '', 'Nashville Predators - Anaheim Ducks', 'Canada - Finlande', 'Biélorussie - Norvège', 'République Tchèque - Suisse'
                ]
            ], [
                'name' => 'Boxe',
                'championships' => [
                    '', '', ''
                ],
                'winners' => [
                    'Gervonta Davis', 'Liam Walsh', 'Terence Crawford', 'Felix Diaz', 'Gary Russell Jr', 'Oscar Escandon', 'George Groves', 'Fedor Chudinov', 'Kell Brook'
                ],
                'titles' => [
                    '', '', 'Gervonta Davis - Liam Walsh', 'Terence Crawford - Felix Diaz', 'Gary Russell Jr - Oscar Escandon', 'George Groves - Fedor Chudinov', 'Kell Brook - Errol Spence Jr'
                ]
            ]
        ];

        $bookmakers = $this->em->getRepository('AppBundle:Bookmaker')
            ->findAll();

        $progress = new ProgressBar($output, $total);

        for ($i = 0; $i < $total; $i++) {

            // Generate tipster or get
            $tipster = null;
            $tipsterNickname = $tipsters[rand(0, count($tipsters) - 1)];

            $user = $this->em->getRepository('AppBundle:User')
                ->findOneByNickname($tipsterNickname);

            if ($user === null) {
                $user = new User();

                $encoder = $this->getContainer()->get('security.password_encoder');
                $password = $encoder->encodePassword($user, 'tamtam');

                $user->setEmail($tipsterNickname . '@prono-bet.com')
                    ->setNickname($tipsterNickname)
                    ->setPassword($password)
                    ->setRole('ROLE_TIPSTER');
                $this->em->persist($user);
                $this->em->flush($user);

                $tipster = new Tipster($user);
                $tipster->setFee(rand(20, 30))
                    ->setCommission(rand(10, 30))
                    ->setDescription("Alios autem dicere aiunt multo etiam inhumanius (quem locum breviter paulo ante perstrinxi) praesidii adiumentique causa, non benevolentiae neque caritatis, amicitias esse expetendas; itaque, ut quisque minimum firmitatis haberet minimumque virium, ita amicitias appetere maxime; ex eo fieri ut mulierculae magis amicitiarum praesidia quaerant quam viri et inopes quam opulenti et calamitosi quam ii qui putentur beati.")
                    ->setPicture($tipsterNickname . '_picture.jpg')
                    ->setCover($tipsterNickname . '_cover.jpg');
                $this->em->persist($tipster);
                $this->em->flush($tipster);

                $awsS3->putObject([
                    'Bucket' => $this->getContainer()->getParameter('aws_s3.bucket_medias'),
                    'Key' =>  $tipster->getPicture(),
                    'SourceFile' => $localTipsterPath . $tipster->getPicture(),
                    'ACL'          => 'public-read',
                ]);

                $awsS3->putObject([
                    'Bucket' => $this->getContainer()->getParameter('aws_s3.bucket_medias'),
                    'Key' =>  $tipster->getCover(),
                    'SourceFile' => $localTipsterPath . $tipster->getCover(),
                    'ACL'          => 'public-read',
                ]);
            } else {
                $tipster = $user->getTipster();
            }

            // Generate sport forecast
            $sportForecast = new SportForecast($tipster);
            $title = '';

            // Generate sport bet
            $nb = rand(0, 2);
            for ($y = 0; $y <= $nb; $y++) {

                $sportBet = new SportBet($sportForecast);

                $sportData = $sports[rand(0, count($sports) - 1)];

                $title = $sportData['titles'][rand(0, count($sportData['titles']) - 1)];

                // Generate sport or get
                $sport = $this->em->getRepository('AppBundle:Sport')
                    ->findOneByName($sportData['name']);
                if ($sport === null) {
                    $sport = new Sport();
                    $sport->setName($sportData['name'])
                        ->setVisible(true);
                    $this->em->persist($sport);
                    $this->em->flush($sport);
                }
                $sportBet->setSport($sport);

                // Generate championship or get
                $championshipName = $sportData['championships'][rand(0, count($sportData['championships']) - 1)];
                if ($championshipName !== '') {
                    $championship = null;
                    foreach ($sport->getChampionships() as $c) {
                        if ($championshipName === $c->getName()) {
                            $championship = $c;
                            break;
                        }
                    }

                    if ($championship === null) {
                        $championship = new Championship($sport);
                        $championship->setName($championshipName)
                            ->setVisible(true);
                        $this->em->persist($championship);
                        $this->em->flush($championship);
                        $sport->addChampionship($championship);
                    }
                    $sportBet->setChampionship($championship);
                }

                // Get winner, rating
                $rating = rand(110, 300) / 100;
                $sportBet->setWinner($sportData['winners'][rand(0, count($sportData['winners']) - 1)])
                    ->setPlayedAt(new \DateTime())
                    ->setConfidenceIndex(rand(0, 5))
                    ->setRating($rating);

                $sportForecast->addSportBet($sportBet);

            }

            // Generate ticket
            $ticket = $tickets[rand(0, count($tickets) - 1)];

            if ($ticket !== '') {

                $s3Tickets[] = $ticket;
            }


            $index = rand(0, (count($bookmakers) - 1));
            $sportForecast->setTitle($title)
                ->setTicket($ticket)
                ->setBookmaker($bookmakers[$index])
                ->setBetting(rand(10, 100))
                ->setIsVip(rand(0, 1));

            // Generate date
            $publishedAt = null;
            $playedAt = null;
            $isValidate = false;
            $status = rand(1, 4);
            switch ($status) {
                case 1:
                    // unpublished
                    $playedAt = new \DateTime();
                    break;
                case 2:
                    // in progress
                    $publishedAt = new \DateTime();
                    $int = new \DateInterval('P' . rand(1, 30) . 'DT' . rand(0,24) . 'H' . rand(0,60) . 'M' . rand(0,60) . 'S');
                    $publishedAt->add($int);
                    $playedAt = $publishedAt;
                    break;
                case 3:
                    // to validate
                    $publishedAt = new \DateTime();
                    $playedAt = new \DateTime();
                    $int = new \DateInterval('P' . rand(1, 30) . 'DT' . rand(0,24) . 'H' . rand(0,60) . 'M' . rand(0,60) . 'S');
                    $publishedAt->sub($int);
                    $int = new \DateInterval('PT' . rand(1, 23) . 'H');
                    $playedAt->sub($int);
                    break;
                default:
                    // history
                    $publishedAt = new \DateTime();
                    $playedAt = new \DateTime();
                    $int = new \DateInterval('P' . rand(1, 30) . 'DT' . rand(0,24) . 'H' . rand(0,60) . 'M' . rand(0,60) . 'S');
                    $publishedAt->sub($int);
                    $int = new \DateInterval('PT' . rand(1, 23) . 'H');
                    $playedAt->sub($int);
                    $isValidate = true;
            }

            $sportForecast->setPublishedAt($publishedAt)
                ->setIsValidate($isValidate);
            $this->em->persist($sportForecast);
            $this->em->flush($sportForecast);

            foreach ($sportForecast->getSportBets() as $sportBet) {
                $isWin = false;
                if ($isValidate === true) {
                    $isWin = (bool)rand(0, 1);
                }
                $sportBet->setPlayedAt($playedAt)
                    ->setIsWon($isWin);

                $this->em->persist($sportBet);
                $this->em->flush($sportBet);
            }

            $progress->advance();
        }

        $s3Tickets = array_unique($s3Tickets);
        foreach($s3Tickets as $s3Ticket) {
            if ($s3Ticket === '') {
                continue;
            }
            $awsS3->putObject([
                'Bucket' => $this->getContainer()->getParameter('aws_s3.bucket_medias'),
                'Key' =>  $s3Ticket,
                'SourceFile' => $localPath . $s3Ticket,
                'ACL'          => 'public-read',
            ]);
        }

        $progress->finish();
    }

    public function importCountry(OutputInterface $output)
    {
        $output->writeln(chr(10) . 'country import');

        $filePath = __DIR__ . '/../Resources/csv/sql-pays.csv';
        $promotes = [
            'FR' => 3,
            'BE' => 2,
            'CH' => 1
        ];

        if (($handle = fopen($filePath, "r")) !== false) {
            while (($data = fgetcsv($handle, 0, ",")) !== false) {

                $phoneNumberCode = $data[6];
                if ($data[6] === '') {
                    $phoneNumberCode = null;
                }

                $country = new Country();
                $country->setAlpha2($data[2])
                    ->setAlpha3($data[3])
                    ->setName($data[4])
                    ->setPhoneNumberCode($phoneNumberCode);

                $nationality = new Nationality();
                $nationality->setAlpha2($data[2])
                    ->setAlpha3($data[3])
                    ->setName($data[4]);

                foreach ($promotes as $key => $order) {
                    if ($data[2] === $key) {
                        $country->setSorting($order);
                        $nationality->setSorting($order);
                        break;
                    }
                }


                $this->em->persist($country);
                $this->em->persist($nationality);
                $this->em->flush();
            }
        }
    }

}
