<?php

namespace Fhooe\WebDockDashboard;

use Latte\Engine;
use Latte\Loaders\FileLoader;
use PDO;
use PDOException;

/**
 * Dashboard class to display the dashboard and query server and php information.
 * @package Fhooe\WebDockDashboard
 */
class Dashboard
{
    /**
     * @var Engine The Latte template engine.
     */
    private Engine $templateEngine;

    /**
     * @var string The path to the webapp directory
     */
    private string $webappDirectory;

    /**
     * @var string The name of the default database.
     */
    private string $dbName;

    /**
     * @var string The name of the database user.
     */
    private string $dbUser;

    /**
     * @var string The password of the database user.
     */
    private string $dbPassword;

    /**
     * @var string The internal host of the database.
     */
    private string $dbHostInternal;

    /**
     * @var string The external host of the database.
     */
    private string $dbHostExternal;

    /**
     * @var string The internal port of the database.
     */
    private string $dbPortInternal;

    /**
     * @var string The external port of the database.
     */
    private string $dbPortExternal;

    /**
     * @var string The hostname of the web server.
     */
    private string $hostname;

    /**
     * @var string The HTTP port of the web server.
     */
    private string $webPortHttp;

    /**
     * @var string The HTTPS port of the web server.
     */
    private string $webPortHttps;

    /**
     * @var string The HTTP port of phpMyAdmin.
     */
    private string $pmaPortHttp;

    /**
     * @var string The HTTPS port of phpMyAdmin.
     */
    private string $pmaPortHttps;

    /**
     * Creates a new Dashboard instance.
     * @param string $webappDirectory The path to the webapp directory
     */
    public function __construct(string $webappDirectory = ".")
    {
        $this->templateEngine = $this->getTemplateEngine();
        $this->webappDirectory = $webappDirectory;

        $this->dbName = getenv("DB_NAME") ?: "default";
        $this->dbUser = getenv("DB_USER") ?: "dbuser";
        $this->dbPassword = getenv("DB_PASSWORD") ?: "geheim";
        $this->dbHostInternal = getenv("DB_HOST") ?: "db";
        $this->dbHostExternal = getenv("DB_HOST_EXTERNAL") ?: "localhost";
        $this->dbPortInternal = getenv("DB_PORT") ?: "3306";
        $this->dbPortExternal = getenv("DB_PORT_EXTERNAL") ?: "6033";
        $serverName = $_SERVER["SERVER_NAME"] ?? "localhost";
        $this->hostname = is_string($serverName) ? $serverName : "localhost";
        if (!$this->isFrankenPhp()) {
            $this->webPortHttp = getenv("WEB_PORT_HTTP") ?: "8080";
            $this->webPortHttps = getenv("WEB_PORT_HTTPS") ?: "7443";
        } else {
            $this->webPortHttp = getenv("FRANKENPHP_WEB_PORT_HTTP") ?: "8081";
            $this->webPortHttps = getenv("FRANKENPHP_WEB_PORT_HTTPS") ?: "7444";
        }
        $this->pmaPortHttp = getenv("PMA_PORT_HTTP") ?: "8082";
        $this->pmaPortHttps = getenv("PMA_PORT_HTTPS") ?: "7443";
    }

    /**
     * Displays the dashboard.
     */
    public function display(): void
    {
        $this->templateEngine->render("dashboard.latte", [
            "url" => $this->getUrl(),
            "directories" => $this->getWebappDirectories(),
            "containerDescriptions" => $this->getContainerDescriptions(),
            "currentWebContainerName" => $this->getCurrentWebContainerName(),
            "databaseParameters" => [
                "name" => $this->dbName,
                "user" => $this->dbUser,
                "password" => $this->dbPassword,
                "hostInternal" => $this->dbHostInternal,
                "portInternal" => $this->dbPortInternal,
                "hostExternal" => $this->dbHostExternal,
                "portExternal" => $this->dbPortExternal,
            ],
            "webserverParameters" => [
                "hostname" => $this->hostname,
                "portHttp" => $this->webPortHttp,
                "portHttps" => $this->webPortHttps,
            ],
            "pmaParameters" => [
                "portHttp" => $this->pmaPortHttp,
                "portHttps" => $this->pmaPortHttps,
            ],
            "webserverVersion" => $this->getServerVersion(),
            "phpVersion" => $this->getPhpVersion(),
            "debuggerVersion" => $this->getDebuggerVersion(),
            "databaseVersion" => $this->getDatabaseVersion(),
        ]);
    }

    /**
     * Returns the template engine.
     * @return Engine The Latte template engine.
     */
    private function getTemplateEngine(): Engine
    {
        $latte = new Engine();
        $latte->setLoader(new FileLoader(__DIR__ . "/../views"));
        $latte->setCacheDirectory(sys_get_temp_dir());
        return $latte;
    }

    /**
     * Scan a directory and return all entries. Filters out . and .. per default.
     * @param string $directory The directory to scan.
     * @param array<string> $filteredDirectories Directories to filter out (. and .. per default).
     * @return array<string> The entries of the directory.
     */
    private function scanDirectory(string $directory, array $filteredDirectories = ['.', '..']): array
    {
        $directoryContent = scandir($directory);
        $directories = [];
        foreach ($directoryContent as $item) {
            if (is_dir($item) && !in_array($item, $filteredDirectories)) {
                $directories[] = $item;
            }
        }
        natcasesort($directories);
        return $directories;
    }

    /**
     * Returns the server host.
     * @return string The server host.
     */
    private function getServerHost(): string
    {
        $host = $_SERVER["HTTP_HOST"] ?? "localhost";
        return is_string($host) ? $host : "localhost";
    }

    /**
     * Returns the server protocol.
     * @return string The server protocol.
     */
    private function getServerProtocol(): string
    {
        return $_SERVER["PROTOCOL"] = !empty($_SERVER["HTTPS"]) ? "https" : "http";
    }

    /**
     * Returns the URL of the server.
     * @return string The URL of the server.
     */
    private function getUrl(): string
    {
        $host = $this->getServerHost();
        $protocol = $this->getServerProtocol();
        return $protocol . "://" . $host;
    }

    /**
     * Returns all subdirectories in the webapp directory. Filters out some common unwanted directories.
     * @return array<string> All directories in the webapp directory.
     */
    private function getWebappDirectories(): array
    {
        return $this->scanDirectory($this->webappDirectory, [".", "..", "dashboard", ".idea", ".vscode"]);
    }

    /**
     * Returns whether the current request is served by FrankenPHP (true) or Apache (false).
     * @return bool True if FrankenPHP/Caddy, false if Apache.
     */
    public function isFrankenPhp(): bool
    {
        return !function_exists("apache_get_version");
    }

    /**
     * Returns the container name for the current web server (webapp or webapp-frankenphp).
     * @return string The container name.
     */
    public function getCurrentWebContainerName(): string
    {
        return $this->isFrankenPhp() ? "webapp-frankenphp" : "webapp";
    }

    /**
     * Returns a short description of the current web server for the dashboard sidebar.
     * @return string The description (e.g. "Apache web server with PHP." or "Caddy web server with FrankenPHP.").
     */
    public function getCurrentWebContainerDescription(): string
    {
        return $this->isFrankenPhp()
            ? "Caddy web server with FrankenPHP."
            : "Apache web server with PHP.";
    }

    /**
     * Returns the list of Docker container names and descriptions for the "Useful Information" sidebar.
     * The first entry (web server) depends on whether the dashboard is served by Apache or FrankenPHP.
     * @return array<int, array{name: string, description: string}> List of ["name" => string, "description" => string].
     */
    public function getContainerDescriptions(): array
    {
        return [
            [
                "name" => $this->getCurrentWebContainerName(),
                "description" => $this->getCurrentWebContainerDescription(),
            ],
            [
                "name" => "mariadb",
                "description" => "MariaDB database.",
            ],
            [
                "name" => "fhooe-web-dock-adminer-1",
                "description" => "Adminer for database management.",
            ],
        ];
    }

    /**
     * Returns the server version.
     * Supports Apache (apache_get_version) and FrankenPHP/Caddy.
     * @return string The server version.
     */
    private function getServerVersion(): string
    {
        if (function_exists('apache_get_version')) {
            $version = apache_get_version();
            return $version !== false ? $version : 'Apache (version unknown)';
        }
        // FrankenPHP: try to get version from binary (shell_exec may be disabled)
        if (function_exists('shell_exec')) {
            $version = trim((string)@shell_exec('frankenphp --version 2>/dev/null'));
            if ($version !== '' && preg_match(
                    '/FrankenPHP (v\d+\.\d+\.\d+).*?Caddy (v\d+\.\d+\.\d+)/',
                    $version,
                    $matches,
                )) {
                return "FrankenPHP $matches[1] using Caddy $matches[2]";
            }
            if ($version !== '') {
                return $version;
            }
        }
        $software = $_SERVER['SERVER_SOFTWARE'] ?? 'FrankenPHP';
        return is_string($software) ? $software : 'FrankenPHP';
    }

    /**
     * Returns the PHP version.
     * @return string The PHP version.
     */
    private function getPhpVersion(): string
    {
        return "PHP " . phpversion();
    }

    /**
     * Returns the debugger version.
     * @return string The debugger version.
     */
    private function getDebuggerVersion(): string
    {
        return "Xdebug " . phpversion("xdebug");
    }

    /**
     * Returns the database version.
     * @return string The database version.
     */
    private function getDatabaseVersion(): string
    {
        $charsetAttr = "SET NAMES utf8 COLLATE utf8_general_ci";
        $dsn = "mysql:host=" . $this->dbHostInternal . ";dbname=" . $this->dbName . ";port=" . $this->dbPortInternal;
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            Pdo\Mysql::ATTR_INIT_COMMAND => $charsetAttr,
            Pdo\Mysql::ATTR_MULTI_STATEMENTS => false,
        ];

        try {
            $pdo = new PDO($dsn, $this->dbUser, $this->dbPassword, $options);

            // Get the version of the database server
            $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $versionString = is_string($version) ? $version : '';

            // This results in something like "11.2.2-MariaDB-1:11.2.2+maria~ubu2204"
            // We only want the version number, so we use a regular expression to extract it
            if (preg_match('/\d+\.\d+\.\d+/', $versionString, $matches)) {
                $versionString = $matches[0];
            }

            return "MariaDB " . $versionString;
        } catch (PDOException) {
            return "MariaDB version not available. Check the connection to the database container.";
        }
    }
}
