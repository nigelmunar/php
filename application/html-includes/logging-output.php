<?php
    declare(strict_types = 1);

    if($isLocal)
    {
        $log = \Logger::getLog();
        $logOutput = '';
        $totalLogTime = 0.0;
        $totalSqlTime = 0.0;
        $totalMemcachedTime = 0.0;
        $totalRedisTime = 0.0;

        foreach($log as $logType => $logEntries)
        {
            $logOutput .= '<li class="log-type">' . ucwords($logType) . '</li>';

            $logTime = 0.0;

            for($i = 0; $i < count($logEntries); $i++)
            {
                $logOutput .= '<li>';
                $logOutput .= '<span class="log-time">' . rtrim(rtrim(number_format($logEntries[$i]->getTimeTaken(), 4), '0'), '.') . 's</span>';
                $logOutput .= '<span class="log-text">' . htmlspecialchars($logEntries[$i]->getText()) . '</span>';
                
                $logTime += $logEntries[$i]->getTimeTaken();

                switch($logType)
                {
                    case 'sql':
                        foreach($logEntries[$i]->getParameters() as $j => $parameter)
                        {
                            $logOutput .= '<span class="log-parameter">' . htmlspecialchars($parameter[0]) . ' = ' . htmlspecialchars((string)$parameter[1]) . '</span>';
                        }
                        
                        break;
                }

                $logOutput .= '</li>';
            }

            $logOutput .= '<li class="log-subtotal">Total ' . ucwords($logType) . ': ' . rtrim(rtrim(number_format($logTime, 4), '0'), '.') .  's</li>';

            switch($logType)
            {
                case 'sql':
                    $totalSqlTime = $logTime;

                    break;
                case 'memcached':
                    $totalMemcachedTime = $logTime;

                    break;
                case 'redis':
                    $totalRedisTime = $logTime;

                    break;
            }

            $totalLogTime += $logTime;
        }

        $pageFinish = microtime(true);
        $pageTime = $pageFinish - $pageStart;


        $logOutput = '<li class="log-page-grandtotal" onclick="$(this).parent().toggleClass(\'expanded\');"><span>Page Loaded In: </span>' . rtrim(rtrim(number_format($pageTime, 4), '0'), '.') .  's</li><li class="log-grandtotal">Total Logged Time: ' . rtrim(rtrim(number_format($totalLogTime, 4), '0'), '.') .  's</li>' . $logOutput;

        echo "<ul class=\"log\">$logOutput</ul>";
    }
?>