<?php
ob_start(); // Start output buffering

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if a file is uploaded
    if (isset($_FILES['vcard']) && $_FILES['vcard']['error'] == UPLOAD_ERR_OK) {
        // Get details of the uploaded file
        $fileTmpPath = $_FILES['vcard']['tmp_name'];
        $fileName = $_FILES['vcard']['name'];
        $fileSize = $_FILES['vcard']['size'];
        $fileType = $_FILES['vcard']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Define allowed file extensions
        $allowedfileExtensions = array('txt', 'vcf');

        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory in which the uploaded file will be moved
            $uploadFileDir = './uploaded_files/';
            $dest_path = $uploadFileDir . $fileName;

            // Create the directory if it does not exist
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Output file for vCards
                $outputFile = 'contacts.vcf';
                $outputHandle = fopen($outputFile, 'w');

                // Function to create vCard
                function create_vcard($name, $phone) {
                    return "BEGIN:VCARD\nVERSION:3.0\nFN:$name\nTEL:$phone\nEND:VCARD\n";
                }

                // Process the uploaded file
                $fileContent = file($dest_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                // Process each line in the uploaded file
                foreach ($fileContent as $line) {
                    $lineParts = explode(',', $line);
                    if (count($lineParts) == 2) {
                        $name = trim($lineParts[0]);
                        $phone = trim($lineParts[1]);
                        $vcard = create_vcard($name, $phone);
                        fwrite($outputHandle, $vcard);
                    } else {
                        echo "<div style='color: white;'>Skipping invalid line: $line<br></div>";
                    }
                }

                fclose($outputHandle);

                // Force download of the generated vCard file
                if (file_exists($outputFile)) {
                    // Clear any previously echoed output
                    ob_clean();
                    
                    // Send headers for file download
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($outputFile) . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($outputFile));

                    // Send the file content
                    readfile($outputFile);
                    exit;
                } else {
                    echo "<div style='color: white;'>Error: vCard file not found.</div>";
                }
            } else {
                echo "<div style='color: white;'>There was an error moving the uploaded file.</div>";
            }
        } else {
            echo "<div style='color: white;'>Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions) . "</div>";
        }
    } else {
        echo "<div style='color: white;'>There was an error with the file upload.</div>";
    }

    // Redirect to index.html after processing
    header("Location: index.html");
    exit;
} else {
    echo "<div style='color: white;'>Invalid request method.</div>";
}

ob_end_flush(); // End output buffering and flush output
?>
