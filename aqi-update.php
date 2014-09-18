<?php

require 'config.php' ;

function dataURL ($year, $mon, $day, $dom, $pol)
{
    return sprintf ('http://www.esmeralda-web.fr/esmeralda/FIGS/'
                   .'%d/%02d/%d%02d%02d/GN15/conc_peak.%s.%s.txt',
                    $year, $mon, $year, $mon, $day, $dom, $pol) ;
}

function getData ($year, $mon, $day, $dom, $pol)
{
    $ch = curl_init (dataURL ($year, $mon, $day, $dom, $pol)) ;
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
    $res = curl_exec ($ch) ;
    curl_close ($ch) ;

    foreach (explode ('\n', $res) as $line)
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

function getAllData ($year, $mon, $day)
{
    $pollutants = [ 'PM10', 'PM25', 'NO2', '03' ] ;
    $domains = [ 'bour', 'bret', 'chp3', 'idf3',
                 'lgr3', 'nmd', 'npc', 'pcd' ] ;

    $data = [] ;

    foreach ($domains as $dom)
    {
        foreach ($pollutants as $pol)
        {
            foreach (getData ($year, $mon, $day, $dom, $pol)
                as $station => $conc)
            {
                $data [$dom.'.'.$station] [$pol] = $conc ;
            }
        }
    }

    return $data ;
}

function main ()
{
    global $AQI_DATA_FILE ;

    $date = getDate () ;
    $data = getAllData ($date['year'], $date['mon'], $date['mday']) ;
    if (file_put_contents ($AQI_DATA_FILE, serialize ($data)))
    {
        var_dump ($data);
    }
}

main () ;

?>
