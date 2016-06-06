<?php

require_once 'database.php'; //php file containing the db authentication info
/*
*         Functions:
** get_db_connection()------------------------ returns msqli object connected to db specified in database.php
** query_db($query) -------------------------- executes the query in $query and returns the result object
** select_row_query($query)------------------- Executes the given SELECT QUERY and returns the rows
** select_rows($table,$cols_to_select,$where)- Constructs the SELECT query and executes it using select_row_query()
** update_row($table,$id,$data)--------------- UPDATEs the data in $data, which should be an assoc array of (col_name => value), to the row with id $id
** insert_row($table,$data)------------------- INSERTs the data in $data of the form (col_name => value) into the table named $table
** delete_rows($table,$where)----------------- DELETEs rows matching the WHERE contition
** delete_row($table,$col,$val=NULL)---------- DELETEs the row in $table WHERE $col = $val. if $val is NULL then $col will the taken as the primary key(id)
** create_table($table_name,$columns)--------- CREATEs a table named $table with $columns of the form (column name => datatype)
** delete_table($table_name)------------------ DROPs the table named $table_name
** get_last_row($table_name)------------------ returns the last row entry in the table $table_name
** db_identifier_escape($string)-------------- removes all special chars except underscore and replaces spaces with '_'
** create_table_from_assoc_array($struct)----- CREATEs a table from a php assoc array. (See function def for details)
** create_table_from_json($json)-------------- CREATEs a table from  JSON. (See function def for details)
*
*
*/








function get_db_connection(){
  // returns mysqli object

  global $db_name;
  global $db_user;
  global $db_pass;
  global $db_hostname;

  $conn = new mysqli($db_hostname,$db_user,$db_pass,$db_name);

  if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
  }
  return $conn;
}


function query_db($query){
  // execute query
  $conn = get_db_connection();

  $res = mysqli_query($conn,$query);

  mysqli_close($conn);
  return $res;
}


function select_row_query($query)
{
  $res = query_db($query);
  $rows = $res->fetch_all(MYSQLI_BOTH);
  return $rows;
}

function select_rows($table,$cols_to_select,$where){

  // return assoc or numbered array of rows from $table of columns $cols_to_select
  $query = "SELECT ".implode($cols_to_select,',')." FROM ".$table." WHERE ".$where[0].$where[1].$where[2];
  $rows = select_row_query($query);

  return $rows;
}

function update_row($table,$id,$data){
  // $table -> table name and $data -> assoc array of data
  $query = "UPDATE ".$table." SET ";
  foreach ($data as $key => $value) {
    $query .= $key."='".$value."' ";
  }

  $query.= " WHERE id=".$id.";";

  $res = query_db($query);
}

function insert_row($table,$data){
  // insert ro $table => table name $data => assoc array of cols and vals
  $query = "INSERT INTO ".$table." (";
  $keys = array_keys($data);
  $arr_len = count($keys);
  $query .= implode(',',$keys);
  $query .= ") VALUES (";
  for($x = 0;$x < $arr_len;$x++){
    $query .= "'".$data[$keys[$x]]."',";
  }
  $query = substr($query,0,-1).")";

  $res = query_db($query);
}

function delete_rows($table,$where){
  // deletes rows accoring to the WHERE condition
  $query = "DELETE FROM ".$table." WHERE ".implode($where.' ');
  $res = query_db($query);
}

function delete_row($table,$col,$val=NULL){
  // deletes a row

  if($val){
    delete_rows($table,array($col,"=",$val));
  }else{
    delete_rows($table,array("id","=",$col));
  }
  $res = query_db($query);
}

function create_table($table_name,$columns)
{
  /*
  Creates a table with $table_name as table name and assoc array $columns with key as column name and val as datatype
  */
  $query = "CREATE TABLE ".$table_name."(id int NOT NULL AUTO_INCREMENT,";
  foreach ($columns as $col_name => $type) {
    $query.= db_identifier_escape($col_name)." ".$type.",";
  }

  $query .= "PRIMARY KEY(id))";

  $res = query_db($query);
}

function delete_table($table_name){
  // deletes the table named $table_name from the db
  $query = "DROP TABLE ".$table_name;
  $res = query_db($query);
}

function add_column($table,$col_name,$col_type){
  // adds a column named $col_name to table named $table of type $col_type
  $query = "ALTER TABLE ".$table." ADD ".$col_name." ".$col_type;
  $res = query_db($query);
}

function delete_column($table,$col_name){
  // deletes column named $col_name from table named $table
  $query = "ALTER TABLE ".$table." DROP COLUMN ".$col_name;
  $res = query_db($query);

}

function get_last_row($table_name){
  // returns the last row entry in the table $table_name
  $query = "SELECT * FROM ".$table_name." ORDER BY id DESC LIMIT 1";
  $rows = select_row_query($query);
  if($rows){
    return $rows[0];
  }else{
    return NULL;
  }
}


function db_identifier_escape($string){
  // removes all special chars except underscore and replaces spaces with '_'
  $name = preg_replace('/[^a-zA-Z0-9_\s]/','',$string);
  $name = preg_replace('/\s+/','_',$name);
  return $name;
}

function create_table_from_assoc_array($struct){
  /* CREATEs a table from a php assoc array of the form
    array(
      "table_name_1" => array(
          "col_1" => "type_of_col_1",
          "col_2" => "type_of_col_2",
          "col_3" => "type_of_col_3"
      ),
      "table_name_2" => array(
          "col_1" => "type_of_col_1",
          "col_2" => "type_of_col_2",
          "col_3" => "type_of_col_3"
      )
      .
      .
      .

    )
  */
  foreach ($struct as $table_name => $table_cols) {
    create_table($new_table_name,$table_cols);

  }

}


function create_table_from_json($json){
  /* CREATEs a table from  JSON  of the form
    {
      "table_name_1" : {
          "col_1" : "type_of_col_1",
          "col_2" : "type_of_col_2",
          "col_3" : "type_of_col_3"
      },
      "table_name_2" : {
          "col_1" : "type_of_col_1",
          "col_2" : "type_of_col_2",
          "col_3" : "type_of_col_3"
      }
      .
      .
      .
    }
  */
  $struct = json_decode($json,true); // returns parsed json as assoc arrays
  create_table_from_assoc_array($struct);

}
?>
