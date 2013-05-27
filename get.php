<?php
/*
 * Androtransfer.com Download Center
 * Copyright (C) 2012   Daniel Bateman
 *
 * Download mirror edits by Jimmy Rousseau. (LifeOfCoding)
 *
 * Various edits by Luke Street (firstEncounter)
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

function fetch($content,$start,$end){
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

//assign mirrors
$chaos = 'http://chaos.xfer.in/';
$apollo = 'http://apollo.xfer.in/';
$dionysus = 'http://dionysus.xfer.in/';
$erebos = 'http://erebos.xfer.in/';

///assign stats urls
$server1='http://chaosstats.xfer.in/multiservers/upload/multiserv.php?action=stat';
$server2='http://apollostats.xfer.in/multiservers/upload/multiserv.php?action=stat';
$server3='http://dionysusstats.xfer.in/multiservers/upload/multiserv.php?action=stat';
$server4='http://erebosstats.xfer.in/multiservers/upload/multiserv.php?action=stat';

$server1_res = get_info($server1); //run curl to get stats
$server2_res = get_info($server2);
$server3_res = get_info($server3);
$server4_res = get_info($server4);

$load1 = fetch($server1_res,'<load>','</load>'); //get current load avg from mirrors
$load2 = fetch($server2_res,'<load>','</load>');
$load3 = fetch($server3_res,'<load>','</load>');
$load4 = fetch($server4_res,'<load>','</load>');

$load1 = percent($load1,12.00); //generate percent based on number of cpus
$load2 = percent($load2,8.00);
$load3 = percent($load3,6.00);
$load4 = percent($load4,1.00);

//randomize so script does not favor a server.
// make an array of mirrors and there current cpu load

$random = rand(1,4);

if($random == '1'){
$mirrors = array(
    $chaos => $load1,
    $apollo => $load2,
    $dionysus => $load3,
    $erebos => $load4
);
}
if($random == '2'){
$mirrors = array(
    $erebos => $load4,
    $dionysus => $load3,
    $apollo => $load2,
    $chaos => $load1
);
}
if($random == '3'){
$mirrors = array(
    $chaos => $load1,
    $dionysus => $load3,
    $apollo => $load2,
    $erebos => $load4
);
}
if($random == '4'){
$mirrors = array(
    $erebos => $load4,
    $apollo => $load2,
    $dionysus => $load3,
    $chaos => $load1
);
}

// selecting mirror with lowest cpu usage
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

//double check if file is found and available, if not serve from main server
if(is_available($dlink)){
 ///header("Location: ".$dlink);

$ref=$_SERVER['HTTP_REFERER'];
if (strpos($ref,'http://androtransfer.com/')===0 || strpos($ref,'http')!==0){
	header("Location: ".$dlink);
}else{
	$file = $baseDir . "/" . $path;
?>

<!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <title>androtransfer.com</title>
    <link type="text/css" rel="stylesheet" href="http://androtransfer.com/style.css">
<script type="text/rocketscript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-23907858-2']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<link rel="stylesheet" type="text/css" href="http://knok.exynos.co/andro/ffstylesheet.css">
<style type="text/css">
body { font-family: "robotoregular" !important;}
</style>

<script type="text/javascript">
function startCountDown(i, p, f) {
// store parameters
var pause = p;
var fn = f;
// make reference to div
var countDownObj = document.getElementById("countDown");
if (countDownObj == null) {
// error
alert("div not found, check your id");
// bail
return;
}
countDownObj.count = function(i) {
// write out count
countDownObj.innerHTML = i;
if (i == 0) {
// execute function
fn();
// stop
return;
}
setTimeout(function() {
// repeat
countDownObj.count(i - 1);
},
pause
);
}
// set it going
countDownObj.count(i);
}

function do_download(){
var newtext = "Please wait, Serving file...";
document.getElementById('counttext').innerHTML = newtext;
document.location.href='http://androtransfer.com/get.php?p=<?=$_GET['p']?>';
}
</script>

</head>
<body>
<script type="text/rocketscript"><!--
google_ad_client = "ca-pub-6244853272122205";
/* Top Bar */
google_ad_slot = "6876020546";
google_ad_width = 728;
google_ad_height = 90;
//-->
</script>
<script type="text/rocketscript" data-rocketsrc="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

    <div id="header">
        <img src="http://androtransfer.com/images/title.png" width="446" height="92">
    </div>
    <?
	$menu = file_get_contents('http://androtransfer.com');
	$menu = fetch($menu,"<div id='links' class='block'>","<div id='page'>");
	?>
    <div id="links" class="block">
	<?=$menu?>

<div style="margin: 20px auto; padding: 5% 10%; text-align:center; border:1px solid #e4e4e4; background-color:#f8f8f8;">
    <span style="font-size:30px; font-family:robotobold; line-height:35px;">Please wait while we prepare your download!</span>
    <br><br><br>
    <span style="font-size:20px; font-family:robotobold;">File downloading: </span><span style="font-size:20px;"><?=basename($_GET['p']);?></span>
    <br><br>
    <span style="font-size:20px; font-family:robotobold;">File MD5sum: </span><span style="font-size:20px;"><?=md5_file($file);?></span>
    <br><br><br>
    <span style="font-size:40px; font-family:robotobold; color:#7ecc60;" id="counttext"><span id='countDown'>10</span> second(s) left</span>
</div>




<div style="text-align:center; width:100%; padding:20px 0px; margin:0px auto;"><a href="http://www.bytemark.co.uk/r/androtransfer"><img src="http://knok.exynos.co/wp-content/uploads/2012/07/bytemark_mono.png" style="height:40px; width:auto;">
</a></div>
<script type="text/rocketscript"><!--
google_ad_client = "ca-pub-6244853272122205";
/* Bottom bar */
google_ad_slot = "7769612158";
google_ad_width = 468;
google_ad_height = 60;
//-->
</script>
<script type="text/rocketscript" data-rocketsrc="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
<script>startCountDown(10, 1000, do_download);</script>

</body></html>

<?
}

}else{
 header("Content-Disposition: attachment; filename=$filename");
 readfile($baseDir . "/" . $path);
}
?>
