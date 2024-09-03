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
			// Ensure password_verify is used correctly
			if (password_verify($password, $user["Password"]) || $password === $user["Password"]) {
				unset($user["Password"]);  // Remove the password before returning the user data
				return $user;
			} else {
				echo "You are not logged in"; // Provide clear error message for invalid password
				error_log("LoginByID: Invalid password");
			}
		} else {
			error_log("LoginByID: User not found");
		}
		return false;
	}
}
