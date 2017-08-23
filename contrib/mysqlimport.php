<?php
$dir              = 'sql';
$filename         = 'sql';
$dbHost           = getenv(strtoupper(getenv("WORDPRESS_DB_HOST"))."_SERVICE_HOST");
$dbUser           = getenv("WORDPRESS_DB_USER");
$dbPass           = getenv("WORDPRESS_DB_PASSWORD");
$dbName           = getenv("WORDPRESS_DB_NAME");
$maxRuntime       = 290; // less then your max script execution limit


$deadline         = time() + $maxRuntime; 
$progressFilename = $filename . '_filepointer'; // tmp file for progress
$errorFilename    = $filename . '_error'; // tmp file for erro
$filenames        = scandir('sql');
$link             = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName) OR die('connecting to host: ' . $dbHost . ' failed: ' . mysqli_error($link));

foreach ($filenames as $filename) {
  $file = $dir . '/' . $filename;
  if ($filename == '.' || $filename == '..' || is_dir($file)) {
      continue;
  }

  ($fp  = gzopen($file, 'r')) OR die('failed to open file:' . $file);

    // check for previous error
    if( file_exists($errorFilename) ){
        die('<pre> previous error: '.file_get_contents($errorFilename));
    }
    
    // go to previous file position
    $filePosition = 0;
    if( file_exists($progressFilename) ){
        $filePosition = file_get_contents($progressFilename);
        gzseek($fp, $filePosition);
    }
    
    $queryCount = 0;
    $query      = '';
    while( $deadline>time() AND ($line=gzgets($fp, 1024000)) ){
        if(substr($line,0,2)=='--' OR trim($line)=='' ){
            continue;
        }
    
        $query .= $line;
        if( substr(trim($query),-1)==';' ){
            if( ! mysqli_query($link, $query) ){
                $error = 'Error performing query \'<strong>' . $query . '\': ' . mysqli_error($link);
                file_put_contents($errorFilename, $error."\n");
                exit;
            }
            $query = '';
            file_put_contents($progressFilename, ftell($fp)); // save the current file position for 
            $queryCount++;
        }
    }
    
    if( gzeof($fp) ){
        echo 'dump successfully restored! ' . $queryCount . ' queries processed!';
    }else{
        echo gztell($fp).'/'.filesize($filename).' '.(round(gztell($fp)/(filesize($filename)+1), 2)*100).'%'."\n";
        echo $queryCount.' queries processed! please reload or wait for automatic browser refresh!';
    }
    gzclose($fp);
}
mysqli_close($link);
?>
