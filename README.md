Journey Planner[![Build Status](https://travis-ci.org/linusnorton/journey-planner.svg?branch=master)](https://travis-ci.org/linusnorton/journey-planner)
===============

A simple journey planner that uses a combination of the Connection Scan Algorithm and Transfer Patterns to quickly (~50ms) return a full days schedule of journeys. It currently uses UK Rail data but could include any GTFS-ish dataset. 

The journey planner is available to use through the CLI, which only returns a single result or an API that returns many results. You can see it in action and understand the API call by viewing [traintickets.to](http://traintickets.to).

There is a fairly basic journey filter applied to remove some of the noise. A journey is removed if it departs at the same time as another but arrives later, or it arrives at the same time and departs earlier. In the case of a tie the journey with the least number of changes is used.

## Environment 

This project depends on PHP and MySQL or MariaDB. The following environment variables should be exported:

```
export DATABASE_USERNAME={{ database_username }}
export DATABASE_PASSWORD={{ database_password }}
export DATABASE_HOSTNAME={{ database_hostname }}
```

Instead of this you may use the [traintickets.to development environment](https://github.com/linusnorton/traintickets.to).

## Set up

The journey planner depends on a GTFS data set and some pre-processed transfer patterns. Calculating the transfer patterns can take between 2-8 hours depending on the number of cores available.

```
./bin/import-data
./bin/find-transfer-patterns
```

## CLI

```
./bin/run TON CHX 2016-07-26T09:00
```

The date is optional.

## Web interface

You will need to set up a virtual host for the API. If you are using the [traintickets.to development environment](https://github.com/linusnorton/traintickets.to) this is typically `http://api-ttt.local/`.

```
curl "ttt.local/api/journey-plan?origin=PDW&destination=MAR&date=2016-07-25T09:00"
```

## PHP interface
```
$loader = $app['loader.database'];
$targetTime = strtotime("2016-07-25T09:00");

$origins = $loader->getRelevantStations("PDW");
$destinations = $loader->getRelevantStations("CST");
$planner = new MultiSchedulePlanner($loader, [new SlowJourneyFilter()]);
$journeys = $planner->getJourneys($origins, $destinations, $targetTime);

print_r($journeys);
```

## Tests

```
./vendor/bin/phpunit
```

## Notes

Services that start after midnight are not currently considered.

London Underground is a bit of a blackhole and is covered by "transfers" as opposed to individual tube trips.

The connection scan algorithm tends is geared towards speed and tends to chuck people of trains as soon as possible in order to get them to their destination. A more sensible approach would be to use the largest available major station.

## Contributing

Issues and PRs are very welcome. Alternate journey planning algorithms to calculate transfer patterns are welcome. 

The project is written in PHP 7.0 but relies on MariaDB/MySQL to do a lot of the heavy lifting.

## License

This software is licensed under [GNU GPLv3](https://www.gnu.org/licenses/gpl-3.0.en.html).

Copyright 2016 Linus Norton.