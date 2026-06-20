<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use Exception;

class InstallController extends Controller
{
    /**
     * Step 1: Welcome & Requirements Check
     */
    public function welcome()
    {
        $requirements = $this->checkRequirements();
        return view('install.welcome', compact('requirements'));
    }

    /**
     * Step 2: Database Configuration Form
     */
    public function database()
    {
        $requirements = $this->checkRequirements();
        if (!$requirements['all_passed']) {
            return redirect()->route('install.welcome')->with('error', 'Please resolve all system requirements first.');
        }

        // Pre-populate with current env values if available
        $defaults = [
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'omnipay'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ];

        return view('install.database', compact('defaults'));
    }

    /**
     * POST handler to save database configuration to session
     */
    public function saveDatabase(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|string',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        session(['install_db_config' => $request->only(['host', 'port', 'database', 'username', 'password'])]);

        return redirect()->route('install.admin');
    }

    /**
     * AJAX endpoint to test database connection with override port.
     */
    public function testDb(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|string',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        $host = $request->input('host');
        $port = $request->input('port');
        $dbname = $request->input('database');
        $username = $request->input('username');
        $password = $request->input('password') ?? '';

        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            
            // Try to connect to MySQL server first (without database to verify if server exists)
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 4,
            ];
            $pdo = new PDO($dsn, $username, $password, $options);
            
            // Check if database exists, if not, try to create it automatically!
            $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($dbname));
            $exists = $stmt->fetch();
            
            if (!$exists) {
                $pdo->exec("CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully connected to database! The database has been prepared.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection Failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Step 3: Admin Configuration Form
     */
    public function admin()
    {
        // Redirect back to DB step if DB session configs are missing
        if (!session()->has('install_db_config')) {
            return redirect()->route('install.database')->with('error', 'Please configure the database connection first.');
        }

        return view('install.admin');
    }

    /**
     * POST handler to save admin configuration to session
     */
    public function saveAdmin(Request $request)
    {
        $request->validate([
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:6',
        ]);

        session(['install_admin_config' => $request->only(['admin_name', 'admin_email', 'admin_password'])]);

        return redirect()->route('install.run');
    }

    /**
     * Step 4: Installation Progress Screen
     */
    public function run()
    {
        // Verify session data is present
        if (!session()->has('install_db_config') || !session()->has('install_admin_config')) {
            return redirect()->route('install.database')->with('error', 'Session timeout or configuration missing. Please restart.');
        }

        return view('install.run');
    }

    /**
     * AJAX action endpoint to run the actual install process steps.
     */
    public function runInstall()
    {
        $dbConfig = session('install_db_config');
        $adminConfig = session('install_admin_config');

        if (!$dbConfig || !$adminConfig) {
            return response()->json(['success' => false, 'message' => 'Installation config is missing. Please restart.'], 400);
        }

        try {
            // 1. Prepare/Update .env file
            $this->updateEnv($dbConfig);

            // 2. Clear config caches to ensure new env database connection is loaded
            try {
                Artisan::call('config:clear');
            } catch (Exception $e) {
                // Ignore config clear failure
            }

            try {
                config(['cache.default' => 'file']);
                Artisan::call('cache:clear');
            } catch (Exception $e) {
                // Ignore cache clear failure
            }

            // 3. Re-configure database connection on runtime to ensure subsequent queries use the correct DB
            config([
                'database.connections.mysql.host' => $dbConfig['host'],
                'database.connections.mysql.port' => $dbConfig['port'],
                'database.connections.mysql.database' => $dbConfig['database'],
                'database.connections.mysql.username' => $dbConfig['username'],
                'database.connections.mysql.password' => $dbConfig['password'] ?? '',
            ]);
            DB::purge('mysql');
            DB::reconnect('mysql');

            // 4. Run database migrations
            Artisan::call('migrate:fresh', ['--force' => true]);

            // 5. Run database seeds
            Artisan::call('db:seed', ['--force' => true]);

            // 6. Update or Create Admin User
            $adminUser = \App\Models\User::where('role', 'admin')->first();
            if ($adminUser) {
                $adminUser->update([
                    'name' => $adminConfig['admin_name'],
                    'email' => $adminConfig['admin_email'],
                    'password' => Hash::make($adminConfig['admin_password']),
                ]);
            } else {
                \App\Models\User::create([
                    'name' => $adminConfig['admin_name'],
                    'email' => $adminConfig['admin_email'],
                    'password' => Hash::make($adminConfig['admin_password']),
                    'role' => 'admin',
                ]);
            }

            // 7. Write lock file
            file_put_contents(storage_path('installed'), date('Y-m-d H:i:s'));

            // 8. Clean sessions
            session()->forget(['install_db_config', 'install_admin_config']);

            return response()->json([
                'success' => true,
                'message' => 'OmniPay successfully installed!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Step 5: Complete Screen
     */
    public function complete()
    {
        return view('install.complete');
    }

    /**
     * Check php requirements & write permissions
     */
    private function checkRequirements()
    {
        $requirements = [
            'php' => [
                'name' => 'PHP Version (>= 8.2)',
                'supported' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
            ],
            'extensions' => [],
            'directories' => [],
            'all_passed' => true,
        ];

        // Core Extensions Check
        $extensions = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $requirements['extensions'][$ext] = [
                'name' => $ext,
                'supported' => $loaded,
            ];
            if (!$loaded) {
                $requirements['all_passed'] = false;
            }
        }

        if (!$requirements['php']['supported']) {
            $requirements['all_passed'] = false;
        }

        // Directories Permissions Check
        $directories = [
            'storage' => storage_path(),
            'storage/app' => storage_path('app'),
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        // Also check if .env is writable or if root directory is writable to create .env
        $envPath = base_path('.env');
        $envWritable = file_exists($envPath) ? is_writable($envPath) : is_writable(base_path());
        $requirements['directories']['.env'] = [
            'name' => '.env file',
            'path' => $envPath,
            'supported' => $envWritable,
        ];
        if (!$envWritable) {
            $requirements['all_passed'] = false;
        }

        foreach ($directories as $name => $path) {
            // Create directories if they don't exist
            if (!file_exists($path)) {
                @mkdir($path, 0775, true);
            }
            
            $writable = is_writable($path);
            $requirements['directories'][$name] = [
                'name' => $name,
                'path' => $path,
                'supported' => $writable,
            ];
            if (!$writable) {
                $requirements['all_passed'] = false;
            }
        }

        return $requirements;
    }

    /**
     * Safely update environment configurations in .env
     */
    private function updateEnv(array $dbConfig)
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                file_put_contents($envPath, '');
            }
        }

        $envContent = file_get_contents($envPath);

        // Standard database configs
        $replacements = [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $dbConfig['host'],
            'DB_PORT' => $dbConfig['port'],
            'DB_DATABASE' => $dbConfig['database'],
            'DB_USERNAME' => $dbConfig['username'],
            'DB_PASSWORD' => $dbConfig['password'] ?? '',
        ];

        // Generate APP_KEY if empty or not defined
        if (!preg_match('/^APP_KEY=base64:[a-zA-Z0-9\+\/=]+$/m', $envContent) && !preg_match('/^APP_KEY=[a-zA-Z0-9]{32}$/m', $envContent)) {
            $replacements['APP_KEY'] = 'base64:' . base64_encode(Str::random(32));
        }

        foreach ($replacements as $key => $value) {
            // Check if key exists in env file
            if (preg_match("/^{$key}=(.*)$/m", $envContent)) {
                // Handle passwords that may contain special characters or spaces by wrapping in quotes
                $safeVal = (str_contains($value, ' ') || preg_match('/[^a-zA-Z0-9_\-\.\/]/', $value)) ? '"' . str_replace('"', '\"', $value) . '"' : $value;
                $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$safeVal}", $envContent);
            } else {
                $safeVal = (str_contains($value, ' ') || preg_match('/[^a-zA-Z0-9_\-\.\/]/', $value)) ? '"' . str_replace('"', '\"', $value) . '"' : $value;
                $envContent .= "\n{$key}={$safeVal}";
            }
        }

        file_put_contents($envPath, trim($envContent) . "\n");
    }
}
