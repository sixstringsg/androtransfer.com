<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

	<? if(($_GET['developer'])&&(!$_GET['folder'])){ ?>
	<title><?=$_GET['developer'];?> Downloads @ AndroXfer.in</title>
	<? } ?>
	<? if(($_GET['developer'])&&($_GET['folder'])){ ?>
	<title><?=$_GET['developer'];?> Downloads For <?=$_GET['folder'];?> @ AndroXfer.in</title>
	<? } ?>
	<? if((!$_GET['developer'])&&(!$_GET['folder'])){ ?>
	<title>AndroXfer.in</title>
	<? } ?>


	<meta name="description" content="">
	<meta name="viewport" content="width=device-width">

	<link rel="stylesheet" href="css/normalize.min.css">
	<link rel="stylesheet" href="css/main.css">
	<link rel="stylesheet" href="css/whhg.css">

	<script src="js/vendor/modernizr-2.6.2-respond-1.1.0.min.js"></script>
</head>