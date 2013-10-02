<?php
/*
 * test.androxfer.in Download Center
 * Copyright (C) 2012   Daniel Bateman
 *
 * Download mirror edits by Jimmy Rousseau. (LifeOfCoding)
 *
 * Various edits by Luke Street (firstEncounter)
 *
 * Modified 2013 by George Merlocco (scar45)
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
curl_setopt($ch, CURLOPT_REFERER, "http://test.androxfer.in/"); 
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
//$retval='false';
return $retval;
}

function percent($num_amount, $num_total) {
 $count1 = $num_amount / $num_total;
 $count2 = $count1 * 100;
 $count = number_format($count2, 0);
 return $count;
}

require_once 'config.php';

if($_GET['debug']){
error_reporting(E_ALL);
ini_set('display_errors', '1');
}

$path = $_GET['p'];
$filename = basename($path);
$ext = end(explode('.', $path));
$dir = dirname($path);
$blacklist = array('php');


//assign mirrors
//$chaos = 'http://chaos.xfer.in/';
$apollo = 'http://apollo.xfer.in/';
$dionysus = 'http://dionysus.xfer.in/';
//$erebos = 'http://erebos.xfer.in/';

///assign stats urls
//$server1='http://chaosstats.xfer.in/multiservers/upload/multiserv.php?action=stat';
$server2='http://apollostats.xfer.in/multiservers/upload/multiserv.php?action=stat';
$server3='http://dionysusstats.xfer.in/multiservers/upload/multiserv.php?action=stat';
//$server4='http://erebosstats.xfer.in/multiservers/upload/multiserv.php?action=stat';

//$server1_res = get_info($server1); //run curl to get stats
$server2_res = get_info($server2);
$server3_res = get_info($server3);
//$server4_res = get_info($server4);

//$load1 = fetch($server1_res,'<load>','</load>'); //get current load avg from mirrors
$load2 = fetch($server2_res,'<load>','</load>');
$load3 = fetch($server3_res,'<load>','</load>');
//$load4 = fetch($server4_res,'<load>','</load>');

//$load1 = percent($load1,12.00); //generate percent based on number of cpus
$load2 = percent($load2,8.00);
$load3 = percent($load3,6.00);
//$load4 = percent($load4,1.00);

//randomize so script does not favor a server.
// make an array of mirrors and there current cpu load

$random = rand(1,2);
//$random = 1;

if($random == '1'){
$mirrors = array(
//    $chaos => $load1,
    $apollo => $load2,
    $dionysus => $load3
//    $erebos => $load4
);
}
if($random == '2'){
$mirrors = array(
//    $erebos => $load4,
    $dionysus => $load3,
    $apollo => $load2
//    $chaos => $load1
);
}
//if($random == '3'){
//$mirrors = array(
//    $chaos => $load1,
//    $dionysus => $load3,
//    $apollo => $load2,
//    $erebos => $load4
//);
//}
//if($random == '4'){
//$mirrors = array(
//    $erebos => $load4,
//    $apollo => $load2,
//    $dionysus => $load3,
//    $chaos => $load1
//);
//}

// selecting mirror with lowest cpu usage
$mirror = array_search(min($mirrors), $mirrors);
//$mirror='http://dionysus.xfer.in/';

if(in_array($ext, $blacklist)) {
    die($ext." is not an allowed extension.");
}
if(strpos($path, '../') !== false || strpos($path, '..\\') !== false || strpos(realpath($baseDir.'/'.$path), 'public_html') == false) {
    die("<meta http-equiv=\"refresh\" content=\"5;url=http://test.androxfer.in/\" /> Error: 2 [ Not Allowed ]");
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
//echo $dlink;
//die;

if($_GET['directserve']){
 header("Pragma: public");
 header("Expires: 0");
 header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
 header("Cache-Control: public");
 header("Content-Description: File Transfer");
 header("Content-type: application/octet-stream");
 header("Content-Disposition: attachment; filename=\"".$filename."\"");
 header("Content-Transfer-Encoding: binary");
 header("Content-Length: ".filesize($baseDir . "/" . $path));
 readfile($baseDir . "/" . $path);
 die;
}

//double check if file is found and available, if not serve from main server
if(is_available($dlink)){

if($_GET['countdown']){
header("Location: ".$dlink);
die;
}

$ref=$_SERVER['HTTP_REFERER'];
$ref='http://google.com/';

if (strpos($ref,'http://test.androxfer.in/')===0 || strpos($ref,'http')!==0 || strpos($ref,'http://www.test.androxfer.in/')===0){
	header("Location: ".$dlink);
}else{
	$file = $baseDir . "/" . $path;
?>

<?php include 'androxfer-head.php'; ?>
<?php include 'androxfer-header.php'; ?>

    <div id="links" class="andro-column">
		<div id="files" class="andro-column">
			<h2>Downloading: <?=basename($_GET['p']);?></h2>
			<div class="dir-message">
				<p>Please wait while we prepare your download!</p>
				<p>File: <?=basename($_GET['p']);?></p>
				<p>md5: <?=md5_file($file);?></p>
				<p id="counttext"><span id='countDown'>10</span> second(s) left</span></p>
				<div class="downloading-ad">
					<?php include 'androxfer-google_ad_2.php'; ?>
				</div>
			</div>
		</div>
	</div>

<?php include 'androxfer-footer.php'; ?>
	
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
var newtext = "Initializing File Download...";
document.getElementById('counttext').innerHTML = newtext;
document.location.href='http://test.androxfer.in/get.php?p=<?=$_GET['p']?>&countdown=1';
}
</script>

<script>startCountDown(10, 1000, do_download);</script>

</body></html>

<?
}

}else{
	
if($_GET['countdown']){
?>

<?php include 'androxfer-head.php'; ?>
<?php include 'androxfer-header.php'; ?>

    <div id="links" class="andro-column">
		<div id="files" class="andro-column">
			<h2>Downloading: <?=basename($_GET['p']);?></h2>
			<div class="dir-message">
				<p>Please wait while we prepare your download!</p>
				<p>File: <?=basename($_GET['p']);?></p>
				<p>md5: <?=md5_file($file);?></p>
				<p id="counttext"><span id='countDown'>10</span> second(s) left</span></p>
				<div class="downloading-ad">
					<?php include 'androxfer-google_ad_2.php'; ?>
				</div>
			</div>
		</div>
	</div>

<?php include 'androxfer-footer.php'; ?>
	
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
var newtext = "Initializing File Download...";
document.getElementById('counttext').innerHTML = newtext;
document.location.href='http://test.androxfer.in/get.php?p=<?=$_GET['p']?>&countdown=1';
}
</script>

<script>startCountDown(10, 1000, do_download);</script>

</body></html>

<?
die;
}else{

if($_GET['countdown']){
	
 header("Pragma: public");
 header("Expires: 0");
 header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
 header("Cache-Control: public");
 header("Content-Description: File Transfer");
 header("Content-type: application/octet-stream");
 header("Content-Disposition: attachment; filename=\"".$filename."\"");
 header("Content-Transfer-Encoding: binary");
 header("Content-Length: ".filesize($baseDir . "/" . $path));
 readfile($baseDir . "/" . $path);
 die;

}else{
$file = $baseDir . "/" . $path;
?>

<?php include 'androxfer-head.php'; ?>
<?php include 'androxfer-header.php'; ?>

    <div id="links" class="andro-column">
		<div id="files" class="andro-column">
			<h2>Downloading: <?=basename($_GET['p']);?></h2>
			<div class="dir-message">
				<p>Please wait while we prepare your download!</p>
				<p>File: <?=basename($_GET['p']);?></p>
				<p>md5: <?=md5_file($file);?></p>
				<p id="counttext"><span id='countDown'>10</span> second(s) left</span></p>
				<div class="downloading-ad">
					<?php include 'androxfer-google_ad_2.php'; ?>
				</div>
			</div>
		</div>
	</div>

<?php include 'androxfer-footer.php'; ?>
	
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
var newtext = "Initializing File Download...";
document.getElementById('counttext').innerHTML = newtext;
document.location.href='http://test.androxfer.in/get.php?p=<?=$_GET['p']?>&directserve=1';
}
</script>

<script>startCountDown(10, 1000, do_download);</script>

</body></html>

<?
die;
}

}
}
?>
