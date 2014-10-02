<?php

$POLLUTANTS = [ 'PM10', 'PM25', 'NO2', 'O3' ] ;
$DOMAINS    = [ 'bour', 'bret', 'chp3', 'idf3',
                'lgr3', 'nmd', 'npc', 'pcd' ] ;
$DATADIR     = '.' ;

function localDataURL ($year, $mon, $day)
{
    global $DATADIR ;
    return $DATADIR . sprintf ('/%d%02d%02d.dat', $year, $mon, $day) ;
}

/* Generate URL to fetch according to date, domain and pollutant. */
function remoteDataURL ($year, $mon, $day, $dom, $pol)
{
    return sprintf ('http://www.esmeralda-web.fr/esmeralda/FIGS/'
                   .'%d/%02d/%d%02d%02d/GN15/conc_peak.%s.%s.txt',
                    $year, $mon, $year, $mon, $day, $dom, $pol) ;
}

/* Get content return by url using curl, and retur it as string. */
function cURL ($url)
{
    $ch = curl_init ($url) ;
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
    $res = curl_exec ($ch) ;
    curl_close ($ch) ;
    return $res ;
}

/* Get raw data from esmeralda-web for a day / domain / pollutant.
 * Parse the result and extract values. */
function fetchSheet ($year, $mon, $day, $dom, $pol)
{
    $raw = cURL (remoteDataURL ($year, $mon, $day, $dom, $pol)) ;

    foreach (explode ("\n", $raw) as $line)
    {
        preg_match ('/\s*([\w\d_]+)'
                   .'\s+(\d+)\.\s+\d+'
                   .'\s+(\d+)\.\s+\d+'
                   .'\s+(\d+)\.\s+\d+'
                   .'\s+(\d+)\.\s+\d+'
                   .'\s*/',
                    $line, $matches) ;
        if (count ($matches) == 6)
        {
            $data [$matches[1]] = array_slice ($matches, 2) ;
        }
    }

    return $data ;
}

/* Fetch data for all pollutants and all stations according a given day. */
function fetchData ($year, $mon, $day)
{
    global $DOMAINS ;
    global $POLLUTANTS ;

    $data = [] ;

    foreach ($DOMAINS as $dom)
    {
        foreach ($POLLUTANTS as $pol)
        {
            foreach (fetchSheet ($year, $mon, $day, $dom, $pol)
                as $station => $conc)
            {
                $data [$dom.'.'.$station] [$pol] = $conc ;
            }
        }
    }

    return $data ;
}

/* Fetch data and save them on the server. */
function importData ($year, $mon, $day)
{
    $data = fetchData ($year, $mon, $day) ;
    $filename = localDataURL ($year, $mon, $day) ;
    return file_put_contents ($filename, serialize ($data)) ;
}

function loadData ($year, $mon, $day, $stations, $pollutants)
{
    $filename = localDataURL ($year, $mon, $day) ;
    if (!file_exists ($filename) && !importData ($year, $mon, $day))
    {
        return NULL ;
    }
    $db = unserialize (file_get_contents ($filename));

    $selected_stations = [] ;

    /* Station may be a station name or a regexp. */
    foreach ($stations as $station)
    {
        if (!empty ($db [$station]))
        {
            $selected_stations [] = $station ;
        }
        else
        {
            foreach ($db as $db_station => $_)
            {
                if (preg_match ($station, $db_station) === 1)
                {
                    $selected_stations [] = $db_station ;
                }
            }
        }
    }

    foreach ($selected_stations as $station)
    {
        foreach ($pollutants as $pollutant)
        {
            if (!empty ($db [$station] [$pollutant]))
            {
                $data [$station] [$pollutant] = $db [$station] [$pollutant] ;
            }
        }
    }

    return $data;
}

function main ()
{
    global $POLLUTANTS ;

    $stations = (isset ($_GET ['s'])) ?
                (explode (',', $_GET ['s'])) :
                ['/.*/'];

    $pollutants = (isset ($_GET ['p']) ?
                   (explode (',', $_GET ['p'])) :
                   $POLLUTANTS) ;

    $date = date_parse_from_format ('Y-m-d', (isset ($_GET ['d']) ?
                                              $_GET ['d'] :
                                              (date ('Y-m-d')))) ;

    $data = loadData ($date['year'], $date['month'], $date['day'],
                      $stations, $pollutants) ;

    $json = json_encode ($data) ;
    if ($json != NULL)
    {
        echo $json ;
    }
}

main ();

?>
