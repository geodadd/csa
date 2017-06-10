<?php

declare(strict_types=1);

$stops = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7'];
$trips = ['T1', 'T2', 'T3', 'T4', 'T5'];

$connections = [
    // T1
    [
        'from' => 'S1',
        'to' => 'S2',
        'departure' => 0,
        'arrival' => 10,
        'trip' => 'T1',
        'change_time' => 4,
    ],
    [
        'from' => 'S2',
        'to' => 'S4',
        'departure' => 10,
        'arrival' => 20,
        'trip' => 'T1',
        'change_time' => 4,
    ],
    [
        'from' => 'S4',
        'to' => 'S5',
        'departure' => 20,
        'arrival' => 30,
        'trip' => 'T1',
        'change_time' => 4,
    ],
    // T2
    [
        'from' => 'S1',
        'to' => 'S2',
        'departure' => 10,
        'arrival' => 20,
        'trip' => 'T2',
        'change_time' => 4,
    ],
    [
        'from' => 'S2',
        'to' => 'S4',
        'departure' => 20,
        'arrival' => 30,
        'trip' => 'T2',
        'change_time' => 4,
    ],
    [
        'from' => 'S4',
        'to' => 'S5',
        'departure' => 30,
        'arrival' => 40,
        'trip' => 'T2',
        'change_time' => 4,
    ],
    [
        'from' => 'S5',
        'to' => 'S7',
        'departure' => 40,
        'arrival' => 50,
        'trip' => 'T2',
        'change_time' => 4,
    ],
    // T3
    [
        'from' => 'S4',
        'to' => 'S5',
        'departure' => 25,
        'arrival' => 35,
        'trip' => 'T3',
        'change_time' => 4,
    ],
    [
        'from' => 'S5',
        'to' => 'S6',
        'departure' => 35,
        'arrival' => 45,
        'trip' => 'T3',
        'change_time' => 4,
    ],
    // T4
    [
        'from' => 'S1',
        'to' => 'S2',
        'departure' => 30,
        'arrival' => 40,
        'trip' => 'T4',
        'change_time' => 4,
    ],
    [
        'from' => 'S2',
        'to' => 'S4',
        'departure' => 40,
        'arrival' => 50,
        'trip' => 'T4',
        'change_time' => 4,
    ],
    [
        'from' => 'S4',
        'to' => 'S5',
        'departure' => 50,
        'arrival' => 60,
        'trip' => 'T4',
        'change_time' => 4,
    ],
    [
        'from' => 'S5',
        'to' => 'S7',
        'departure' => 60,
        'arrival' => 70,
        'trip' => 'T4',
        'change_time' => 4,
    ],
    // T5
    [
        'from' => 'S6',
        'to' => 'S7',
        'departure' => 47,
        'arrival' => 67,
        'trip' => 'T5',
        'change_time' => 4,
    ],
];

function getConnectionId(int $cId, array $c): string
{
    return sprintf('%2d/%s@%2d->%s@%2d', $cId, $c['from'], $c['departure'], $c['to'], $c['arrival']);
}

function printTrip(string $tripId)
{
    global $connections;

    echo $tripId . ': ';

    $prevC = null;
    foreach ($connections as $cI => $c) {
        if ($c['trip'] != $tripId) continue;

        if ($prevC != null) {
            echo str_repeat(' ', $c['departure'] - $prevC['arrival']);
        } else {
            if ($c['departure'] != 0) echo str_repeat(' ', 17);
            echo str_repeat(' ', $c['departure']);
        }

        printf('%s %s ', getConnectionId($cI, $c), str_repeat('=', $c['arrival'] - $c['departure']));

        $prevC = $c;
    }

    echo PHP_EOL . PHP_EOL;
}

// print the network
uasort($connections, function ($c1, $c2) {
    return $c1['departure'] - $c2['departure'];
});

foreach ($trips as $t) {
    printTrip($t);
}

// --------------------------

// sort by departure desc
uasort($connections, function ($c1, $c2) {
    return $c2['departure'] - $c1['departure'];
});

// Initial profiles
$profiles = array_fill_keys($stops, [
    [
        'departure_start' => INF,
        'arrival_end' => INF,
        'enter_conn' => null,
        'exit_conn' => null,
    ],
]);
$tripsEA = array_fill_keys($trips, INF);
$tripsExitConn = array_fill_keys($trips, null);

// input
$from = 'S1';
$to = 'S7';
$departureTimestamp = -1;

printf("Depart from %s to %s at %d\n\n", $from, $to, $departureTimestamp);

// --------------------------

foreach ($connections as $cI => $c) {
    printf("---------------\n");
    printf("Inspecting C %s on %s\n", getConnectionId($cI, $c), $c['trip']);

    // I. --------------------------------------------------------
    // Find the minimum arrival time among of all values, that
    // this connection can introduce.

    $t = INF;

    // The options are:

    // 1. Continue on the vehicle from the trip, i.e. remain seated.
    $t = min($t, $tripsEA[$c['trip']]);

    /*debug*/ if ($t !== INF) {
        printf("Can remain seated on trip %s, t = %d\n", $c['trip'], $t);
    }

    // 2. Change the vehicle. Evaluating the profile of arrival stop.
    // todo: introduce change time
    /*debug*/ echo PHP_EOL . $c['to'] . ' profiles' . PHP_EOL . print_r($profiles[$c['to']], true);

    foreach ($profiles[$c['to']] as $pi => $pr) {
        if ($c['arrival'] <= $pr['departure_start']) { // todo: <=?
            $prevT = $t;
            $t = min($t, $pr['arrival_end']);

            /*debug*/ if ($t !== $prevT) {
                printf("Evaluated profile of %s. Got better option at profile [%d]. t = %d\n", $c['to'], $pi, $t);
            }

            break;
        }
    }

    // 3. Arrive at target stop.
    if ($c['to'] == $to) {
        $t = min($t, $c['arrival']);

        /*debug*/ if ($t !== INF) {
            printf("Arrived to destination %s. t = %d\n", $c['to'], $t);
        }
    }

    // II. -------------------------------------------------------
    // Update trip's arrival time. `t` now contains the earliest
    // arrival time over all journeys starting in c.

    if ($t < $tripsEA[$c['trip']]) {
        /*debug*/ printf("Update EAT of trip %s. t = %d\n", $c['trip'], $t);

        $tripsEA[$c['trip']] = $t;

        /*debug*/ printf("Set exit C of trip %s to %d\n", $c['trip'], $cI);
        $tripsExitConn[$c['trip']] = $cI; // todo: enter connection?
    }

    // III. ------------------------------------------------------
    // Update the profile of the current connection's departure stop.

    /*debug*/ echo PHP_EOL . $c['from'] . ' profiles' . PHP_EOL . print_r($profiles[$c['from']], true);

    if ($t < $profiles[$c['from']][0]['arrival_end']) {
        if ($c['departure'] == $profiles[$c['from']][0]['departure_start']) {
            /*debug*/ printf("Update arrival_end to %d for equal departure_start\n", $t);

//            $profiles[$c['from']][0] = [
//                'departure_start' => $c['departure'],
//                'arrival_end' => $t,
//                'enter_conn' => $cI,
//                'exit_conn' => $tripsExitConn[$c['trip']],
//            ];

            $profiles[$c['from']][0]['arrival_end'] = $t;
            $profiles[$c['from']][0]['exit_conn'] = $tripsExitConn[$c['trip']];
        } else {
            /*debug*/ printf("Prepend new profile with t = %d\n", $t);

            array_unshift(
                $profiles[$c['from']],
                [
                    'departure_start' => $c['departure'],
                    'arrival_end' => $t,
                    'enter_conn' => $cI,
                    'exit_conn' => $tripsExitConn[$c['trip']],
                ]
            );
        }

        /*debug*/ echo PHP_EOL . $c['from'] . ' profiles' . PHP_EOL . print_r($profiles[$c['from']], true);
    }

    printf("---------------\n");
    echo PHP_EOL . PHP_EOL;
}

// ------------------ Results -----------------------

// Do we have results for input?
// If yes, build route
echo 'Results for input: ' . (count($profiles[$from]) > 1 ? var_export(true, true) : var_export(false, true)) . PHP_EOL;

print_r($profiles[$from]);

$routes = [];
foreach ($profiles[$from] as $profileIndex => $profile) {
    if ($profile['arrival_end'] === INF) continue;

    $journey = [];
    $localProfile = $profile;
    while (true) {
        $enterConnection = $connections[$localProfile['enter_conn']];
        $exitConnection = $connections[$localProfile['exit_conn']];

        $journey[] = [
            'trip' => $enterConnection['trip'],
            'from' => $enterConnection['from'],
            'depart' => $localProfile['departure_start'],
            'to' => $exitConnection['to'],
            'arrive' => $exitConnection['arrival'],
            'profile' => $profileIndex,
        ];

        if ($to === $exitConnection['to']) {
            break;
        }

        $localProfile = $profiles[$exitConnection['to']][$profileIndex];
    }

    $routes[] = $journey;
}

foreach ($routes as $routeIndex => $journeys) {
    printf("Route #%d\n", $routeIndex);

    foreach ($journeys as $journey) {
        printf(
            "Trip %s: depart from %s at %d, arrive to %s at %d, profileIndex = %d\n",
            $journey['trip'],
            $journey['from'],
            $journey['depart'],
            $journey['to'],
            $journey['arrive'],
            $journey['profile']
        );
    }

    echo PHP_EOL;
}

echo PHP_EOL;

// other stops
echo 'Stops:' . PHP_EOL;
foreach ($profiles as $stop => $stopPrs) {
    foreach ($stopPrs as $profile) {
        if (INF === $profile['departure_start'])
            continue;

        printf("%s->%s: (%s, %s)\n", $stop, $to, $profile['departure_start'], $profile['arrival_end']);
    }
}

echo PHP_EOL;

echo 'Trips:' . PHP_EOL;
foreach ($tripsEA as $trip => $eat) {
    if (INF === $eat)
        continue;

    printf("%s: %s\n", $trip, $eat);
}

print_r($tripsExitConn);

echo PHP_EOL;
