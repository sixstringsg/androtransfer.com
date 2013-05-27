<?php
/*
 * Androtransfer.com Download Center
 * Copyright (C) 2012   Daniel Bateman
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function extstr3($content,$start,$end){
  if($content && $start && $end) {
    $r = explode($start, $content);
    if (isset($r[1])){
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
  }
}

function get_info($url){
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"$url");
curl_setopt($ch, CURLOPT_USERAGENT, "AndroBot");
curl_setopt($ch, CURLOPT_REFERER, "http://androtransfer.com/"); 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, "10");
curl_setopt($ch, CURLOPT_TIMEOUT, "10");
$gurl = curl_exec($ch);
curl_close($ch);

return $gurl;
}

function is_available($url, $timeout = 30) {
$ch = curl_init();
$opts = array(CURLOPT_RETURNTRANSFER => true, // do not output to browser
CURLOPT_URL => $url,            // set URL
CURLOPT_NOBODY => true,         // do a HEAD request only
CURLOPT_TIMEOUT => $timeout);   // set timeout
curl_setopt_array($ch, $opts); 
curl_exec($ch);
$retval = curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200; // check if HTTP OK
curl_close($ch); // close handle

return $retval;
}

function percent($num_amount, $num_total) {
 $count1 = $num_amount / $num_total;
 $count2 = $count1 * 100;
 $count = number_format($count2, 0);
 return $count;
}

require_once 'config.php';

$path = $_GET['p'];
$filename = basename($path);
$ext = end(explode('.', $path));
$dir = dirname($path);
$blacklist = array('php');

$server1='http://chaosstats.xfer.in/multiservers/upload/multiserv.php?action=stat';
$server2='http://brizostats.xfer.in/multiservers/upload/multiserv.php?action=stat';
$server3='http://dionysusstats.xfer.in/multiservers/upload/multiserv.php?action=stat';
$server4='http://erebosstats.xfer.in/multiservers/upload/multiserv.php?action=stat';

$server1_res = get_info($server1);
$server2_res = get_info($server2);
$server3_res = get_info($server3);
$server4_res = get_info($server4);

$load1 = extstr3($server1_res,'<load>','</load>');
$load2 = extstr3($server2_res,'<load>','</load>');
$load3 = extstr3($server3_res,'<load>','</load>');
$load4 = extstr3($server4_res,'<load>','</load>');

$load1 = percent($load1,12.00);
$load2 = percent($load2,8.00);
$load3 = percent($load3,6.00);
$load4 = percent($load4,1.00);

$mirrors = array(
    "http://chaos.xfer.in/" => $load1,
    "http://brizo.xfer.in/" => $load2,
    "http://dionysus.xfer.in/" => $load3,
    "http://erebos.xfer.in/" => $load4
);

$mirror = array_search(min($mirrors), $mirrors);

if(in_array($ext, $blacklist)) {
    die($ext." is not an allowed extension.");
}
if(strpos($path, '../') !== false || strpos($path, '..\\') !== false || strpos(realpath($baseDir.'/'.$path), 'public_html') == false) {
    die("not allowed: 2");
}

$location = $baseDir."/.counts";
$fp = fopen($location, "r+");
if ($fp) {
    if (flock($fp, LOCK_EX)) {
        $size = filesize($location);
        $data = fread($fp, $size);
        $counts = json_decode($data, true);
        if ($counts) {
            $count = 1;
            if (isset($counts[$path])) {
                $count = $counts[$path] + 1;
            }
            $counts[$path] = $count;

            $newData = json_encode($counts);
            if ($newData) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, $newData);
            } else {
                file_put_contents($baseDir."/.last_error_encode", "JSON failed to encode: " . json_last_error());
            }
        } else {
            file_put_contents($baseDir."/.last_error_decode", "JSON failed to decode: " . json_last_error());
        }
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}
/*$dc = json_decode(file_get_contents($baseDir."/.counts"), true);
if ($dc && count($dc) > 0) {
    $count = 1;
    if(isset($dc[$path])) {
        $count = $dc[$path]+1;
    }
    $dc[$path] = $count;

    file_put_contents($baseDir."/.counts", json_encode($dc));
} else {
    file_put_contents($baseDir."/.last_error", "JSON failed to decode! " . json_last_error());
}*/

//set mirrors file path
$dlink=$mirror.''.$path;

//double check if file is found, if not server from main server
if(is_available($dlink)){
 header("Location: ".$dlink);
}else{
 header("Content-Disposition: attachment; filename=$filename");
 readfile($baseDir . "/" . $path);
}
?>