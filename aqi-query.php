<?php

require 'config.php' ;

$AQI = unserialize (file_get_contents ($AQI_DATA_FILE));

function getConc ($station, $pollutant)
{
    global $AQI ;
    return $AQI [$station] [$pollutant] ;
}

function getBatchConc ($stations, $pollutants)
{
    global $AQI ;
    $data = [];
    foreach ($stations as $station)
    {
        foreach ($pollutants as $pollutant)
        {
            $conc = getConc ($station, $pollutant) ;
            if ($conc != NULL)
            {
                $data [$station] [$pollutant] = $conc ;
            }
        }
    }
    return $data;
}

function main ()
{
    $stations = explode(',', $_GET ['s']) ;
    $pollutants = explode(',', $_GET ['p']) ;
    $data = getBatchConc ($stations, $pollutants) ;
    $json = json_encode ($data) ;
    if ($json != NULL)
    {
        echo $json ;
    }
}

main ();

?>
