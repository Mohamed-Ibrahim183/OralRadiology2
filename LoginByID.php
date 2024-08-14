<?php

require_once('LoginStrategy.php');

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

        error_log("LoginByID: User fetched: " . json_encode($user));

        if ($user) {
            if ($user["Password"] === $password) {
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
