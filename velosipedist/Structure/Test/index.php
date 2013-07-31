<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/yii-mini.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/CFileHelper.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/yaml/spyc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/structure/Structure.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/structure/StructureTest.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/helpers.php';

$struct = new StructureTest($_SERVER['DOCUMENT_ROOT'] . '/lib/structure/fixtureConfig.yml');

$struct->testInit();
$struct->testPath('item');
$struct->testPath('item/sub-item');
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
?>
<div style="height:80px;"></div>
<?
//$struct->dumpArrayConfig();
//$struct->dumpYamlConfig();