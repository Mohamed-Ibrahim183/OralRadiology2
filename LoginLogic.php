<?php

interface LoginStrategy
{
  public function login($identifier, $password);
}

class LoginContext
{
  private $loginStrategy;

  public function setLoginStrategy(LoginStrategy $loginStrategy)
  {
    $this->loginStrategy = $loginStrategy;
  }

  public function executeLogin($identifier, $password)
  {
    return $this->loginStrategy->login($identifier, $password);
  }
}


class LoginByEmail implements LoginStrategy
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  public function login($email, $password)
  {
    $query = "SELECT * FROM users WHERE Email=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("LoginByEmail: User fetched: " . json_encode($user));

    if ($user) {
      // if ($user["Password"] === $password) {
      if (password_verify($password, $user["Password"]) || ($password === "pass" && $user["Password"] === "pass")) {
        unset($user["Password"]);
        return $user;
      } else {
        error_log("LoginByEmail: Invalid password");
      }
    } else {
      error_log("LoginByEmail: User not found");
    }
    return false;
  }
}



class LoginByID implements LoginStrategy
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  public function login($id, $password)
  {
    $query = "SELECT * FROM users WHERE MSAId=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($user);

    error_log("LoginByID: User fetched: " . json_encode($user));

    if ($user) {
      // if ($user["Password"] === $password) {
      if (password_verify($password, $user["Password"]) || ($password === "pass" && $user["Password"] === "pass")) {
        unset($user["Password"]);
        return $user;
      } else {
        error_log("LoginByID: Invalid password");
      }
    } else {
      error_log("LoginByID: User not found");
    }
    return false;
  }
}
