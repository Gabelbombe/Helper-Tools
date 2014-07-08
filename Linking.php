<?php

/**
 * Based loosely off of: http://www.dsalgo.com/2013/02/index.php.html
 */

Namespace Linking
{
    /**
     * @property string $next
     */
    Class Node
    {
        public  $data = null,
                $next;

        /**
         * @param $data
         */
        public function __construct($data)
        {
            $this->data = $data;
            $this->next = null;
        }

        public function getNode()
        {
            return $this->data;
        }
    }

    Class Link
    {
        protected $first ,  // first node in set
                  $last  ,  // last node in set
                  $count ;     // total nodes in our list

        /**
         * ...
         */
        public function __construct()
        {
            $this->$first = null;
            $this->last   = null;
            $this->count  = 0;
        }


        /**
         * @param $field
         * @return bool
         */
        public function isEmpty($field)
        {
            return (null === $this->$field);
        }


        /**
         * @param $data
         * @return $this
         */
        public function insertFirst($data)
        {
            $link = New Node($data);
            $link->next = $this->first;
            $this->first =& $link;

            if(null === $this->last)
                $this->last = &$link;

            $this->count++;

            return $this;
        }


        /**
         * @param $data
         * @return $this
         */
        public function insertLast($data)
        {
            if(null !== $this->first)
            {
                $link = New Node($data);
                $this->last->next = $link;
                $link->next = null;
                $this->last =& $link;
                $this->count++;
            } else {
                $this->insertFirst($data);
            }

            return $this;
        }


        /**
         * @return $tmp
         */
        public function deleteFirst()
        {
            $tmp = $this->first;
            $this->first = $this->first->next;

            if(null != $this->first)
                $this->count--;

            return $tmp;
        }


        /**
         * @return $this
         */
        public function deleteLast()
        {
            if(null !== $this->first)
            {
                if(null === $this->first->next)
                {
                    $this->first = null;
                    $this->count--;
                } else {
                    $prev = $this->first;
                    $curr = $this->first->next;

                    while(null !== $curr->next)
                    {
                        $prev = $curr;
                        $curr = $curr->next;
                    }

                    $prev->next = null;
                    $this->count--;
                }
            }

            return $this;
        }


        /**
         * @param $key
         * @return null
         */
        public function delete($key)
        {
            $curr = $this->first;
            $prev = $this->first;

            while($curr->data != $key)
            {
                if(null === $curr->next)
                    return null;

                $prev = $curr;
                $curr = $curr->next;
            }

            if($curr == $this->first)
            {
                if(1 === $this->count)
                    $this->last = $this->first;

                $this->first = $this->first->next;

            } else {

                if($this->last == $curr)
                    $this->last = $prev;

                $prev->next = $curr->next;
            }
            $this->count--;
        }


        /**
         * @return object|null
         */
        public function next()
        {
            return ($tmp = $this->deleteFirst() && ! empty($tmp)) ? $tmp : null;
        }


        /**
         * @param $key
         * @return null|object
         */
        public function search($key)
        {
            $curr = $this->first;

            while($curr->data != $key)
            {
                if(null === $curr->next)
                    return null;

                $curr = $curr->next;
            }
            return $curr;
        }

        /**
         * @param $pos
         * @return null|object
         */
        public function readByNode($pos)
        {
            if($pos <= $this->count)
            {
                $curr = $this->first;
                $pos = 1;
                while($pos != $pos)
                {
                    if(null === $curr->next)
                        return null;

                    $curr = $curr->next;
                    $pos++;
                }
                return $curr->data;
            }

            return null;
        }


        /**
         * @return int
         */
        public function total()
        {
            return $this->count;
        }


        /**
         * @return array
         */
        public function readByList()
        {
            $data = [];
            $curr = $this->first;

            while(null !== $curr)
            {
                array_push($data, $curr->readByNode());
                $curr = $curr->next;
            }
            return $data;
        }


        /**
         * ...
         */
        public function reverse()
        {
            if(null !== $this->first)
            {
                if(null !== $this->first->next)
                {
                    $curr = $this->first;
                    $new = null;

                    while (null !== $curr)
                    {
                        $tmp = $curr->next;
                        $curr->next = $new;
                        $new = $curr;
                        $curr = $tmp;
                    }
                    $this->first = $new;
                }
            }
        }
    }
}