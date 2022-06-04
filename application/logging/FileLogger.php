<?php
    declare(strict_types = 1);

    class FileLogger
    {
        private $fileName;
        private $maxAgeDays;
        private $firstLogLine;


        public function __construct(string $fileName, int $maxAgeDays)
        {
            $this->fileName = $fileName . '-' . (new \DateTime('now', new \DateTimeZone('UTC')))->format('Ymd') . '.log';
            $this->firstLogLine = true;
            $this->maxAgeDays = $maxAgeDays;

            $logFiles = glob($fileName . '-*.log');

            for($i = 0; $i < count($logFiles); $i++)
            {
                try
                {
                    $logDate = str_replace($fileName . '-', '', $logFiles[$i]);
                    $logDate = substr($logDate, 0, strlen($logDate) - 4);

                    $logDate = (int)$logDate;

                    if($logDate < (((int)(new \DateTime('now', new \DateTimeZone('UTC')))->format('Ymd')) - $this->maxAgeDays))
                    {
                        $this->log('Removing old log file: ' . $logFiles[$i]);
                        unlink($logFiles[$i]); 
                    }
                }
                catch(\Exception $ex)
                {
                    echo 'FileLogger Error:' . $ex->getMessage() . "\n";
			        echo $ex->getTraceAsString() . "\n";
                }
                catch(\Throwable $ex)
                {
                    echo 'FileLogger Error:' . $ex->getMessage() . "\n";
			        echo $ex->getTraceAsString() . "\n";
                }
            }
        }

        public function log(string $textToLog, bool $outputToScreen = true)
        {
            if(!isset($this->fileName) || strlen($this->fileName) === 0)
            {
                echo "FileLogger Error: Log filename not set.\n";
                return;
            }

            try
            {
                $logFile = fopen($this->fileName, "a");

                if($this->firstLogLine)
                {
                    fwrite($logFile, "\n");
                }

                $logText = $this->getLogTime() . $textToLog . "\n";

                fwrite($logFile, $logText);

                fclose($logFile);

                if($outputToScreen)
                {
                    echo $logText;
                }

                $this->firstLogLine = false;
            }
            catch(\Exception $ex)
            {
                if($outputToScreen)
                {
                    echo 'FileLogger Error:' . $ex->getMessage() . "\n";
                    echo $ex->getTraceAsString() . "\n";
                }
            }
            catch(\Throwable $ex)
            {
                if($outputToScreen)
                {
                    echo 'FileLogger Error:' . $ex->getMessage() . "\n";
                    echo $ex->getTraceAsString() . "\n";
                }
            }


        }

        private function getLogTime() : string
        {
            return '[' . (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z') . '] ';
        }
    }