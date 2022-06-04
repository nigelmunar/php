<?php
    declare(strict_types = 1);

    class LogEntry 
    {
        private $_text      = ''; //string
        private $_timeTaken = 0;  //float
        private $_trace     = []; //array

        public function getText() : string
        {
            return $this->_text;
        }

        public function setText(string $text) : void
        {
            $this->_text = $text;
        }

        public function getTimeTaken() : float
        {
            return $this->_timeTaken;
        }

        public function setTimeTaken(float $timeTaken) : void
        {
            $this->_timeTaken = $timeTaken;
        }


        public function getTrace() : array
        {
            return $this->_trace;
        }

        public function setTrace(array $trace) : void
        {
            $this->_trace = $trace;
        }
    }