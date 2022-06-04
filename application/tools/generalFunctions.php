<?php
    declare(strict_types = 1);

    function print_svg(string $file) : string
    {
        $iconfile = new DOMDocument();
        $iconfile->load($file);
        return $iconfile->saveHTML($iconfile->getElementsByTagName('svg')[0]);
    }

    function getUserIP() : string
    {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                  $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = (isset($_SERVER['HTTP_CLIENT_IP']) ? @$_SERVER['HTTP_CLIENT_IP'] : '');
        $forward = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? @$_SERVER['HTTP_X_FORWARDED_FOR'] : '');
        $remote  = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
    
        if(filter_var($client, FILTER_VALIDATE_IP)) { $ip = $client; }
        elseif(filter_var($forward, FILTER_VALIDATE_IP)) { $ip = $forward; }
        else { $ip = $remote; }
    
        return $ip;
    }

    function startsWith(string $haystack, string $needle) : bool 
    {
        if(strlen($needle) > strlen($haystack))
        {
            return false;
        }

        $length = strlen($needle);

        return substr($haystack, 0, $length) === $needle;
    }


    function endsWith(string $haystack, string $needle) : bool 
    {
        if(strlen($needle) > strlen($haystack))
        {
            return false;
        }

        $length = strlen($needle);

        return substr($haystack, strlen($haystack) - $length, $length) === $needle;
    }

    
    function generateRandomString(int $length = 10) : string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function calculateMaxPages(int $recordCount, int $pageSize)
    {
        $maxPages = ((float)$recordCount / (float)$pageSize);

        if(!compareFloatNumbers((float)(int)$maxPages, (float)$maxPages))
        {
            return max((int)$maxPages + 1, 1);
        }
        
        return max((int)$maxPages, 1);
    }

    function clamp_int(int $num, int $min, int $max) : int
    {
        return max($min, min($max, $num));
    }

    function compareFloatNumbers($float1, $float2, $operator='=')  
    {  
        // Check numbers to 5 digits of precision  
        $epsilon = 0.00001;  
        
        $float1 = (float)$float1;  
        $float2 = (float)$float2;  
        
        switch ($operator)  
        {  
            // equal  
            case "=":  
            case "eq":  
            {  
                if (abs($float1 - $float2) < $epsilon) {  
                    return true;  
                }  
                break;    
            }  
            // less than  
            case "<":  
            case "lt":  
            {  
                if (abs($float1 - $float2) < $epsilon) {  
                    return false;  
                }  
                else  
                {  
                    if ($float1 < $float2) {  
                        return true;  
                    }  
                }  
                break;    
            }  
            // less than or equal  
            case "<=":  
            case "lte":  
            {  
                if (compareFloatNumbers($float1, $float2, '<') || compareFloatNumbers($float1, $float2, '=')) {  
                    return true;  
                }  
                break;    
            }  
            // greater than  
            case ">":  
            case "gt":  
            {  
                if (abs($float1 - $float2) < $epsilon) {  
                    return false;  
                }  
                else  
                {  
                    if ($float1 > $float2) {  
                        return true;  
                    }  
                }  
                break;    
            }  
            // greater than or equal  
            case ">=":  
            case "gte":  
            {  
                if (compareFloatNumbers($float1, $float2, '>') || compareFloatNumbers($float1, $float2, '=')) {  
                    return true;  
                }  
                break;    
            }  
            case "<>":  
            case "!=":  
            case "ne":  
            {  
                if (abs($float1 - $float2) > $epsilon) {  
                    return true;  
                }  
                break;    
            }  
            default:  
            {  
                die("Unknown operator '".$operator."' in compareFloatNumbers()");     
            }  
        }  
        
        return false;  
    }  
    
    function deleteDir($dirPath) : void
    {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    function getPagerHtml(string $baseURL, int $page, int $numPages, int $recordCount, int $pageSize, string $singlularEntityName, string $pluralEntityName) : string
    {
        $minPagesForElipsis = 11;

        $html = '';

        if($numPages > 1)
        {
            $qsCharacter = '?';

            if(strpos($baseURL, '?') > 0)
            {
                $qsCharacter = '&amp;';
            }

            $html = '<div class="pagination">';
            $html .= '<div class="pagination__info">';
            $html .= 'Showing ' . $singlularEntityName . ' ' . ((($page - 1) * $pageSize) + 1) . ' to ' . min($recordCount, $page * $pageSize);
            $html .= ' of ' . number_format($recordCount, 0) . ' ' . $pluralEntityName;
            $html .= '</div>';

            $html .= '<div class="pagination__container">';
            $html .= '<a href="' . $baseURL;
            
            if($page - 1 > 1)
            {
                $html .= $qsCharacter . 'page=' . ($page - 1);
            }

            $html .= '" class="pagination__link previous';

            if($page <= 1)
            {
                $html .= ' disabled';
            }

            $html .= '">Previous</a>';

            $html .= '<span class="pagination__numbers">';

            $upperPage = min(2, $numPages);

            //Start pages
            for($i = 1; $i <= $upperPage; $i++)
            {
                $html .= '<a href="' . $baseURL;

                if($i > 1)
                {
                    $html .= $qsCharacter . 'page=' . $i;
                }

                $html .= '" class="pagination__link';
                
                if($i === $page)
                {
                    $html .= ' pagination__link--active';
                }

                $html .= '">' . $i . '</a>';
            }


            if($numPages > 2)
            {
                if($page > 6 && $numPages >= $minPagesForElipsis)
                {
                    $html .= '<span>...</span>';
                }

                if($page <= 6)
                {
                    $lowerPage = 3;
                }
                else
                {
                    $lowerPage = max(min(max(($page - 2), 3), ($numPages - 6)), 3);
                }

                if(($numPages - $page) >= 6 && $numPages >= $minPagesForElipsis)
                {
                    if($page > 6 && $numPages >= $minPagesForElipsis)
                    {
                        $upperPage = min(max(($page + 2), 7), ($numPages - 2));
                    }
                    else
                    {
                        $upperPage = min(max(($page + 1), 7), ($numPages - 2));
                    }
                }
                else
                {
                    if($page > 6) 
                    {
                        $lowerPage = max(min(max(($page - 1), 3), ($numPages - 6)), 3);
                    }

                    $upperPage = $numPages - 2;
                }

                //Middle pages
                for($i = $lowerPage; $i <= $upperPage; $i++)
                {
                    $html .= '<a href="' . $baseURL . $qsCharacter . 'page=' . $i . '" class="pagination__link';
                    
                    if($i === $page) 
                    {
                        $html .= ' pagination__link--active';
                    }

                    $html .= '">' . $i . '</a>';
                }

                if(($numPages - $page) >= 6 && $numPages >= $minPagesForElipsis)
                {
                    $html .= '<span>...</span>';
                }

                //End pages
                for($i = max(max($upperPage + 1, ($numPages - 1)), 3); $i <= $numPages; $i++)
                {
                    $html .= '<a href="' . $baseURL . $qsCharacter . 'page=' . $i . '" class="pagination__link';
                    
                    if($i === $page) 
                    {
                        $html .= ' pagination__link--active';
                    }

                    $html .= '">' . $i . '</a>';
                }
            }



            $html .= '</span>';

            $html .= '<a href="';
            
            if($page < $numPages)
            {
                $html .= $baseURL . $qsCharacter . 'page=' . ($page + 1);
            } 

            $html .= '" class="pagination__link next';
            
            if($page >= $numPages)
            {
                $html .= ' disabled';
            } 

            $html .= '">Next</a>';

            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }