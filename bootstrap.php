<?php

    $cdir = FALSE;

    if (FALSE === strpos($_GET['x'], 'images-'))
    {
        $cdir = substr($_GET['x'],0,strpos(substr($_GET['x'],0), "/"));
        $page = str_replace($cdir,'',$_GET['x']);
        $url = "http://{$cdir}.webarch-rdc-a-dev.erado.com/{$page}";
    }

    else
    {
        $page = str_replace('images-','',$_GET['x']);
        $url = preg_replace('~(?<!\:)\/\/~', '/', "http://webarch-rdc-a-dev.erado.com/{$page}");
    }


    $referer = 'https://manage-801-dev.erado.com';
    $headers = array();


    $ch = curl_init($url);
    curl_setopt_array($ch, array (
        CURLOPT_HEADER          => 1,
        CURLOPT_RETURNTRANSFER  => 1,
        CURLOPT_CONNECTTIMEOUT  => 0,
        CURLOPT_FOLLOWLOCATION  => 1,
        CURLOPT_SSL_VERIFYPEER  => 0,
        CURLOPT_SSL_VERIFYHOST  => 0,
        CURLOPT_REFERER         => $referer,
    ));

    $data = curl_exec($ch); // should Throw on empty...

    if (FALSE === curl_exec($ch) || empty($data))
    {
        Throw New RuntimeException('Request invalid; halting compiler, Curl error' . print_r(curl_error($ch),1));
    }

    curl_close($ch);
    unset($ch); // free


    list($headers, $body) = explode("\r\n\r\n", $data, 2);


        $headers = explode("\r\n", $headers);


    foreach ($headers AS $header)
    {
        if (! stripos($header, 'length', 8)) header($header); // ignore length, we will create our own
    }


    // avoid double slashed urls
    $cpath = ($cdir)
        ? "/website-capture/$cdir/"
        : "/website-capture/";


    $body = preg_replace(
        array (
            '~(?<!\.)\b(?:href|src)\s*=\s*(["\']?+)\K(?:/(?!/)|(?=[\s>]|\1))~i',    # html
            '`\b(?::url)\s*\(\s*(["\']?+)\K(?:/(?!/)|(?=[\s>]|\1))`i',              # css
            '~\/\/~'
        ),
        array (
            $cpath,     # /html
            $cpath,     # /css
            '/'
        ),
    $body);

    echo (FALSE === $body) ?: $body;
