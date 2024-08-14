<?php

require_once('LoginStrategy.php');

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
            if ($user["Password"] === $password) {
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
