<?php

    // usage: TERM$ php -f kmzCompressor.php

    // needs to be something like: 
    // /home/webhosts/{domain}/htdocs/sie-application/xml/foo/
    $workingDirectory = "~/DirInQuestion/";
	$start = microtime(1);

    // required name for kmz's to work
    $kmlName = 'doc.kml'; 
    $count = 0;

    if (is_dir($workingDirectory)) 
    {
        $directoryIterator = New RecursiveDirectoryIterator($workingDirectory);
        $iteratorIterator  = New RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST); 
        // could use CHILD_FIRST if you so wish, but this works fine.

        foreach ($iteratorIterator AS $file) 
        {
            // weed out base directory crap that we don't really need
            if (is_file($file)) 
            {
                // hush var otf info, its loud =/
                // we need a way to create vars otf, without complaint =/ another proj maybe?
                $output = ''; 

                // current working assets
                $workingDirName = preg_replace('~'.strrchr($file, '/').'~', '/', $file);
                $workingZipName = $workingDirName.array_shift(explode('.', substr(strrchr($file, '/'), 1))) . '.kmz';

                // because foo
                echo "Working on: " . str_replace($workingDirectory, './', $file) ."\n";

                // temp name to push
                $outputName = tempnam($workingDirName, $kmlName);

                    // open handles, create temps
                    $inputHandle  = fopen($file, 'rb'); 
                    $outputHandle = fopen($outputName, 'w+');

                    // tried a funky if (($inputHandle  = fopen($file, 'rb') && $outputHandle = fopen($outputName, 'w+')) === true) // do a barrel roll
                    // because we can set inside switches, and it just looks cooler, =/ hahah
                    // but for anyone ELSES sanity... ;)

                    // write to out; fyi cline means "a fluid layer with a property that varies", no relation to the below though....
                    while ( $cline = fgets($inputHandle) ) fwrite($outputHandle, preg_replace('!\s+!',' ',$cline));
                    // ^ needs more refine, leaves double whitespacing =P

                    // reset pointer or be borked
                    rewind($outputHandle);

                // close handles
                fclose($inputHandle); // why no fclose($inputHandle, $outputHandle); ??
                fclose($outputHandle);

                // house cleaning (see: Oikology)
                rename($outputName, $kmlName);

                // have to do this, no ziparchive and gzip uses wrong algorithm =(
                // which is a pita because its the only one that can vary compression
                // algorithms....
                shell_exec("zip -9mjr $workingZipName $kmlName");
                #chmod($workingZipName, 0755); // if needed.... prob not though.

                    // stupid facts math
                    if ($bytes < 1024)           $oSize =  $bytes .' B';
                    elseif ($bytes < 1048576)    $oSize =  round($bytes / 1024, 2) .' KB';
                    elseif ($bytes < 1073741824) $oSize =  round($bytes / 1048576, 2) . ' MB';

                    $bytes = filesize($workingZipName);
                    if ($bytes < 1024)           $cSize =  $bytes .' B';
                    elseif ($bytes < 1048576)    $cSize =  round($bytes / 1024, 2) .' KB';
                    elseif ($bytes < 1073741824) $cSize =  round($bytes / 1048576, 2) . ' MB';

                    $dPerc = floor((filesize($file)-filesize($workingZipName))/filesize($file)*100);

                // because foo
                echo "Created: ".str_replace($workingDirectory, './', $workingZipName)."\n";
                echo "Origional Size:  {$oSize}\n";
                echo "Compressed Size: {$cSize}\n";
                echo "File shrank: {$dPerc}%\n\n"; // averages 98% overall, win!
                $count++;

            }
        }

    } else { die("$workingDirectory is not an operable directory."); }

    echo "Completed $count files in ".($start-microtime(1))." seconds";

