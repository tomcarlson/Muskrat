<?php
/**
* Class for interacting with SQLITE and MySQL Databases
*
*/
 
class Muskrat {	   // Begin class Muskrat
	/* available for status and debug */
  var $lastquery_status;      // status of last operation
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
  	      $this->conn = mysql_connect($this->db_host, $this->db_user, $this->db_password) or die('Error connecting to mysql');
          mysql_select_db($this->db_name);       
          break;
        case 'sqlite':             
          //$this->conn = new SQLiteDatabase($this->db_name, 0666, $this->error);         
          // we must use sqlite3 in order to use the alter table to add fields
          $this->conn = new PDO('sqlite:'.$this->db_name);
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

  // primary_key will be an autoincrementing integer for our tables
  // when we create a table, we must include at least one field, so we choose
  // to specify the primary index field when we create the table.
  public function createTable($name,$primary_key) {  	
  	$q = @$this->conn->query("SELECT * FROM $name");
    if ($q === false) {
      switch ($this->db_type) {
        case 'mysql':         	  	  
  	      $this->conn->query("CREATE TABLE $name $primary_key INT NOT NULL AUTO_INCREMENT PRIMARY KEY");          
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
    	 $this->lastquery_status = $e->getMessage();	
    }  
    	 
  	
  }

  public function createRecord($table,$associative_array) {  	
  	$value_array = array();
  	$sql = "INSERT INTO $table(";   	
  	foreach ($associative_array as $key => $value)
  	{
  	  $sql .= $key . ',';
  	  $value_array[] = $value;
  	}
  	$sql = substr($sql,0,-1) . ')';  // remove last comma and close the parenthesis  	
  	$sql .= ' VALUES (';
  	foreach ($associative_array as $value)
  	{
  	  $sql .= "?,";
  	}  	
  	$sql = substr($sql,0,-1) . ');';  // remove last comma and close the parenthesis
  	
  	try {
      $stmt = $this->conn->prepare($sql);
      $stmt->execute($value_array); 
      $this->lastquery_status = "ok";    
    }
    catch (PDOException $e) {
    	 $this->lastquery_status = $e->getMessage();	
    }     
    
  } 
 
  public function readRecord($table,$where_array) { 
    $wherefield = implode(array_keys($where_array));
    $whereval = implode(array_values($where_array));

    $sql = "SELECT * FROM $table ";
    $sql .= " WHERE $wherefield = '$whereval'";
    
    
    try {
      $record = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
      $this->lastquery_status = "ok";    
        
    }
    catch (PDOException $e) {
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
    	 $this->lastquery_status = $e->getMessage();	
    }     
    
    $this->lastquery_results = count($record);	
  }
 
  
   
 // ------------------------ private functions ---------------------
 
 
}                  // End   class Muskrat
?>
