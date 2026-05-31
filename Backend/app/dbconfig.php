<?php
// Read database configuration from environment variables (with defaults)
$type = getenv('DB_TYPE') ?: 'mysql';
$servername = getenv('DB_HOST') ?: 'mysql';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'secret123';
$database = getenv('DB_NAME') ?: 'developmentdb';
