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

// Sizes
define("ONE_K", 1024);
define("KB", ONE_K);
define("MB", ONE_K * KB);
define("GB", ONE_K * MB);
define("TB", ONE_K * GB);

// Site
$siteName = "androtransfer.com";
$baseDir = ".";
$users = explode("\n", file_get_contents($baseDir.'/.users'));
$globalBlacklist = array(
    "images",
    "private"
);
