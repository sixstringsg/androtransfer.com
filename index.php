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


$thedomain =  $_SERVER['HTTP_HOST'];
$thedomain = "http://" .$thedomain. "/";


if(($thedomain!='http://androtransfer.com/')&&($thedomain!='http://www.androtransfer.com/')){
header('Location: http://androtransfer.com/',true,301);
die;
}

require_once 'config.php';
require_once 'markdown.php';

$currentDeveloper = $_GET['developer'];
if(!in_array($currentDeveloper, $users))
    die("Access denied.");
$currentFolder = $_GET['folder'];
if(strpos($currentFolder, '..') !== false)
    die("Access denied.");
$totalPath = null;

$fp = fopen($baseDir."/.counts","r");
$downloadCounts = array();
if ($fp) {
    if (flock($fp, LOCK_SH)) {
        $downloadCounts = json_decode(file_get_contents($baseDir."/.counts"), true);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}
if(!$downloadCounts)
    $downloadCounts = array();

$fp = fopen($baseDir."/.md5s","r");
$fileMd5s = array();
$md5dsLoaded = false;
if ($fp) {
    if (flock($fp, LOCK_SH)) {
        $fileMd5s = json_decode(file_get_contents($baseDir."/.md5s"), true);
        flock($fp, LOCK_UN);
        if ($fileMd5s)
            $md5sLoaded = true;
    }
    fclose($fp);
}

$fileMTimes = array();

define("FILE_FILTER_FILES", 0x1);
define("FILE_FILTER_DIRS", 0x2);
define("FILE_FILTER_ALL", FILE_FILTER_DIRS | FILE_FILTER_FILES);
function getAllInFolder($folder, $filter=FILE_FILTER_ALL) {
    global $globalBlacklist;
    $handle = opendir($folder);
    $entries = array();
    if ($handle) {
        while (false !== ($entry = readdir($handle))) {
            $entryPath = $folder."/".$entry;
            if ($entry[0] == '.')
                continue;
            if (in_array($entry, $globalBlacklist))
                continue;

            if ((is_dir($entryPath) && $filter & FILE_FILTER_DIRS) ||
                (!is_dir($entryPath) && $filter & FILE_FILTER_FILES)) {
                $entries[] = $entry;
            }
        }
        closedir($handle);
    }
    return $entries;
}

function sizePretty($bytes) {
    if($bytes >= GB)
        return number_format($bytes/GB) . " GB";
    else if($bytes >= MB)
        return number_format($bytes/MB) . " MB";
    else if($bytes >= KB)
        return number_format($bytes/KB) . " KB";
    return number_format($bytes) . " bytes";
}

function md5_file_alt($file) {
    $fileContents = file_get_contents($file);
    return md5($fileContents);
}

if ($currentDeveloper) {
    $devPath = $baseDir."/".$currentDeveloper;
    $subFolders = getAllInFolder($devPath, FILE_FILTER_DIRS);
    sort($subFolders);

    if (!$currentFolder) {
        $currentFolder = '.';
    }

    if ($currentFolder) {
        $folderPath = $devPath."/".$currentFolder;
        $totalPath = $folderPath;
        $files = getAllInFolder($folderPath, FILE_FILTER_FILES);
        $handle = opendir($folderPath);
        $md5s = array();
        if (!empty($files)) {
            $folderReadme = file_get_contents($folderPath."/.readme");

            $blacklist = explode("\n", file_get_contents($folderPath."/.hide"));
            function fileFilterForBlacklist($file) {
                global $blacklist;
                if (in_array($file, $blacklist)) {
                    return false;
                }
                return true;
            }
            $files = array_filter($files, "fileFilterForBlacklist");

            if ($md5sLoaded) {
                $md5Done = false;
                foreach ($files as $file) {
                    $rp = realpath($totalPath . "/" . $file);
                    $resolvedPath = substr($rp, strpos($rp, "public_html")+strlen("public_html/"));
                    $fileMTimes[$resolvedPath] = $mtime = filemtime($rp);
                    if ((time()-$mtime) < 120) {
                        continue;
                    }
                    if (!$md5Done && (!isset($fileMd5s[$resolvedPath]) || trim($fileMd5s[$resolvedPath]) == '')) {
                        $md5 = md5_file($rp);
                        if ($md5 !== false) {
                            $fileMd5s[$resolvedPath] = $md5;
                        } else {
                            $md5 = md5_file_alt($rp);
                            if ($md5 !== false) {
                                $fileMd5s[$resolvedPath] = $md5;
                            }
                        }
                        $md5Done = true;
                    }
                }

                $fp = fopen($baseDir."/.md5s","w");
                if ($fp) {
                    $data = json_encode($fileMd5s);
                    if ($data && flock($fp, LOCK_EX)) {
                        fwrite($fp, $data);
                        flock($fp, LOCK_UN);
                    }
                    fclose($fp);
                }
            }

            $rawMD5s = explode("\n", file_get_contents($folderPath."/.md5"));
            foreach ($rawMD5s as $line) {
                if($line[0] == '#')
                    continue;
                if(trim($line) == '')
                    continue;
                $lineEnd = strpos($line, "#");
                if($lineEnd !== false)
                    $line = substr($line, 0, $lineEnd);
                $split = explode(" ", $line);
                $split = array_filter(array_map("trim", $split));
                $file = array_shift(array_values($split));
                $md5 = end($split);

                $rp = realpath($totalPath . "/" . $file);
                $resolvedPath = substr($rp, strpos($rp, "public_html")+strlen("public_html/"));
                $fileMd5s[$resolvedPath] = $md5;
            }
        }

        function test_date($x, $y) {
            global $totalPath, $fileMTimes;
            $rp = realpath($totalPath . "/" . $x);
            $resolvedPath = substr($rp, strpos($rp, "public_html")+strlen("public_html/"));
            $dateX = $fileMTimes[$resolvedPath];
            $rp = realpath($totalPath . "/" . $y);
            $resolvedPath = substr($rp, strpos($rp, "public_html")+strlen("public_html/"));
            $dateY = $fileMTimes[$resolvedPath];
            if($dateX < $dateY) return 1;
            else if($dateX > $dateY) return -1;
            return 0;
        }
        usort($files, "test_date");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <!---<title><?= $siteName ?></title>--->

<? if(($_GET['developer'])&&(!$_GET['folder'])){ ?>
<title><?=$_GET['developer'];?> Downloads At Androtransfer.com</title>
<? } ?>
<? if(($_GET['developer'])&&($_GET['folder'])){ ?>
<title><?=$_GET['developer'];?> Downloads For <?=$_GET['folder'];?> At Androtransfer.com</title>
<? } ?>
<? if((!$_GET['developer'])&&(!$_GET['folder'])){ ?>
<title>Androtransfer.com</title>
<? } ?>

    <link type='text/css' rel='stylesheet' href='style.css'/>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-23907858-2']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</head>
<body>

    <div id='header'>
        <!---<img src='images/title.png' width='446' height='92' />--->

        <table width="100%"><tr><td><img src="http://androtransfer.com/images/title.png" width="98%" height="auto"></td>
<td style="text-align:right;">
<script type="text/javascript"><!--
google_ad_client = "ca-pub-6244853272122205";
/* Bottom bar */
google_ad_slot = "7769612158";
google_ad_width = 468;
google_ad_height = 60;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
</td>
</tr>
</table>

    </div>
    <div id='links' class='block'>
        <h2>Select a developer</h2>
        <?php foreach($users as $user): ?>
        <a href='?developer=<?= $user ?>'><?= $user ?></a>
        <?php endforeach ?>
        <div style='clear: both'></div>
    </div>

    <div id='page'>
        <?php if($currentDeveloper): ?>
            <div id='sidebar'>
                <div class='block'>
                    <h2><?= htmlspecialchars($currentDeveloper) ?></h2>
                    <ul>
                    <?php foreach($subFolders as $folder): ?>
                        <li class='<?= $currentFolder == $folder ? "active" : "" ?>'><a href='?developer=<?= rawurlencode($currentDeveloper) ?>&amp;folder=<?= rawurlencode($folder) ?>'><?= $folder ?></a><li>
                    <?php endforeach ?>
                    </ul>
                </div>
            </div>

            <?php if($currentFolder): ?>
                <div style='float: left; margin-left: 10px; width: 668px'>
                    <div class='block'>
                        <h2><?= htmlspecialchars($currentFolder) ?></h2>
                        <?php if (count($files) > 0): ?>
                            <table>
                                    <tr>
                                        <th align='left'>File</th>
                                        <th align='left' width='120px' style='padding-right: 50px'>Last Mod.</th>
                                        <th align='left' width='80px'>Size</th>
                                        <th align='right' width='80px'>Downloads</th>
                                    </tr>
                                    <?php foreach($files as $file): ?>
                                        <?php
                                        $rp = realpath($totalPath . "/" . $file);
                                        $resolvedPath = substr($rp, strpos($rp, "public_html")+strlen("public_html/"));
                                        $filePath = $baseDir . "/" . $resolvedPath;
                                        ?>

<?
///if (file_exists('/home/website/www/androtransfer.com/public_html/AOKP/a510/aokp_a510_jb-mr1_build-3.zip')) {

if (file_exists($filePath)) {
    //echo "$filePath was last modified: " . date ("F d Y H:i:s.", filemtime($resolvedPath));
    $last_modified = date ("F dS Y", filemtime($resolvedPath));
}else{
    $last_modified = 'N/A';
}
?>

                                        <tr class='download'>
                                            <td>
                                                <div class='name'><a style='display: block' href='get.php?p=<?= $resolvedPath ?>'><?= $file ?></a></div>
                                                <?php if(isset($fileMd5s[$resolvedPath])): ?>
                                                    <span class='info'><strong>MD5:</strong> <span style='font-family: Courier'><?= $fileMd5s[$resolvedPath] ?></span></span>
                                                <?php endif ?>

                                            <!---<span class='info'><strong>MD5:</strong> <span style='font-family: Courier'><?//=md5_file($resolvedPath);?></span></span>--->
                                            </td>
                                            <td><?=$last_modified?></td>
                                            <!---<td><?= date("F dS Y", $fileMTimes[$resolvedPath]) ?></td>--->
                                            <td>
                                                <?= sizePretty(filesize($filePath)) ?>
                                            </td>
                                            <td style='font-size: 24px; text-align: right;'>
                                                <?= number_format(isset($downloadCounts[$resolvedPath]) ? $downloadCounts[$resolvedPath] : 0) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                            </table>
                        <?php else: ?>
<? if(!$_GET['folder']){ ?>
                            Please select a device on your left.
<? }else{ ?>
                            No files found.
<? } ?>
                        <?php endif ?>
                    </div>

                    <?php if ($folderReadme): ?>
                        <div class='block'>
                            <h2>.readme</h2>
                            <div class='readme'>
                                <?= Markdown($folderReadme) ?>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
            <?php endif ?>
        <?php else: ?>
            <div id='content'>
                Click a link at the top to view each developers' files.
            </div>
        <?php endif ?>
        <div style='clear: both'></div>
    </div>

<!---
<br><br>
<div style="-webkit-box-shadow: 0px 1px 30px 2px #000000; box-shadow: 0px 1px 30px 2px #000000; -webkit-border-radius: 10px;
border-radius: 10px; text-align:center; padding-top:5px; padding-bottom:10px; margin:0px auto;background-color:#000;width:480px;">
<a href="http://hxcmusic.com/" style="text-decoration:none;"><p style="width:100%:height:100%;"><img src="http://hxcmusic.com/images/logo.me4.1.png" style="height:75px; width:auto;"><br><span style="text-shadow: 0px 0px 10px rgba(255, 255, 255, 1);font-family:'Arial',Arial,Helvetica,sans-serif;font-size:13px;font-style:italic;color:#CCC;">Free Online Music Service & Internet Radio</span></p></a>
</div>
<br>
--->


<div style="text-align:center; width:100%; padding:20px 0px; margin:0px auto;"><a href="http://www.bytemark.co.uk/r/androtransfer"> <img src="http://knok.exynos.co/wp-content/uploads/2012/07/bytemark_mono.png" style="height:40px; width:auto;" />
</a><a href="http://hxcmusic.com/"><img src="http://hxcmusic.com/images/hxc_ad_small_white.png" style="height:40px; width:auto;"></a></div>

<br><br><br><br>
<!---<table width="100%" height="80px">
<tr>
<td style="text-align:center;">--->
<center><script type="text/javascript"><!--
google_ad_client = "ca-pub-6244853272122205";
/* Top Bar */
google_ad_slot = "6876020546";
google_ad_width = 728;
google_ad_height = 90;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script></center>
<!---</td>
<td style="text-align:center;">

<div style="-webkit-box-shadow: 0px 1px 30px 2px #000000; box-shadow: 0px 1px 30px 2px #000000; -webkit-border-radius: 10px;
border-radius: 10px; text-align:center; padding-top:5px; padding-bottom:10px; margin:0px auto;background-color:#000;width:480px;">
<a href="http://hxcmusic.com/" style="text-decoration:none;"><p style="width:100%:height:100%;"><img src="http://hxcmusic.com/images/logo.me4.1.png" style="height:75px; width:auto;"><br><span style="text-shadow: 0px 0px 10px rgba(255, 255, 255, 1);font-family:'Arial',Arial,Helvetica,sans-serif;font-size:13px;font-style:italic;color:#CCC;">Free Online Music Service & Internet Radio</span></p></a>
</div>
<br>

</td>
</tr>
</table>
--->

</body>
</html>
