<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

class vars
{
  // add vas you want here

  // 1. Database Variables
  public $DbName, $dsn, $dbUserName, $dbPassword;


  public function __construct()
  {
    // set the values of the vars
    $this->DbName = "oralradiology";
    $this->dsn = "mysql:host=localhost;dbname=$this->DbName";
    $this->dbUserName = "root";
    $this->dbPassword = "";
  }
}
