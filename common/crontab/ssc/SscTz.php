#!/usr/local/php/bin/php
<?php
include('../../tools/Tool_Common.php');
$rst = system("curl http://www.0898ssc.com/index.php?r=forum/index/tz");
echo "\r\n";

$logData = ['rst'=>$rst];
//Tool_Common::log('/WORK/LOG/'.date('Ymd').'/0898tz','INFO','0898投注记录curl', $logData);
