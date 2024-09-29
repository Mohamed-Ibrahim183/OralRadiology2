<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
class Helpers
{
  private $pdo;
  public function __construct(PDO &$pdo)
  {
    $this->pdo = $pdo;
  }

  public function prepareAndBind(string $query, $params, bool $execute = false): PDOStatement
  {
    $keys = array_keys($params);
    $query .=  !empty($keys) ?  " (:" . implode(", :", $keys) . ");" : ";";
    $stmt = $this->pdo->prepare($query);
    $stmt = !empty($keys) ? $this->bindParams($params, $stmt) : $stmt;
    if ($execute) $stmt->execute();
    return $stmt;
  }
  public function bindParams(array $params, PDOStatement &$stmt, bool $execute = false): PDOStatement
  {
    foreach ($params as $key => $value)
      $stmt->bindValue($this->ensureColon($key), $value);
    if ($execute) $stmt->execute();
    return $stmt;
  }

  public function ensureColon($key): string
  {
    // Check if the key already starts with a colon
    return strpos($key, ':') === 0 ? $key : ':' . $key;
  }

  public function fetchOne(string $query, array $params = []): array
  {
    $stmt = $this->prepareAndBind($query, $params, true);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  }

  public function fetchAll(string $query, array $params = []): array
  {
    $stmt = $this->prepareAndBind($query, $params, true);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  public function execute(string $query, array $params = []): bool
  {
    $stmt = $this->prepareAndBind($query, $params);
    return $stmt->execute();
  }

  public function validData($data): bool
  {
    $ready = true;
    foreach ($data as $value) {
      if (trim($value) === "")
        $ready = false;
    }
    return $ready;
  }
}
