<?php

require_once('DBChecker/DBChecker.php');

$dbChecker = new \DBChecker\DBChecker();

foreach ($dbChecker->run() as $error)
{
    echo $error;
}