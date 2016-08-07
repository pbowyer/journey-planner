<?php
/**
 * Created by PhpStorm.
 * User: linus
 * Date: 07/08/16
 * Time: 20:10
 */

namespace JourneyPlanner\App\Console\Command;

use KMeans\Space;
use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AssignStationClusters extends ConsoleCommand {
    const NAME = 'assign-clusters';
    const DESCRIPTION = 'Calculate and assign the cluster for each station';

    /**
     * @var PDO
     */
    private $db;

    /**
     * @param PDO $db
     */
    public function __construct(PDO $db) {
        parent::__construct();
        $this->db = $db;
    }

    /**
     * Set up arguments
     */
    protected function configure() {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $stmt = $this->db->query("SELECT stop_lat, stop_lon FROM stops WHERE stop_code != ''");
        $space = new Space(2);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $space->addPoint([$row['stop_lat']*10000, $row['stop_lon'] * 10000]);
        }

        $clusters = $space->solve(40, Space::SEED_DASV);
        $stmt = $this->db->prepare("UPDATE stops SET zone_id = :cluster WHERE stop_lat = :lat AND stop_lon = :lon");
        foreach ($clusters as $i => $cluster) {
            foreach ($cluster as $station) {
                list($x, $y) = $station->getCoordinates();

                $stmt->execute(['cluster' => $i, 'lat' => $x / 10000, 'lon' => $y / 10000]);
            }
        }

        return 0;
    }
}