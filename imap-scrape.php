<?php // meant for PHP v5.4+

Namespace MAPReader
{
    Class IMap
    {
        protected   $headers    = [],
                    $bodies     = [],
                    $counts     = [],
                    $mb         = null;

        private     $resource   = null,
                    $list       = null;

        public      $host       = false,
                    $port       = false,
                    $user       = false,
                    $pass       = false;

        public function __construct($host, $port, $user, $pass)
        {
            foreach (['host','port','user','pass'] AS $key)
            {
                $this->$key = $key; // breaks when parent constructs overriding children ....

                if (empty($key))
                {
                    parent::__contruct();
                    break;
                }
            }

            $this->resource = "{{$this->host}:{$this->port}}"; // create our IMap resource
        }

        /**
         * Open IMap loop
         *
         * @return $this
         * @throws \HttpRequestPoolException
         */
        public function open()
        {
            try {
                $this->mb = imap_open($this->resource, $this->user, $this->pass, OP_HALFOPEN);
            } catch (\Exception $e) {
                Throw New \HttpRequestPoolException("Pool issue: ".  print_r($e, 1) . "Logged with error: " . imap_last_error());
            }

            return $this;
        }

        /**
         * Close loop
         *
         * @return bool
         */
        public function close()
        {
            return imap_close($this->mb);
        }

        public function available()
        {
            if (isset($this->mb) && is_resource($this->mb))
            {
                // bitches about void function return vals
                $this->list = imap_list($this->mb, $this->resource, "*");
            }

            return $this;
        }



        /**
         * Read and set headers/bodies
         *
         * @return $this
         */
        public function read()
        {
            $this->counts[] = imap_num_msg($this->mb);

            for ($c=1;$c<=end($this->counts);$c++)
            {
               $this->headers[] = imap_headerinfo($this->mb, $c);
               $this->bodies[]  = imap_fetchbody($this->mb, $c, 1);
            }

                return $this;
        }

        /**
         * Process an individual or a batch
         *
         * @param int $enum
         * @return $this
         */
        public function process($enum=0)
        {
            if (is_int($enum) && isset($this->headers[$enum]) && isset($this->bodies[$enum]))
            {
                $this->doSomething($this->headers[$enum], $this->bodies[$enum]);
            }

            if ('batch' === strtolower($enum) && count($this->headers) === count($this->bodies))
            {
                for ($i=0;$i<count($this->headers);$i++)
                {
                    $this->doSomething($this->headers[$i], $this->bodies[$i]);
                }
            }

            return $this;
        }


        /**
         * What do you have there sir?
         *
         * @return array|null
         */
        public function getMailbox()
        {
            return $this->list;
        }


        /**
         * Verbose implementation of the above
         *
         * @return $this
         */
        public function listMailbox()
        {
            if (isset($this->list) && ! empty($this->list))
            {
                header('Content-type: text/plain');

                foreach ($this->list AS $value)
                {
                    echo "{$value}n";
                }
            }

            return $this;
        }

        /**
         * Should do something with Headers and Body
         *
         * @param array $headers
         * @param $bodies
         * @return string
         */
        private function doSomething(array $headers, $bodies)
        {
            return 'wat-do?';
        }
    }    
}


