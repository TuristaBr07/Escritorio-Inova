<?php
require_once dirname(__DIR__) . '/includes/auth.php';
deslogar();
header('Location: login.php?msg=logout');
exit;
