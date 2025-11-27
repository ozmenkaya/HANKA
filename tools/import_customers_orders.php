<?php
require_once __DIR__ . '/../homedir/public_html/include/db.php';

$file = __DIR__ . '/../import_data.sql';
if (!file_exists($file)) {
    die("File not found: $file\n");
}

$handle = fopen($file, "r");

// Disable strict mode to allow empty strings in ENUMs (or handle them gracefully)
try {
    $conn->exec("SET sql_mode = ''");
} catch (PDOException $e) {
    echo "Warning: Could not set sql_mode: " . $e->getMessage() . "\n";
}

$state = 'NONE';
$insertPrefix = '';
$insertedMusteri = 0;
$insertedSiparis = 0;
$skippedMusteri = 0;
$skippedSiparis = 0;

if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $trimLine = trim($line);
        
        if (strpos($line, 'INSERT INTO `musteri`') !== false) {
            $state = 'MUSTERI';
            $insertPrefix = $trimLine;
            // If the line contains values immediately (e.g. VALUES (1...), (2...))
            // We need to handle that. But based on inspection, it ends with VALUES
            continue;
        } elseif (strpos($line, 'INSERT INTO `siparisler`') !== false) {
            $state = 'SIPARISLER';
            $insertPrefix = $trimLine;
            continue;
        } elseif (strpos($line, 'INSERT INTO') !== false) {
            $state = 'NONE';
            continue;
        }

        if (($state == 'MUSTERI' || $state == 'SIPARISLER') && strpos($trimLine, '(') === 0) {
            // This is a value line: (1, ...), or (1, ...);
            
            // Extract ID
            // ID is the first number after (.
            // Regex: /^\(\s*(\d+)/
            if (preg_match('/^\(\s*(\d+)/', $trimLine, $matches)) {
                $id = $matches[1];
                $table = ($state == 'MUSTERI') ? 'musteri' : 'siparisler';
                
                // Check if exists
                $stmt = $conn->prepare("SELECT id FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                if (!$stmt->fetch()) {
                    // Does not exist, insert
                    // Clean the line: remove trailing comma or semicolon
                    $valuePart = rtrim($trimLine, ',;');
                    
                    // Construct query
                    $query = $insertPrefix . " " . $valuePart;
                    
                    try {
                        $conn->exec($query);
                        echo "Inserted $table ID: $id\n";
                        if ($table == 'musteri') $insertedMusteri++;
                        else $insertedSiparis++;
                    } catch (PDOException $e) {
                        echo "Error inserting $table ID $id: " . $e->getMessage() . "\n";
                    }
                } else {
                    // echo "Skipped $table ID: $id (Exists)\n";
                    if ($table == 'musteri') $skippedMusteri++;
                    else $skippedSiparis++;
                }
            }
        }
    }
    fclose($handle);
    
    echo "\nSummary:\n";
    echo "Musteri: Inserted $insertedMusteri, Skipped $skippedMusteri\n";
    echo "Siparisler: Inserted $insertedSiparis, Skipped $skippedSiparis\n";
} else {
    echo "Error opening file.\n";
}
?>
