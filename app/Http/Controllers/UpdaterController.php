<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

class UpdaterController extends Controller
{
    public function index()
    {
        $currentVersion = config('app.version');
        $updateServerUrl = config('app.update_server_url');

        $updateAvailable = false;
        $latestVersion = $currentVersion;
        $changelog = '';

        try {
            $response = Http::timeout(5)->get($updateServerUrl);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success'] && isset($data['data'])) {
                    $remoteVersion = $data['data']['version'];
                    $changelog = $data['data']['changelog'];
                    
                    if (version_compare($remoteVersion, $currentVersion, '>')) {
                        $updateAvailable = true;
                        $latestVersion = $remoteVersion;
                    }
                }
            }
        } catch (\Exception $e) {
            // Update server unreachable
            $changelog = "Could not reach the update server. " . $e->getMessage();
        }

        return view('dashboard.admin.updater.index', compact(
            'currentVersion', 
            'updateAvailable', 
            'latestVersion', 
            'changelog'
        ));
    }

    public function run(Request $request)
    {
        $updateServerUrl = config('app.update_server_url');
        
        try {
            $response = Http::get($updateServerUrl);
            if (!$response->successful()) {
                return back()->with('error', 'Update server is not reachable.');
            }
            
            $data = $response->json();
            if (!isset($data['data']['download_url']) || !isset($data['data']['version'])) {
                return back()->with('error', 'Invalid response from update server.');
            }
            
            $downloadUrl = $data['data']['download_url'];
            $newVersion = $data['data']['version'];
            
            // 1. Download ZIP
            $tempPath = storage_path('app/temp');
            if (!File::exists($tempPath)) {
                File::makeDirectory($tempPath, 0755, true);
            }
            
            $zipFile = $tempPath . '/update.zip';
            $fileData = Http::timeout(300)->get($downloadUrl)->body();
            File::put($zipFile, $fileData);
            
            // 2. Extract ZIP
            $zip = new ZipArchive;
            if ($zip->open($zipFile) === TRUE) {
                $extractPath = base_path();
                
                // Extract directly to base path
                // Note: ZipArchive::extractTo overwrites existing files.
                $zip->extractTo($extractPath);
                $zip->close();
                
                // Cleanup temp zip
                File::delete($zipFile);
                
                // 3. Run Migrations safely
                Artisan::call('migrate', ['--force' => true]);
                
                // 4. Clear Caches
                Artisan::call('optimize:clear');
                
                // 5. Update .env version
                $this->setEnvironmentValue('APP_VERSION', $newVersion);
                
                return back()->with('success', "Successfully updated to version $newVersion!");
            } else {
                return back()->with('error', 'Failed to open the downloaded update file.');
            }
            
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }
    
    private function setEnvironmentValue($envKey, $envValue)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        if (strpos($str, $envKey) !== false) {
            $str = preg_replace(
                "/^{$envKey}=.*/m",
                "{$envKey}={$envValue}",
                $str
            );
        } else {
            $str .= "\n{$envKey}={$envValue}\n";
        }

        file_put_contents($envFile, $str);
    }
}
