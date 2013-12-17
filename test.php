<?php

Class LibValidate
{
    private $_errLog = array();

    /**
     * Validate email address
     *
     * @param $address
     * @return bool|mixed
     */
    public function Email($address)
    {
        if (is_array($address))

            return (count($address) === array_filter(filter_var_array($address, FILTER_VALIDATE_EMAIL)));

        return (filter_var($address, FILTER_VALIDATE_EMAIL));
    }

    /**
     * Validate password
     *
     * @param  string $pass
     * @param  string $user
     * @return bool
     */
    public function Password($pass, $user)
    {
        if (empty($pass) || empty($user)) return false; // make sure we have values

        // return (bool) 1 if all condt are met (bool) 0 otherwise
        return ( strlen($pass) > 8
            || preg_match('(^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$)', $pass)
            || $this->nequals($pass, array($user, 'password', '12345678'))
            || ! stristr($pass,'password')
        );
    }

    /**
     * Validate S/MIME key
     * NOTE: Bug: openssl_x509_checkpurpose is returning "false" on valid S/MIME keys because of a key chain issue?
     * See: https://bugs.php.net/bug.php?id=42886 for more info. May not be a problem as nobody EVER has uploaded a
     * S/MIME key!
     *
     * @param $key
     * @param $keyInfo
     * @return bool|$this
     */
    public function SMimeKey ($key, $keyInfo)
    {
        if (1 !== openssl_x509_checkpurpose($key, X509_PURPOSE_SMIME_ENCRYPT))
        {
            $this->_errLog['err'][] = "Error in openssl_x509_checkpurpose with {$key}";
            return false;
        }

        $ssl2Time = function ($in) // closures are better than nested functions
        {
            //              hour,              minute,            second,             month,             day,               year
            return gmmktime(substr($in, 6, 2), substr($in, 8, 2), substr($in, 10, 2), substr($in, 2, 2), substr($in, 4, 2), substr($in, 0, 2));
        };

        return $this->$key = ($keyInfo + array (
            'key_address'       => openssl_x509_parse($key) ['subject']['emailAddress'],
            'key_expires'       => date("Y-m-d H:i:s", $ssl2Time(openssl_x509_parse($key) ['validTo'])),
            'key_fingerprint'   => openssl_x509_parse($key) ['hash']
        ));

    }

    /**
     * Returns S/Mime or bool
     *
     * @param $key
     * @return bool|array
     */
    public function getSMimeKey ($key)
    {
        return (isset($this->$key)) ? $this->$key : FALSE;
    }

    /**
     * Compare a value against another value
     *
     * @param $comp1
     * @param bool $comp2
     * @return bool
     */
    private function nequal($comp1, $comp2 = false)
    {
        return strtolower(trim($comp1)) !== strtolower(trim($comp2));
    }

    /**
     * Compare a value against contents of an array
     *
     * @param  string $comp1 comparator
     * @param  array  $comp2 comparisons
     * @return bool
     */
    protected function nequals($comp1, $comp2= array())
    {
        foreach ($comp2 AS $comparison)
        {
            if (strtolower(trim($comp1)) === strtolower(trim($comparison))) return false;
        }
        return true;
    }

}