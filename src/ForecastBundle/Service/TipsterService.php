<?php


namespace ForecastBundle\Service;


use AppBundle\Entity\Sport;
use AppBundle\Entity\Tipster;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

class TipsterService
{
    private $em;
    private $ra;
    private $tipster;

    public function __construct(EntityManager $em, $ra, Tipster $tipster)
    {
        $this->em = $em;
        $this->ra = $ra;
        $this->tipster = $tipster;
    }

    /**
     * @return array
     */
    public function getStatsLast30Days()
    {
        $tipsterStats = $this->ra->getItem('tipster.stats' . $this->tipster->getId());
        if ($tipsterStats->isHit()) {
            return $tipsterStats->get();
        }


        $stats = $this->calculateStats();

        $tipsterStats->set($stats);
        $this->ra->save($tipsterStats);

        return $stats;
    }

    /**
     * @param null $startDate
     * @param null $endDate
     * @return array
     */
    public function calculateStats($startDate = null, $endDate = null)
    {
        // calculate dates
        if ($startDate === null && $endDate === null) {
            $startDate = new \DateTime();
            $endDate = new \DateTime();
            $dt = new \DateInterval('P30D');
            $startDate->sub($dt);
        }

        // retreive all sports forecasts
        $sportForecasts = $this->em->getRepository('AppBundle:SportForecast')
            ->getSportForecastsHistoryByTipster(0, 0, '', '', $this->tipster, $startDate, $endDate, 'asc');

        $sportForecastStats = $this->calculateSportForecastStats($sportForecasts);
        $sportStats = $this->calculateSportStats($sportForecasts);
        $sportDataSet = $this->generateSportDataSet($sportStats);

        // get last 5 sport forecasts
        $lastFive = [];
        $i = 5;
        foreach ($sportForecasts as $sportForecast) {
            $lastFive[] = $sportForecast;
            $i--;

            if ($i === 0) {
                break;
            }
        }

        return [
            'sportForecasts' => $lastFive,
            'sportForecastStats' => $sportForecastStats,
            'sportStats' => $sportStats,
            'sportDataSet' => $sportDataSet
        ];
    }

    /**
     * @param $sportStats
     * @return array
     */
    private function generateSportDataSet($sportStats)
    {
        $sportDataSet = [
            'all' => [
                'labels' => [],
                'data' => []
            ],
            'vip' => [
                'labels' => [],
                'data' => []
            ],
            'free' => [
                'labels' => [],
                'data' => []
            ]
        ];

        foreach ($sportStats as $type => $sports) {
            foreach ($sports as $sport => $stats) {
                $sportDataSet[$type]['labels'][] = $sport;
                $sportDataSet[$type]['data'][] = $stats['winrate'];
            }
        }

        return $sportDataSet;
    }

    /**
     * @param $sportForecasts
     * @return array
     */
    private function calculateSportStats($sportForecasts)
    {
        $sportStats = ['all' => [], 'vip' => [], 'free' => []];
        foreach ($sportForecasts as $sportForecast) {
            // skip cancelled sports forecasts for statistics
            if ($sportForecast->isCancelled() === true) {
                continue;
            }

            // all counter
            foreach ($sportForecast->getSportBets() as $sportBet) {
                if ($sportBet->getCancelled() === false) {
                    $sport = $sportBet->getSport()->getName();

                    if (!isset($sportStats['all'][$sport])) {
                        $sportStats['all'][$sport] = [
                            'played' => 1,
                            'won' => (int) $sportBet->getIsWon()
                        ];
                    } else {
                        $sportStats['all'][$sport]['played']++;
                        if ($sportBet->getIsWon()) {
                            $sportStats['all'][$sport]['won']++;
                        }
                    }
                }
            }

            $type = 'free';
            if ($sportForecast->getIsVip()) {
                $type = 'vip';
            }

            foreach ($sportForecast->getSportBets() as $sportBet) {
                if ($sportBet->getCancelled() === false) {
                    $sport = $sportBet->getSport()->getName();

                    if (!isset($sportStats[$type][$sport])) {
                        $sportStats[$type][$sport] = [
                            'played' => 1,
                            'won' => (int)$sportBet->getIsWon()
                        ];
                    } else {
                        $sportStats[$type][$sport]['played']++;
                        if ($sportBet->getIsWon()) {
                            $sportStats[$type][$sport]['won']++;
                        }
                    }
                }
            }
        }

        // calcul win rate
        foreach ($sportStats as $type => $sports) {
            foreach ($sports as $sport => $stats) {
                $sportStats[$type][$sport]['winrate'] = 0;
                if ($stats['played'] > 0) {
                    $sportStats[$type][$sport]['winrate'] = round($stats['won'] * 100 / $stats['played'], 2);
                }
            }
        }

        return $sportStats;
    }

    /**
     * @param $sportForecasts
     * @return array
     */
    private function calculateSportForecastStats($sportForecasts)
    {
        $sportForecastStats = [
            'all' => [
                'played' => 0,
                'won' => 0,
                'bettings' => 0,
                'winnings' => 0,
                'winrate' => 0,
                'roi' => 0,
                'bankroll' => [
                    'labels' => [],
                    'data' => []
                ]
            ],
            'free' => [
                'played' => 0,
                'won' => 0,
                'bettings' => 0,
                'winnings' => 0,
                'winrate' => 0,
                'roi' => 0,
                'bankroll' => [
                    'labels' => [],
                    'data' => []
                ]
            ],
            'vip' => [
                'played' => 0,
                'won' => 0,
                'bettings' => 0,
                'winnings' => 0,
                'winrate' => 0,
                'roi' => 0,
                'bankroll' => [
                    'labels' => [],
                    'data' => []
                ]
            ],
        ];

        $bankrollAll = 0;
        $tBankrollAll = [];
        $bankrollVip = 0;
        $tBankrollVip = [];
        $bankrollFree = 0;
        $tBankrollFree = [];

        foreach ($sportForecasts as $sportForecast) {
            // skip cancelled sports forecasts for statistics
            if ($sportForecast->isCancelled() === true) {
                continue;
            }

            // all counters
            $sportForecastStats['all']['played']++;
            $sportForecastStats['all']['bettings'] += $sportForecast->getBetting();
            $bankrollAll += - $sportForecast->getBetting();
            if ($sportForecast->isWon()) {
                $sportForecastStats['all']['won']++;
                $sportForecastStats['all']['winnings'] = $sportForecast->getWinning();
                $bankrollAll += $sportForecast->getWinning();
            }
            $tBankrollAll[$sportForecast->getPublishedAt()->format('d/m/Y')] = $bankrollAll;

            // vip counters
            if ($sportForecast->getIsVip()) {
                $sportForecastStats['vip']['played']++;
                $sportForecastStats['vip']['bettings'] += $sportForecast->getBetting();
                $bankrollVip += - $sportForecast->getBetting();
                if ($sportForecast->isWon()) {
                    $sportForecastStats['vip']['won']++;
                    $sportForecastStats['vip']['winnings'] += $sportForecast->getWinning();
                    $bankrollVip += $sportForecast->getWinning();
                }
                $tBankrollVip[$sportForecast->getPublishedAt()->format('d/m/Y')] = $bankrollVip;

            } else {
                // free counters
                $sportForecastStats['free']['played']++;
                $sportForecastStats['free']['bettings'] += $sportForecast->getBetting();
                $bankrollFree += - $sportForecast->getBetting();
                if ($sportForecast->isWon()) {
                    $sportForecastStats['free']['won']++;
                    $sportForecastStats['free']['winnings'] += $sportForecast->getWinning();
                    $bankrollFree += $sportForecast->getWinning();
                }
                $tBankrollFree[$sportForecast->getPublishedAt()->format('d/m/Y')] = $bankrollFree;
            }

        }

        // all winrate and roi
        if ($sportForecastStats['all']['played'] > 0) {
            $sportForecastStats['all']['winrate'] = round($sportForecastStats['all']['won'] * 100 / $sportForecastStats['all']['played'], 2);
            $sportForecastStats['all']['roi'] = round($sportForecastStats['all']['winnings'] / $sportForecastStats['all']['bettings'], 2);
        }

        // vip winrate and roi
        if ($sportForecastStats['vip']['played'] > 0) {
            $sportForecastStats['vip']['winrate'] = round($sportForecastStats['vip']['won'] * 100 / $sportForecastStats['vip']['played'], 2);
            $sportForecastStats['vip']['roi'] = round($sportForecastStats['vip']['winnings'] / $sportForecastStats['vip']['bettings'], 2);
        }

        // free winrate and roi
        if ($sportForecastStats['free']['played'] > 0) {
            $sportForecastStats['free']['winrate'] = round($sportForecastStats['free']['won'] * 100 / $sportForecastStats['free']['played'], 2);
            $sportForecastStats['free']['roi'] = round($sportForecastStats['free']['winnings'] / $sportForecastStats['free']['bettings'], 2);
        }

        // all bankroll dataset
        $sportForecastStats['all']['bankroll']['labels'] = array_keys($tBankrollAll);
        $sportForecastStats['all']['bankroll']['data'] = array_values($tBankrollAll);

        // vip bankroll dataset
        $sportForecastStats['vip']['bankroll']['labels'] = array_keys($tBankrollVip);
        $sportForecastStats['vip']['bankroll']['data'] = array_values($tBankrollVip);

        // free bankroll dataset
        $sportForecastStats['free']['bankroll']['labels'] = array_keys($tBankrollFree);
        $sportForecastStats['free']['bankroll']['data'] = array_values($tBankrollFree);


        return $sportForecastStats;
    }
}


