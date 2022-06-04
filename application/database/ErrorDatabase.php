<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/entities/Error.php';
    require_once ROOT_PATH . 'application/utilities/RedisCacher.php';

    class ErrorDatabase
    {
        private $pdo;
        private $errorCount = [];
        private $errorResults = [];

    	public function __construct(\PDO $pdo)
        {
            $this->pdo = $pdo;
        }

        public function getErrors(int $start, int $length, array $sortOrder = []) : array 
        {
        	/*$key = 'ErrorsList_' . $start . '_' . $length . json_encode($sortOrder);
        	if(!array_key_exists($key, $this->errorResults))
            {
                $cachedValue = \RedisCacher::getCache($key);
                if(is_null($cachedValue))
                {*/
                	$unfilteredCount = 0;

                	$stmt = $this->pdo->prepare('
                		SELECT COUNT(1) AS `error_count`
                		FROM `errors`
                		');

           			$stmt->execute();

           			if($row = $stmt->fetch())
                    {
                        $unfilteredCount = (int)$row['error_count'];
                    }


                    $stmt = $this->pdo->prepare('
                    	SELECT `error_id`, e.`error_page_id`, `error_code`, `error_message`, `date_time_logged`, ep.`request_url` FROM `errors` as e
                    		LEFT JOIN `error_pages` as ep ON e.`error_page_id` = ep.`error_page_id` 
                    	ORDER BY e.`date_time_logged` DESC
                        LIMIT :offset, :length');

                    $stmt->bindValue(':offset', $start,  \PDO::PARAM_INT);
                    $stmt->bindValue(':length', $length, \PDO::PARAM_INT);

                    $stmt->execute();

                    $results = [ 
                        'recordsTotal' => $unfilteredCount,
                        'recordsFiltered' => $unfilteredCount,
                        'data' => [] 
                    ];

                     while($row = $stmt->fetch())
                    {
                    	
                        $ids[] = (int)$row['error_id'];

                        $results['data'][] = [ 
                            'id'                => (int)$row['error_id'], 
                            'page_id'              => (string)$row['request_url'], 
                           	'error_code'              => (string)$row['error_code'],
                           	'error_message'              => (string)$row['error_message'],  
                            'date_added'        => datetimeFromDB($row['date_time_logged'])->format('d/m/Y g:ia')
                        ];
                    }

                    /*$this->errorResults[$key] = $results;
                     \RedisCacher::setCache($key, json_encode($this->administratorResults[$key]), ERROR_CACHE_SECONDS, 'Errors');*/
                //}


            //}

            return $results;         
        }
    }