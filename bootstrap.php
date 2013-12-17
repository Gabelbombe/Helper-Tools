<?php

    /** @closure $replaces */
    $replaces = function ($subject, $patterns, $replaces)
    {
        $patterns = array_values($patterns);
        $replaces = array_values($replaces);
        if (count($patterns) !== count($replaces)) return false;

        for ($i=0;$i<count($patterns);$i++)

            $subject = preg_replace($patterns[$i], $replaces[$i], $subject);

        return $subject;
    };

    if (FALSE === strpos($_GET['x'], 'images-'))
    {
        $checkout_dir = substr($_GET['x'],0,strpos(substr($_GET['x'],0), "/"));
        $page = str_replace($checkout_dir,'',$_GET['x']);
        $url = 'http://' . $checkout_dir . '.webarch-rdc-a-dev.erado.com/'. $page;
    }

    else
    {
        $page=str_replace('images-','',$_GET['x']);
        $url = 'http://webarch-rdc-a-dev.erado.com/'. $page;
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

    if (empty($data))
    {
        Throw New RuntimeException('Request invalid; halting compiler'); die;
    }

    curl_close($ch);
    unset($ch);


    list($headers, $body) = explode("\r\n\r\n", $data, 2);


        $headers = explode("\r\n", $headers);


    foreach ($headers AS $header)
    {
        if (! stripos($header, 'length', 8)) header($header); // ignore length, we will create our own
    }

    $cur_path = "/website-capture/$checkout_dir/";

    $body = $replaces($body,
        array (
            '`\b(?:href|src)\s*=\s*(["\']?+)\K(?:/(?!/)|(?=[\s>]|\1))`i',         # html
            '`\b(?::url)\s*\(\s*(["\']?+)\K(?:/(?!/)|(?=[\s>]|\1))`i',            # css
        ),
        array (
            $cur_path,                                                            # /html
            $cur_path,                                                            # /css
        )
    );

    echo (FALSE !== $body) ? $body : 'Error';
