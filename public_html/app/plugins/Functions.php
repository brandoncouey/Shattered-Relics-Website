<?php
class Functions {

    /**
     * Filters a string.
     * @param $str
     * @return mixed
     */
    public static function filter($str) {
        return filter_var($str, FILTER_SANITIZE_STRING,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }

    /**
     * Filters an integer.
     * @param $int
     * @return mixed
     */
    public static function filterInt($int) {
        return filter_var($int, FILTER_SANITIZE_NUMBER_INT,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }

    public static function debug($array) {
        echo "<pre>".htmlspecialchars(json_encode($array, JSON_PRETTY_PRINT))."</pre>";
    }

    public static function printStr($str) {
        echo "<pre>".$str."</pre>";
    }

    public static function friendlyTitle($string, $wordLimit = 0){
        $separator = '-';

        if($wordLimit != 0){
            $wordArr = explode(' ', $string);
            $string = implode(' ', array_slice($wordArr, 0, $wordLimit));
        }

        $quoteSeparator = preg_quote($separator, '#');

        $trans = array(
            '&.+?;'                 => '',
            '[^\w\d _-]'            => '',
            '\s+'                   => $separator,
            '('.$quoteSeparator.')+'=> $separator
        );

        $string = strip_tags($string);
        foreach ($trans as $key => $val){
            $string = preg_replace('#'.$key.'#i', $val, $string);
        }

        $string = strtolower($string);

        return trim(trim($string, $separator));
    }
    
    public static function generateString($n = 15) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }

    public static function elapsed( $ptime ) {
        $etime = time() - $ptime;

        if ( $etime < 1 ) {
            return '0 seconds - '.$etime;
        }

        $a = array(
            12 * 30 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ( $a as $secs => $str ) {
            $d = $etime / $secs;
            if ( $d >= 1 ) {
                $r = round( $d );
                return $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
            }
        }
    }

    public static function getLastNDays($days, $format = 'n j'){
        $m  = date("m");
        $de = date("d");
        $y  = date("Y");

        $dateArray = [];

        for($i = 0; $i <= $days - 1; $i++){
            $dateArray[] = date($format, mktime(0,0,0,$m,($de-$i),$y));
        }

        return array_reverse($dateArray);
    }
}
?>