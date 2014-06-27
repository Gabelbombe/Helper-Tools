<?php

error_reporting(-1);
ini_set('display_errors', 1);

$localitiesMap      =
$blackList          =
$stringList         = [];


foreach (explode("\n", file_get_contents('data/ZipCodeAndTaxLocalities.txt')) AS $data)

    if ($map = explode("\t", $data))
    {
        if (in_array($map[0], $localitiesMap))

            Throw New \LogicException("Duplicate data {$map[0]} cannot exist.");

        $localitiesMap[$map[0]] = $map[1];
    }


foreach (explode("\n", file_get_contents('data/ZipCodePlus4.txt')) AS $data)

    /**
     * fake an array with:
     * [
     *    explode("\t", $data)
     * ]
     */

    foreach ([explode("\t", $data)] AS $arr)

        if (isset($localitiesMap[$arr[0]]))
        {
            $arr[]  = $localitiesMap[$arr[0]];

            unset($arr[3], $arr[4]);

            $stringList[] = implode("\t", $arr);
        } else {
            $blackList[] = $arr;
        }


if (! empty($blackList)) echo "Locality(s) not implemented: " . print_r($blackList, 1);

    file_put_contents('data/flmt_locality_by_zip.txt', implode("\n", $stringList), LOCK_EX);
