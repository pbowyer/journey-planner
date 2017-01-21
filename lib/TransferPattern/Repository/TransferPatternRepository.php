<?php

namespace JourneyPlanner\Lib\TransferPattern\Repository;
use DateTime;
use JourneyPlanner\Lib\Journey\Repository\TimetableLegRepository;
use JourneyPlanner\Lib\Cache\Cache;
use JourneyPlanner\Lib\TransferPattern\PatternSegment;
use JourneyPlanner\Lib\TransferPattern\TransferPattern;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternRepository {

    const NUM_PATTERNS = 10;

    private $db;
    private $cache;
    private $timetableLegRepository;

    /**
     * @param PDO $db
     * @param \JourneyPlanner\Lib\Cache\Cache $cache
     * @param TimetableLegRepository $timetableLegRepository
     */
    public function __construct(PDO $db, Cache $cache, TimetableLegRepository $timetableLegRepository) {
        $this->db = $db;
        $this->cache = $cache;
        $this->timetableLegRepository = $timetableLegRepository;
    }

    /**
     * Lookup the transfer patterns and schedule separately in order to use
     * the cache.
     *
     * @param $origin
     * @param $destination
     * @param $dateTime
     * @return TransferPattern[]
     */
    public function getTransferPatterns(string $origin, string $destination, DateTime $dateTime): array {
        $results = [];

        foreach ($this->getTransferPatternsFromDB($origin . $destination) as $transferPattern) {
            $segments = $this->getTransferPatternSegments($transferPattern, $dateTime);

            if (count($segments) > 0) {
                $results[] = new TransferPattern($segments);
            }
        }
        return $results;
    }

    /**
     * @param string $transferPattern
     * @param DateTime $dateTime
     * @return PatternSegment[]
     */
    private function getTransferPatternSegments(string $transferPattern, DateTime $dateTime): array  {
        $pattern = str_split($transferPattern, 3);
        $legLength = count($pattern);
        $patternLegs = [];

        for ($i = 0; $i < $legLength; $i += 2) {
            $legs = $this->timetableLegRepository->getTimetableLegs($pattern[$i], $pattern[$i + 1], $dateTime);

            if (count($legs) > 0) {
                $patternLegs[] = new PatternSegment($legs);
            }
            else {
                return []; // if any leg is missing services the whole pattern breaks down
            }
        }

        return $patternLegs;
    }

    /**
     * @param $journey
     * @return array
     */
    private function getTransferPatternsFromDB(string $journey): array {
        return $this->db->query("
          SELECT pattern FROM transfer_patterns WHERE journey = '{$journey}'ORDER BY LENGTH(pattern) LIMIT ".self::NUM_PATTERNS
        )->fetchAll(PDO::FETCH_COLUMN);
    }
}