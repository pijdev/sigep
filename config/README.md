# Configurações do SIGEP (Config) /config

Este diretório contém as classes de configuração do sistema, responsáveis por gerenciar a conexão com o banco de dados e as constantes globais do aplicativo.

## 🚀 Como Usar

### 1. Banco de Dados (`Database.php`)

Utilize a classe `Database` para obter a conexão PDO ativa e executar consultas SQL.

```php
use Config\Database;

// Obtém a conexão com o banco de dados
\$db = Database::getConnection();

// Executa a consulta
\(stmt =\)db->query("SELECT * FROM internos LIMIT 10");
\(internos =\)stmt->fetchAll();
```
### 2. Configurações do Aplicativo (`App.php`)

Utilize a classe `App` para acessar informações e constantes globais do sistema.

```php
use Config\App;

// Exibe o nome do aplicativo
echo App::APP_NAME;
```
## 🛠️ Requisitos

* PHP 8.0 ou superior
* Extensão PDO habilitada
* Autoload (Composer ou customizado) configurado para o namespace `Config`
