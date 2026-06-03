Modo de uso de database.php (exemplo):

use Config\Database;
$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM internos LIMIT 10");
$internos = $stmt->fetchAll();