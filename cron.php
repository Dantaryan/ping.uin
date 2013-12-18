<?php

require_once './pinguin.php';
require_once './pingnotifyers.php';

$o = new pinguin();


// чтобы запретить жестко запретить какое-либо уведомление, достаточно просто закоментировать соотв. строку 
$o->attachObserver(new streamPingNotify());
$o->attachObserver(new jabberPingNotify());
$o->attachObserver(new smsPingNotify());
$o->attachObserver(new filelogPingNotify());

$o->run();
