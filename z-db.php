<?php
function connect_db( $db_name="" )
{
	global $myserv;
	$myserv = mysql_connect("localhost",MYSQL_USER,MYSQL_PASSWD);
	mysql_select_db($db_name==""?$_SESSION['db_name']:$db_name);
	mysql_set_charset('utf8');
}
function drop_db( $db_name )
{
	mysql_query("DROP DATABASE $db_name");	
}
function close_db()
{
	global $myserv;
	mysql_close($myserv);
}

function mysql_import_file($filename, &$errmsg) 
{ 
	$lines = file($filename); 
	if(!$lines){ 
		$errmsg = "cannot open file $filename"; 
		return false; 
	} 
	$scriptfile = false; 
	foreach($lines as $line){ 
		$line = trim($line); 
		if(!ereg('^--', $line)){ 
			$scriptfile.=" ".$line; 
		} 
	} 
	if(!$scriptfile){ 
      $errmsg = "no text found in $filename"; 
      return false; 
	} 
	$queries = explode(';', $scriptfile); 
	foreach($queries as $query){ 
		$query = trim($query); 
		if($query == "")
			continue;  
		if(!mysql_query($query.';')){ 
			$errmsg = "query ".$query." failed"; 
			return false; 
		} 
	}
	return true; 
} 
function create_db($dbname, $dbsqlfile, &$errmsg) 
{
	$result = true;
	if(!mysql_select_db($dbname)) 
	{ 
		$result = mysql_query("CREATE DATABASE $dbname"); 
		if(!$result) 
		{ 
			$errmsg = "could not create [$dbname] db in mysql"; 
			return false; 
		} 
		$result = mysql_select_db($dbname); 
	} 

	if(!$result) 
	{ 
		$errmsg = "could not select [$dbname] database in mysql";
		return false; 
	} 

	$result = mysql_import_file($dbsqlfile, $errmsg); 

	return $result; 
} 
?>
