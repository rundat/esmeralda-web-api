<?php

function localDataURL ($year, $mon, $day)
{
    return sprintf ('data/%d%02d%02d.dat', $year, $mon, $day) ;
}

/* Generate URL to fetch according to date, domain and pollutant. */
function dataURL ($year, $mon, $day, $dom, $pol)
{
    return sprintf ('http://www.esmeralda-web.fr/esmeralda/FIGS/'
                   .'%d/%02d/%d%02d%02d/GN15/conc_peak.%s.%s.txt',
                    $year, $mon, $year, $mon, $day, $dom, $pol) ;
}

/* Get content return by url using curl, and retur it as string. */
function fetchURL ($url)
{
    $ch = curl_init ($url) ;
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
    $res = curl_exec ($ch) ;
    curl_close ($ch) ;
    return $res ;
}

/* Get raw data from esmeralda-web for a day / domain / pollutant.
 * Parse the result and extract values. */
function fetchSingleData ($year, $mon, $day, $dom, $pol)
{
    $raw = fetchURL (dataURL ($year, $mon, $day, $dom, $pol)) ;

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
    $pollutants = [ 'PM10', 'PM25', 'NO2', 'O3' ] ;
    $domains = [ 'bour', 'bret', 'chp3', 'idf3',
                 'lgr3', 'nmd', 'npc', 'pcd' ] ;

    $data = [] ;

    foreach ($domains as $dom)
    {
        foreach ($pollutants as $pol)
        {
            foreach (fetchSingleData ($year, $mon, $day, $dom, $pol)
                as $station => $conc)
            {
                $data [$dom.'.'.$station] [$pol] = $conc ;
            }
        }
    }

    return $data ;
}

/* Fetch data and save them on the server. */
function saveData ($year, $mon, $day)
{
    $data = fetchData ($year, $mon, $day) ;
    $filename = localDataURL ($year, $mon, $day) ;
    return file_put_contents ($filename, serialize ($data)) ;
}

function getData ($year, $mon, $day, $stations, $pollutants)
{
    $filename = localDataURL ($year, $mon, $day) ;
    if (!file_exists ($filename)
        && !saveData ($year, $mon, $day))
    {
        return NULL ;
    }
    $db = unserialize (file_get_contents ($filename));

    foreach ($stations as $station)
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
    $date = date_parse_from_format ("Y-m-d", $_GET ['d']) ;
    $stations = explode(',', $_GET ['s']) ;
    $pollutants = explode(',', $_GET ['p']) ;
    $data = getData ($date['year'], $date['month'], $date['day'],
                     $stations, $pollutants) ;
    $json = json_encode ($data) ;
    if ($json != NULL)
    {
        echo $json ;
    }
}

main ();

?>
