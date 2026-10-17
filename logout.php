<?php
// "Not you? Sign out": forget this device and clear the saved details from the session.
require_once 'includes/shop.php';
forgetDevice(getDB());
session_destroy();
redirect(SITE_URL . '/index.php');
