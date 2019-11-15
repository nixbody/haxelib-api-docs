<?php

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/SourceFile.php';
require_once __DIR__ . '/lib/ClassData.php';
require_once __DIR__ . '/lib/ClassField.php';
require_once __DIR__ . '/lib/ApiDoc.php';

session_start();
if (empty($_SESSION['data']) || isset($_GET['scan'])) {
	$_SESSION['data'] = new ApiDoc();
}
echo $_SESSION['data']->html();
