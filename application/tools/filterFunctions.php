<?php
    declare(strict_types = 1);

    function trimDecimal(string $number) : string
    {
        if(strpos($number, '.') !== false)
        {
            while(substr($number, strlen($number) - 1) === '0')
            {
                $number = substr($number, 0, strlen($number) - 1);
            }

            if(substr($number, strlen($number) - 1) === '.')
            {
                $number = substr($number, 0, strlen($number) - 1);
            }
        }

        if(strpos($number, '.') !== false)
        {
            $number = (new \Decimal\Decimal(preg_replace("/[^0-9\.]/", '', $number)))->toFixed(2, true);
        }

        return $number;
    }

    function toURLString(string $string) : string
    {
        $string = strtolower($string);
        $string = str_replace('&', ' and ', $string);
        $string = str_replace('+', ' and ', $string);
        $string = preg_replace("/[^A-Za-z0-9\-]/", '-', $string);

        while(strpos($string, '--') !== false)
        {
            $string = str_replace('--', '_', $string);
        }

        $string = rtrim($string, '-');
        $string = ltrim($string, '-');

        return $string;
    }

    function toDatabaseName(string $string) : string
    {
        $string = strtolower($string);
        $string = str_replace('&', ' and ', $string);
        $string = str_replace('+', ' and ', $string);
        $string = preg_replace("/[^A-Za-z0-9\-]/", '_', $string);

        while(strpos($string, '__') !== false)
        {
            $string = str_replace('__', '_', $string);
        }

        $string = rtrim($string, '_');
        $string = ltrim($string, '_');

        return $string;
    }

    function convertYouTubeURL(string $string) : string
    {
        return preg_replace(
            "/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i",
            "https://www.youtube.com/embed/$2",
            $string
        );
    }