<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/logging/Logger.php';

    class RedisCacher
    {
        private static $redis = null;
        private static $connectionFailed = false;

        private static function initConnection() : void
        {
            if(REDIS_ENABLED && is_null(RedisCacher::$redis))
            {
                $logEntry = new \LogEntry();
                $logEntry->setText('Redis Connection');

                $connectionStart = microtime(true);

                try
                {
                    RedisCacher::$redis = new \Redis();
                    RedisCacher::$redis->connect(REDIS_HOST, REDIS_PORT);
                }
                catch(\Exception $e)
                {
                    RedisCacher::$connectionFailed = true;
                }

                $connectionFinish = microtime(true);

                $connectionTime = $connectionFinish - $connectionStart;

                $logEntry->setTimeTaken($connectionTime);

                \Logger::addLog('redis', $logEntry);

                $logEntry = new \LogEntry();
                $logEntry->setText('Redis Connection ' . (!RedisCacher::$connectionFailed ? 'Successful' : 'Failed'));
                $logEntry->setTimeTaken(0);

                \Logger::addLog('redis', $logEntry);
            }
        }

        public static function setCache(string $key, string $value, int $timeout, string $collection = '') : void
        {
            if(!REDIS_ENABLED ||  RedisCacher::$connectionFailed)
            {
                return;
            }

            RedisCacher::initConnection();

            $logEntry = new \LogEntry();
            $logEntry->setText('SET - ' . $key);

            $start = microtime(true);

            if($timeout < 1)
            {
                RedisCacher::$redis->set('Cache:' . md5($key), $value);
            }
            else
            {
                RedisCacher::$redis->set('Cache:' . md5($key), $value);
                RedisCacher::$redis->expire('Cache:' . md5($key), $timeout);
            }

            if(strlen($collection) > 0)
            {
                RedisCacher::$redis->sAdd('Collection:' . $collection, 'Cache:' . md5($key));
            }

            $finish = microtime(true);

            $time = $finish - $start;

            $logEntry->setTimeTaken($time);

            \Logger::addLog('redis', $logEntry);
        }


        public static function clearCollection(string $collection) : void
        {
            if(!REDIS_ENABLED ||  RedisCacher::$connectionFailed)
            {
                return;
            }

            RedisCacher::initConnection();

            RedisCacher::$redis->del(RedisCacher::$redis->sMembers('Collection:' . $collection));
        }



        public static function getCache(string $key) : ?string
        {
            if(!REDIS_ENABLED || RedisCacher::$connectionFailed)
            {
                return null;
            }

            RedisCacher::initConnection();

            $logEntry = new \LogEntry();
            $logEntry->setText('GET - ' . $key);

            $start = microtime(true);

            $retrievedValue = RedisCacher::$redis->get('Cache:' . md5($key)); 

            if($retrievedValue === false)
            {
                $returnValue = null;
            }
            else
            {
                $returnValue = (string)$retrievedValue;
            }

            $finish = microtime(true);

            $time = $finish - $start;

            $logEntry->setTimeTaken($time);

            \Logger::addLog('redis', $logEntry);

            return $returnValue;
        }


        public static function flush() : void
        {
            if(REDIS_ENABLED)
            {
                RedisCacher::$redis->flushDb();
            }
        }
    }