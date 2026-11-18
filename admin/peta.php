<?php
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');
header('Location: ../warga/peta.php');
exit;