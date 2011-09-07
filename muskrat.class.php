<?php
/**
* Class for interacting with SQLITE and MySQL Databases
*
*/
 
class Muskrat {	   // Begin class Muskrat
	/* available for status and debug */
  var $lastquery_status;      // status of last operation
  var $errorInfo;             // extended error information associated with the last operation
  var $lastquery_results;     // number of results of last operation  

  
  /* May be set externally with setter functions */ 
  var $db_type;             /* sqlite or mysql */  
  var $db_name;             /* name of database if mysql, path to and name of database file if sqlite */
  var $db_host;             /* host of mysql database */
  var $db_username;         /* username of mysql database */
  var $db_password;         /* password of mysql database */
 
  
  
  /* private to the class */
  var $conn;  // must user global declaration in every function that variable appears in
  

  function __construct() {   
  }

  // Set if we're talking to mySQL or sqlite
  public function setType($str) {
    $this->db_type = $str;
  }
  
  public function setName($str) {
    $this->db_name = $str;
  }
  
  public function setHost($str) {
    $this->db_host = $str;
  }  

  public function setUsername($str) {
    $this->db_username = $str;
  }

  public function setPassword($str) {
    $this->db_password = $str;
  }
    
    
  public function connect() {
    if (!$this->conn)
  	{ 
      switch ($this->db_type) {
        case 'mysql':        
          try {
                              
          	$connect_string = "mysql:host=".$this->db_host.";dbname=".$this->db_name;
            $this->conn = new PDO($connect_string, $this->db_username, $this->db_password);
            $this->lastquery_status = "ok";    
      
             // make sure errorInfo() flags bad sql statements
             $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);                 
          } 
          catch (PDOException $e) {
          	$this->conn = NULL;
            $this->lastquery_status = $e->getMessage();	
          }     
          break;
        case 'sqlite':             
          //$this->conn = new SQLiteDatabase($this->db_name, 0666, $this->error);         
          // we must use sqlite3 in order to use the alter table to add fields
          try {
            $this->conn = new PDO('sqlite:'.$this->db_name); 
            
            // make sure we have write permission for this
            chmod($this->db_name, octdec(775));  
            
            $this->lastquery_status = "ok";              
          } 
          catch (PDOException $e) {
          	$this->conn = NULL;
            $this->lastquery_status = $e->getMessage();	
          }
          
          break; 
        default:
          die('Type must be mysql or sqlite');
      }
      
     
    }
  } 
  
  
  public function disconnect() {  
    if ($this->conn)
  	{ 
  		switch ($this->db_type) {
        case 'mysql': 	 
  	      mysql_close($this->conn);          
          break;
        case 'sqlite':  
          sqlite_close($this->conn);          
          break;
        default:
          die('Type must be mysql or sqlite');
      }
      $this->conn = NULL;
    }   
  }

  // returns 1 if table exists, returns 0 if table does not exist
  public function tableExists($name) {
  
  
    if ($this->db_type == 'sqlite')    
  	  $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='$name';";  
  	else    
      $sql = "show tables like '$name'; ";    
    
    try {
      $record = $this->conn->query($sql)->fetchAll(PDO::FETCH_NUM); //->fetchAll(PDO::FETCH_ASSOC);
      $this->lastquery_status = "ok";         
    }
    catch (PDOException $e) {
    	$this->errorInfo = $this->conn->errorInfo();  	
    	$this->lastquery_status = $e->getMessage();	
    }
    
    return(count($record));
  }
  
  
  public function dropTable($name) {  	
 // 	$q = @$this->conn->query("SELECT * FROM $name");
 //   if ($q === true) 
      $this->conn->query("DROP TABLE $name");     
    	
  }
  
  // returns an array of table names  
  public function showTables() {
  	$table = array();
  	
  	if ($this->db_type == 'sqlite')    
  	  $sql = "select tbl_name from sqlite_master;";  
  	else    
      $sql = "show tables;";    
    
    try {
      $record = $this->conn->query($sql)->fetchAll(PDO::FETCH_NUM); //->fetchAll(PDO::FETCH_ASSOC);
      $this->lastquery_status = "ok";         
    }
    catch (PDOException $e) {
    	$this->errorInfo = $this->conn->errorInfo();  	
    	$this->lastquery_status = $e->getMessage();	
    }
    
    $this->lastquery_results = count($record);
    
    foreach ($record as $value)
      $table[] = $value[0];
    
    if ($this->lastquery_results==1)
      return $table[0];   // dereference from 0, so their code looks prettier
    else
      return $table;  	
    
  }
  
  // primary_key will be an autoincrementing integer for our tables
  // when we create a table, we must include at least one field, so we choose
  // to specify the primary index field when we create the table.
  public function createTable($name,$primary_key) {  	
  	$q = @$this->conn->query("SELECT * FROM $name");
    if ($q === false) {
      switch ($this->db_type) {
        case 'mysql':          	  	  
  	      $this->conn->query("CREATE TABLE $name ($primary_key INT NOT NULL AUTO_INCREMENT PRIMARY KEY)");          
          break;
        case 'sqlite':             
          $this->conn->query("CREATE TABLE $name ($primary_key INTEGER, PRIMARY KEY($primary_key))");
          break; 
        default:
          die('Type must be mysql or sqlite');
      }
    }	
  }
  
  public function addField($table,$fieldname,$fielddef) {  	
  	$sql = "ALTER TABLE $table ADD COLUMN $fieldname $fielddef;"; 
  	try {
      $q = $this->conn->query($sql); 
      $this->lastquery_status = "ok";    
    }
    catch (PDOException $e) {
    	$this->errorInfo = $this->conn->errorInfo();  	
    	$this->lastquery_status = $e->getMessage();	
    }  
    	 
  	
  }

  public function createRecord($table,$associative_array) {  	
  	$value_array = array();
  	$sql = "INSERT INTO $table (";   	
  	foreach ($associative_array as $key => $value)
  	{
  	  $sql .= $key . ', ';
  	  $value_array[] = $value;
  	}
  	$sql = substr($sql,0,-2) . ')';  // remove last comma-space and close the parenthesis  	
  	$sql .= ' values (';
  	foreach ($associative_array as $value)
  	{
  	  $sql .= "?, ";
  	}  	
  	$sql = substr($sql,0,-2) . ');';  // remove last comma-space and close the parenthesis
  	 	
  	try {
  		
      $stmt = $this->conn->prepare($sql);
      if (!$stmt)
      {       
      	$this->errorInfo = $this->conn->errorInfo();  	
        $this->lastquery_status = $this->errorInfo[2];	
      }
      else
      {
        $stmt->execute($value_array); 
        $this->lastquery_status = "ok";    
      }
    }
    catch (PDOException $e) 
    {
    	$this->errorInfo = $this->conn->errorInfo();  	
    	$this->lastquery_status = $e->getMessage();	
    }     
    
  } 
 
 
  // returns records determined by the raw SQL that you provide 
  public function readRecordSQL($table,$sql) { 
   
    try {
      $record = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
      $this->lastquery_status = "ok";    
        
    }
    catch (PDOException $e) {
    	$this->errorInfo = $this->conn->errorInfo();  	
    	$this->lastquery_status = $e->getMessage();	
    }
    
    $this->lastquery_results = count($record);
    

    if ($this->lastquery_results==1)
      return $record[0];   // dereference from 0, so their code looks prettier
    else
      return $record;  
   
  }
  
  // returns records matching the where_array
  // if $order_by specified, orders those records in descending order, by $order_by COLUMN
  // if $number_records is specified, returns latest X records that match
  public function readRecord($table,$where_array,$order_by='',$number_records=0) { 
    $wherefield = implode(array_keys($where_array));
    $whereval = implode(array_values($where_array));

    $sql = "SELECT *";      
    $sql .= " FROM $table ";
    
    if (strlen($wherefield)>0)
    $sql .= " WHERE $wherefield = '$whereval' ";
    
    if ($order_by)
      $sql .= "ORDER BY $order_by DESC";    
    
     if ($number_records)
       $sql .= " LIMIT $number_records";
      
    
    try {
      $record = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
      $this->lastquery_status = "ok";    
        
    }
    catch (PDOException $e) {
    	$this->errorInfo = $this->conn->errorInfo();  	
    	$this->lastquery_status = $e->getMessage();	
    }
    
    $this->lastquery_results = count($record);
    

    if ($this->lastquery_results==1)
      return $record[0];   // dereference from 0, so their code looks prettier
    else
      return $record;  
   
  }
    
  public function updateRecord($table,$where_array,$data_array) {  	
    $fields = array_keys($data_array);
    $data = array_values($data_array);
    $fieldcount = count($fields); 

    $wherefield = implode(array_keys($where_array));
    $whereval = implode(array_values($where_array));

    $sql = "UPDATE $table SET ";
    $sql .= implode($fields, ' = ?, ') . ' = ?';
    $sql .= " WHERE $wherefield = '$whereval'";
        
    try {
      $stmt = $this->conn->prepare($sql);
      $stmt->execute($data);   
      $this->lastquery_status = "ok";    
    }
    catch (PDOException $e) {
    	$this->errorInfo = $this->conn->errorInfo();  	
    	$this->lastquery_status = $e->getMessage();	
    }  
        
  }  
 
  public function deleteRecord($table,$where_array) {
    $wherefield = implode(array_keys($where_array));
    $whereval = implode(array_values($where_array));

    $sql = "DELETE FROM $table ";
    $sql .= " WHERE $wherefield = '$whereval'";
    
    try {
      $record = $this->conn->exec($sql);   
      $this->lastquery_status = "ok";        
    }
    catch (PDOException $e) {
    	 $this->errorInfo = $this->conn->errorInfo();  	
    	 $this->lastquery_status = $e->getMessage();	
    }     
    
    $this->lastquery_results = count($record);	
  }
 
  
   
 // ------------------------ private functions ---------------------
 
 
}                  // End   class Muskrat
?>
