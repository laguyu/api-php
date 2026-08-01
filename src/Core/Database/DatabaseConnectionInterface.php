<?php

namespace App\Core\Database;

use PDO;

interface DatabaseConnectionInterface
{
    public function getConnection(): PDO;
}
