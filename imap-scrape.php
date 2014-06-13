<?php // meant for PHP v5.4+

Namespace MAPReader
{
	Class Imap
	{

		protected 	$headers 	= [],
					$bodies		= [],
					$counts		= [];

		public function __construct()
		{
			// ....
		}

		public function open()
		{
			$this->mb = imap_open("{$this->host}:{$this->port}/imap", $this->user, $this->passs);

				return $this;
		}

		public function read()
		{
			$this->count[] = imap_num_msg($this->mb);

			for ($c=1;$c<=$cnt;$c++)
			{
			   $this->headers[] = imap_headerinfo($this->mb, $c);
			   $this->bodies[]  = imap_fetchbody($this->mb, $c, 1);
			}

				return $this;
		}

		public function process($enum=0)
		{
			if (is_int($enum) && isset($this->headers[$enum]) && isset($this->bodies[$enum]))
			{
				$this->doSomething($this->headers[$enum], $this->bodies[$enum]);
			}

			if ('batch' === strtolower($enum) && count($this->headers) === count($this->bodies))
			{
				for ($c=0;$i<count($this->headers);$i++)
				{
					$this->doSomething($this->headers[$i], $this->bodies[$i]);
				}
			}

				return $this;
		}

		private function doSomething(array $headers, $bodies)
		{
			return 'wat-do?';
		}
	}	
}


