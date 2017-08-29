<?php
$dir              = 'sql';
$filename         = 'sql';
$dbHost           = getenv(strtoupper(getenv("WORDPRESS_DB_HOST"))."_SERVICE_HOST");
$dbUser           = getenv("WORDPRESS_DB_USER");
$dbPass           = getenv("WORDPRESS_DB_PASSWORD");
$dbName           = getenv("WORDPRESS_DB_NAME");
$timeout          = 3000000;
$deadline         = time() + $timeout; // less then your max script execution limit`
$filenames        = scandir($dir);
$mysql             = new mysqli($dbHost, $dbUser, $dbPass, $dbName) OR die('connecting to host: ' . $dbHost . ' failed: ' . mysqli_error($mysql));
$mysql->query("SET sql_mode = ''");

function save_progress() {
      global $mysql;
      global $filename;
      global $fp;
      $mysql->query("LOCK TABLES mysqlimport WRITE");
      $mysql->query("INSERT INTO mysqlimport(`what`,`done`) VALUES('" . $filename . "', " . gztell($fp) . ")");
      $mysql->query("UNLOCK TABLES");
      echo gztell($fp);
}

pcntl_signal(SIGINT, function ($sig) {
  echo 'mysqlimport exiting with signal: ' . $sig;
  global $filename;
  global $fp;
  global $mysql;
  if ($fp  ) gzclose($fp);
  if ($mysql) {
    try {
      echo 'Saving file postion ' . gztell($fp) . ".\n";
      $mysql->query("INSERT INTO mysqlimport(what,done) VALUES('" . $filename . "', " . gztell($fp) . ")");
      $mysql->close();
    } catch (Exception $e) { var_dump($e); }
  }
  exit(1);
});

$mysql->query("CREATE TABLE IF NOT EXISTS mysqlimport(`when` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `what` VARCHAR(255), `done` BIGINT UNSIGNED DEFAULT 0)");

foreach ($filenames as $filename) {
  $file = $dir . '/' . $filename;
  if ($filename == '.' || $filename == '..' || is_dir($file)) {
      continue;
  }
  echo "mysqlimport(" . $file . ");\n";

  ($fp = gzopen($file, 'r')) OR die('failed to open file:' . $file);

  //try resume from previous import
  try {
          echo 'looking for previous mysqlimport position ... ';
          if ($mysql_result = $mysql->query("SELECT `done` FROM mysqlimport WHERE `what` = '" . $filename . "' ORDER BY `when` DESC LIMIT 1")) {
                  if ($mysql_result->num_rows > 0) {
                          $row = $mysql_result->fetch_assoc();
                          $pos = $row["done"];
                          gzseek($fp, $pos);
                          echo 'resuming from position ' . $pos . ".\n";
                  }
                  else
                          echo 'not found. starting at 0.' . "\n";
          }
          else
                  echo 'not found. starting at 0.' . "\n";
  } catch(Exception $e) { var_dump($e); }

  $queryCount = 0;
  $query      = '';
  while( $deadline>time() AND ($line=gzgets($fp, 1024000)) ){
      if(substr($line,0,2)=='--' OR trim($line)=='' ){
          continue;
      }
  
      $query .= $line;
      if( substr(trim($query),-1)==';' ){
          if( ! $mysql->query($query) ){
              echo 'Warning: Error performing query \'' . $query . '\': ' . $mysql->error;
              //exit;
          }
          $query = '';
          $queryCount++;
          if ($queryCount % 100   == 0) echo '.';
          if ($queryCount % 10000 == 0) save_progress();
      }
  }
  
  if( gzeof($fp) ){
          echo "dump successfully restored!\n";
  }else{
          echo 'deadline (' . $timeout . "s) expired. dump partially restored!\n"
  }
  echo 'queries : ' . $queryCount                     . " queries\n" 
     . 'filesize: ' . save_progress()                 . " bytes  \n"
     . 'duration: ' . (time() - ($deadline-$timeout)) . " seconds\n";
  gzclose($fp);
}
$mysql->close();
?>
