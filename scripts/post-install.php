<?php

declare(strict_types=1);

/**
 * Post-install script to download pre-built Piper libraries.
 * 
 * This script runs after composer install/update and downloads
 * the pre-built libraries from GitHub releases for the current architecture.
 */

$baseDir = __DIR__ . '/..';
$libsDir = $baseDir . '/libs';

// Detect system architecture
$arch = php_uname('m');
$os = strtolower(php_uname('s'));

// Map PHP architecture to our naming convention
$archMap = [
    'x86_64' => 'x86_64',
    'amd64' => 'x86_64',
    'aarch64' => 'aarch64',
    'arm64' => 'aarch64',
];

$archKey = $archMap[$arch] ?? $arch;

// Currently only Linux x86_64 is supported
if ($os !== 'linux' || $archKey !== 'x86_64') {
    echo "Warning: Pre-built libraries are only available for Linux x86_64.\n";
    echo "Your system: {$os} {$arch}\n";
    echo "Please build from source: make build-piper1\n";
    exit(0);
}

// Create libs directory if it doesn't exist
if (!is_dir($libsDir)) {
    mkdir($libsDir, 0755, true);
    echo "Created directory: {$libsDir}\n";
}

// GitHub release URL
$releaseUrl = 'https://github.com/crazy-goat/piper-php/releases/latest/download/';

$files = [
    "libpiper-linux-{$archKey}.tar.gz",
    "libonnxruntime-linux-{$archKey}.tar.gz",
    'espeak-ng-data.tar.gz',
];

$downloaded = false;

foreach ($files as $file) {
    $url = $releaseUrl . $file;
    $destPath = $libsDir . '/' . $file;
    
    // Skip if already exists
    if (file_exists($destPath)) {
        echo "Already exists: {$file}\n";
        continue;
    }
    
    echo "Downloading: {$file}...\n";
    
    // Download file
    $content = @file_get_contents($url);
    if ($content === false) {
        echo "Warning: Failed to download {$file}\n";
        continue;
    }
    
    // Save file
    if (file_put_contents($destPath, $content) === false) {
        echo "Warning: Failed to save {$file}\n";
        continue;
    }
    
    echo "Downloaded: {$file}\n";
    $downloaded = true;
}

// Extract archives if downloaded
if ($downloaded) {
    echo "\nExtracting archives...\n";
    
    foreach ($files as $file) {
        $archivePath = $libsDir . '/' . $file;
        
        if (!file_exists($archivePath)) {
            continue;
        }
        
        // Extract tar.gz
        $command = sprintf('tar -xzf %s -C %s 2>&1', escapeshellarg($archivePath), escapeshellarg($libsDir));
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "Extracted: {$file}\n";
            // Remove archive after extraction
            unlink($archivePath);
        } else {
            echo "Warning: Failed to extract {$file}\n";
        }
    }
}

echo "\nPost-install complete!\n";
echo "Libraries are available in: {$libsDir}\n";
echo "Architecture: {$os} {$arch}\n";
