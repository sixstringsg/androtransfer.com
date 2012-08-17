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

require_once 'config.php';

$path = $_GET['p'];
$filename = basename($path);
$ext = end(explode('.', $path));
$dir = dirname($path);
$blacklist = array('php');

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

header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=$filename");
header("Content-Type: application/zip");
header("Content-Transfer-Encoding: binary");
readfile($baseDir . "/" . $path);
