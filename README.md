Journey Planner [![Build Status](https://travis-ci.org/open-track/journey-planner.svg?branch=master)](https://travis-ci.org/open-track/journey-planner)
===============

A simple journey planner that uses Transfer Patterns to quickly (~50ms) return a full days schedule of journeys. It currently uses UK Rail data but could include any GTFS-ish dataset. 

The journey planner is available to use through the CLI or an API. You can see it in action and understand the API call by viewing [traintickets.to](http://traintickets.to).

There is a fairly basic journey filter applied to remove some of the noise. A journey is removed if it departs at the same time as another but arrives later, or it arrives at the same time and departs earlier. In the case of a tie the journey with the least number of changes is used.

## Environment 

This project depends on PHP and MySQL or MariaDB. The following environment variables should be exported:

```
export DATABASE_USERNAME={{ database_username }}
export DATABASE_PASSWORD={{ database_password }}
export DATABASE_HOSTNAME={{ database_hostname }}
```

Instead of this you may use the [traintickets.to development environment](https://github.com/open-track/ansible).

## Set up

If you are using the [traintickets.to development environment](https://github.com/open-track/ansible) you do not need to import anything and the API is available at `http://api-ttt.local/`.

If you want to run it as a stand-alone journey planner you can run the import script below and then use the [transfer pattern generator](https://www.github.com/open-track/transfer-pattern-generator-scala) tool to create the transfer patterns. The transfer patterns take a while to create (30min~ on an 8 core machine) so you can download a dump of them from `https://s3-eu-west-1.amazonaws.com/traintickets.to/database/patterns.sql.gz`

```
composer install
npm install
./bin/import-data
```

## CLI

```
./bin/run TON CHX 2016-07-26T09:00
```

The date is optional.

## Web interface

You will need to set up a virtual host for the API. If you are using the [traintickets.to development environment](https://github.com/open-track/ansible) this is typically `http://api-ttt.local/`.

```
curl "ttt.local/api/journey-plan?origin=PDW&destination=MAR&date=2016-07-25T09:00"
```

## PHP interface
```

$origin = "PDW";
$destination = "CST";
$targetTime = new DateTime("2016-07-25T09:00 UTC");

$planner = $app["planner.group_station"];
$journeys = $planner->getJourneys($origins, $destinations, $targetTime);

print_r($journeys);
```

## Tests

```
./vendor/bin/phpunit
```

## Contributing

Issues and PRs are very welcome. Alternate journey planning algorithms to calculate transfer patterns are welcome. 

The project is written in PHP 7.0 but relies on MariaDB/MySQL to do a lot of the heavy lifting.

## License

This software is licensed under [GNU GPLv3](https://www.gnu.org/licenses/gpl-3.0.en.html).

Copyright 2016 Linus Norton.
