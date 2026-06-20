<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallerTest extends TestCase
{

    private $envPath;
    private $envBackup;
    private $lockPath;
    private $lockBackup;

    protected function setUp(): void
    {
        parent::setUp();

        // Backup .env file
        $this->envPath = base_path('.env');
        $this->envBackup = null;
        if (file_exists($this->envPath)) {
            $this->envBackup = file_get_contents($this->envPath);
        }

        // Backup installed lock file
        $this->lockPath = storage_path('installed');
        $this->lockBackup = false;
        if (file_exists($this->lockPath)) {
            $this->lockBackup = true;
            unlink($this->lockPath);
        }
    }

    protected function tearDown(): void
    {
        // Restore .env file
        if ($this->envBackup !== null) {
            file_put_contents($this->envPath, $this->envBackup);
        } elseif (file_exists($this->envPath)) {
            unlink($this->envPath);
        }

        // Restore installed lock file
        if ($this->lockBackup) {
            file_put_contents($this->lockPath, date('Y-m-d H:i:s'));
        } elseif (file_exists($this->lockPath)) {
            unlink($this->lockPath);
        }

        parent::tearDown();
    }

    /**
     * Test redirection when not installed.
     */
    public function test_redirects_to_install_if_not_installed()
    {
        // Assert lock file does not exist
        $this->assertFalse(file_exists($this->lockPath));

        // Visit root or login with installer middleware header enabled
        $response = $this->withHeader('X-Test-Installer-Middleware', '1')
            ->get('/login');

        // Should redirect to install welcome
        $response->assertRedirect(route('install.welcome'));
    }

    /**
     * Test block installer access when installed.
     */
    public function test_blocks_installer_if_already_installed()
    {
        // Create mock lock file
        file_put_contents($this->lockPath, 'installed');

        // Visit installer welcome with installer middleware header
        $response = $this->withHeader('X-Test-Installer-Middleware', '1')
            ->get(route('install.welcome'));

        // Should redirect to login
        $response->assertRedirect(route('login'));
    }

    /**
     * Test welcome/requirements page loads.
     */
    public function test_welcome_page_loads()
    {
        $response = $this->get(route('install.welcome'));
        $response->assertStatus(200);
        $response->assertSee('Initialize Installation');
        $response->assertSee('Environment Check');
    }

    /**
     * Test database configuration view loads.
     */
    public function test_database_page_loads()
    {
        $response = $this->get(route('install.database'));
        $response->assertStatus(200);
        $response->assertSee('Database Connection');
        $response->assertSee('Database Port');
    }

    /**
     * Test database connection testing endpoint.
     */
    public function test_database_test_connection_failure()
    {
        $response = $this->postJson(route('install.database.test'), [
            'host' => 'invalid-host-name',
            'port' => '3306',
            'database' => 'invalid-db',
            'username' => 'invalid-user',
            'password' => 'invalid-pass',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /**
     * Test save database config to session.
     */
    public function test_saves_database_config_to_session()
    {
        $response = $this->post(route('install.database.save'), [
            'host' => '127.0.0.1',
            'port' => '3307',
            'database' => 'omnipay_test',
            'username' => 'root',
            'password' => 'secret',
        ]);

        $response->assertRedirect(route('install.admin'));
        $this->assertEquals([
            'host' => '127.0.0.1',
            'port' => '3307',
            'database' => 'omnipay_test',
            'username' => 'root',
            'password' => 'secret',
        ], session('install_db_config'));
    }

    /**
     * Test admin page blocks access if DB config is missing.
     */
    public function test_admin_page_blocks_access_without_db_config()
    {
        $response = $this->get(route('install.admin'));
        $response->assertRedirect(route('install.database'));
    }

    /**
     * Test admin page loads when DB config is present.
     */
    public function test_admin_page_loads_with_db_config()
    {
        session(['install_db_config' => [
            'host' => '127.0.0.1', 'port' => '3307', 'database' => 'test', 'username' => 'root'
        ]]);

        $response = $this->get(route('install.admin'));
        $response->assertStatus(200);
        $response->assertSee('Administrator Account');
    }

    /**
     * Test save admin setup to session.
     */
    public function test_saves_admin_setup_to_session()
    {
        $response = $this->post(route('install.admin.save'), [
            'admin_name' => 'Super Admin',
            'admin_email' => 'super@admin.com',
            'admin_password' => 'supersecure123',
        ]);

        $response->assertRedirect(route('install.run'));
        $this->assertEquals([
            'admin_name' => 'Super Admin',
            'admin_email' => 'super@admin.com',
            'admin_password' => 'supersecure123',
        ], session('install_admin_config'));
    }

    /**
     * Test full installation run execution.
     */
    public function test_run_install_execution()
    {
        // 1. Set up session values
        session([
            'install_db_config' => [
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'omnipay_test',
                'username' => 'root',
                'password' => '',
            ],
            'install_admin_config' => [
                'admin_name' => 'Setup Admin',
                'admin_email' => 'setup@admin.com',
                'admin_password' => 'newpassword123',
            ]
        ]);

        // 2. Call run action endpoint
        $response = $this->postJson(route('install.run-action'));

        // 3. Assert success response
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'OmniPay successfully installed!'
        ]);

        // 4. Assert lock file is written
        $this->assertTrue(file_exists($this->lockPath));

        // 5. Assert database records exist and admin email was updated correctly
        $this->assertDatabaseHas('users', [
            'email' => 'setup@admin.com',
            'role' => 'admin',
        ]);
    }
}
