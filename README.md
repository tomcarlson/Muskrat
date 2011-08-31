# Should I use Muskrat?
No!  It is still in development and will break your project.

# What is Muskrat?

Muskrat is an easy to use database abstraction layer for MySql and SQLite databases.

# Using Muskrat

Let's link to a database.

    // sqlite...
    $db = new Muskrat();
    $db->setType('sqlite');
    $db->setName('/path/to/db.sqlite'); //db name
    $db->connect();

    // mysql...
    $db = new Muskrat();
    $db->setType('mysql');
    $db->setName('db'); //db name
    $db->setHost('localhost'); // db host
    $db->setUsername('username');
    $db->setPassword('****');
    $db->connect();
    
    

Now, we can create a `User` table in the db.  You must assign the field name of the 
primary key, which will be an autoincrementing integer.
    
    $db->createTable('User','usernumber');
    
Let's add some fields to the table 

    $db->addField('User','username','varchar(100) default NULL' );
    $db->addField('User','password','varchar(100) default NULL' );    
    $db->addField('User','join_date','integer' );    
    
    

Later, we may decide we wish to add another field to the table

    $db->addField('User','expire_date','integer' );    
    
     
The `User` table is now the way we want it. Now let's add a record to it.
    
    $db->createRecord('User', array(
      "username" => "joe_user",
      "password" => "pass1234"
     )
    );
    
Later, we decide to set some other fields in that record
 
    $db->updateRecord('User',
      array('username'=>'joe_user'),          // define how to select record
      array(
       "join_date" => time(),                 // update this field
       "expire_date" => time() + 15552000     // update this field
      )
    );
    
    
Now, let's read that record and print the expire date.

    $record = $db->readRecord('User',array('username'=>'joe_user'));
    if ($db->lastquery_results==1)
      echo date('F j, Y',$record['expire_date']);
    else
    {
    	echo "<pre>";
      print_r($record);  // there may be more than one record returned
    }
    

Finally, let's delete that record.

    $db->deleteRecord('User',array('username'=>'joe_user'));

We can also read a record set based on raw sql 

    $sql = "SELECT *";      
    $sql .= " FROM User ";
    $sql .= " WHERE Time > '1314835200' ";
    $sql .= " AND Volume > '1000' ";
    $data_array = $db->readRecordSQL($mover_tbl,$sql);   

At any time, we can obtain the status of our last operation

    if ($db->lastquery_status!='ok')
      $error = $db->lastquery_status;
    
    if ($db->lastquery_results==0)
      echo "There were no user's named 'jabowski'";
    else
      echo "Read " . $db->lastquery_results . " Twitter Records.";   
     

At any time, we can obtain a list of the tables in the database

    echo "<pre>";
    print_r($db->showTables());
    

# Other

## License

[The MIT License](http://www.opensource.org/licenses/mit-license.php)

