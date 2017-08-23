<?php
require_once("/opt/app-root/src/wp-config.php");
$servername = getenv(strtoupper(getenv("WORDPRESS_DB_HOST"))."_SERVICE_HOST");
$username   = getenv("WORDPRESS_DB_USER");
$password   = getenv("WORDPRESS_DB_PASSWORD");
$database   = getenv("WORDPRESS_DB_NAME");
$k8s_probe  = getenv("K8S_PROBE");

foreach (getallheaders() as $name => $value) {
    error_log("Header: $name: $value");
    if ($name == "X-K8S-PROBE") {
        $k8sHeader = $value;
    }
}

// Check for magic header
if ($k8sHeader != $k8s_probe) {
    error_log("Unauthorized health check, got: $k8sHeader expected: $k8s_probe");
    header("HTTP/1.1 403 Forbidden");
    die("Unauthorized health check");
}
@$link = mysqli_connect($servername, $username, $password, $database);
if ( ! $link ) {
    header("HTTP/1.1 503 Service Unavailable");
    error_log(sprintf("Could not connect to the MySQL server: %s", @mysqli_error($link)));
    die( sprintf( "Could not connect to the MySQL server: %s\n", @mysqli_error($link) ) );
}
echo "OK";
?>
