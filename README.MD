# rundat's esmeralda-web API

This is **NOT** an official API, just few functions dealing with
[esmeralda-web](http://www.esmeralda-web.fr) in order to provide
an API for developpers.

[Esmeralda logo](http://www.esmeralda-web.fr/images/logo.jpg)

## What can it do?

Fetch data from [esmeralda-web](http://www.esmeralda-web.fr),
if needed, cache it, and filter it before returning it as JSON.

## Usage

### Get data

You need to set up a server, copying
[esmeralda-web-api.php](esmeralda-web-api.php) somewhere you can reach it.
You may want to edit `$DATADIR` variable which define where to store
data fetched, and which is by default the same directory as
[esmeralda-web-api.php](esmeralda-web-api.php).

Once you have the address of a suitable server,
send a GET request to it with the following optional parameters:

- `d`: the date, formated as `Y-m-d`. Default is current time.
- `s`: comma-separated stations list. You may use PCRE regexp instead
  of real station names. Default selects all.
- `p`: comma-separated pollutants list. Default selects all.

Example:

    [...]?d=2014-10-01&s=idf3.Paris_18,idf3.Paris_12&p=PM10,NO2

### Use data

Returned data are JSON, with the following format:

    {
      "station_zone.station_name" :
      {
        "POLLUTANT" : [day-1, day, day+1, day+2],
        ... other available pollutants ...
      },
      ... other stations ...
    }

So, it returns an object whose each member key is a station
(prefixed by its region) with another object associating
available pollutant name and concentrations as value.

## Good to know

Data fetched are open data, for everyone, with the only requirement that
one using them will have to display esmeralda logo and web site address
in any publication.

These are raw data, which would need intrepretation by an expert.
