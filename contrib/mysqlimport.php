<?php
$dir              = 'sql';
$filename         = 'sql';
$dbHost           = getenv(strtoupper(getenv("WORDPRESS_DB_HOST"))."_SERVICE_HOST");
$dbUser           = getenv("WORDPRESS_DB_USER");
$dbPass           = getenv("WORDPRESS_DB_PASSWORD");
$dbName           = getenv("WORDPRESS_DB_NAME");
$maxRuntimr       = 290; // less then your max script execution limit


$deadline         = time() + $maxRuntime; 
$filenames        = scandir($dir);
$link             = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName) OR die('connecting to host: ' . $dbHost . ' failed: ' . mysqli_error($link));

foreach ($filenames as $filename) {
  $file = $dir . '/' . $filename;
  if ($filename == '.' || $filename == '..' || is_dir($file)) {
      continue;
  }

  ($fp = gzopen($file, 'r')) OR die('failed to open file:' . $file);

  $queryCount = 0;
  $query      = '';
  while( $deadline>time() AND ($line=gzgets($fp, 1024000)) ){
      if(substr($line,0,2)=='--' OR trim($line)=='' ){
          continue;
      }
  
      $query .= $line;
      if( substr(trim($query),-1)==';' ){
          if( ! mysqli_query($link, $query) ){
              echo 'Error performing query \'' . $query . '\': ' . @mysqli_error();
              exit;
          }
          $query = '';
          $queryCount++;
      }
  }
  
  if( gzeof($fp) ){
      echo 'dump successfully restored! ' . $queryCount . ' queries processed!';
  }else{
      @echo @gztell($fp) . '/' . @filesize($file) . ' ' . (round(@gztell($fp) / (@filesize($file)+1), 2)*100) . '%' . "\n";
      echo 'dump partially restored! ' . $queryCount . ' queries processed! please try again.';
  }
  gzclose($fp);
}
mysqli_close($link);
?>
