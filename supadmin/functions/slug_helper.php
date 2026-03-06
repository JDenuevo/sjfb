<?php
// ==================== supadmin/functions/slug_helper.php ====================
// Manages blog slugs and blog-specific image uploads.
// NOTE: uploadImage() is declared in add.php for market/product uploads.
//       The blog-specific uploader here is named uploadBlogImage() to avoid
//       a fatal "Cannot redeclare uploadImage()" conflict.

function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

function getUniqueSlug($conn, $title, $table = 'blogs', $id = null) {
    $slug         = createSlug($title);
    $originalSlug = $slug;
    $counter      = 1;

    $query = "SELECT blog_id FROM $table WHERE blog_slug = '$slug'";
    if ($id) $query .= " AND blog_id != $id";
    $result = mysqli_query($conn, $query);

    while (mysqli_num_rows($result) > 0) {
        $slug   = $originalSlug . '-' . $counter;
        $query  = "SELECT blog_id FROM $table WHERE blog_slug = '$slug'";
        if ($id) $query .= " AND blog_id != $id";
        $result = mysqli_query($conn, $query);
        $counter++;
    }

    return $slug;
}

/**
 * Blog-specific image uploader.
 * Named uploadBlogImage() to avoid conflict with the uploadImage() helper
 * already declared in add.php (which handles market / product uploads).
 *
 * Returns an associative array:
 *   ['success' => bool, 'message' => string, 'file_path' => string]
 */
function uploadBlogImage($file, $uploadDir = '../uploads/blogs/') {
    $response = [
        'success'   => false,
        'message'   => '',
        'file_path' => '',
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
        ];
        $response['message'] = $uploadErrors[$file['error']] ?? 'Unknown upload error';
        return $response;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo        = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType     = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $response['message'] = 'Only JPG, PNG, WebP, and GIF images are allowed';
        return $response;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $response['message'] = 'File size must be less than 5MB';
        return $response;
    }

    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $response['message'] = 'Failed to create upload directory';
            return $response;
        }
    }

    $extension  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename   = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $response['success']   = true;
        $response['message']   = 'File uploaded successfully';
        $response['file_path'] = '/sjfbi-js/uploads/blogs/' . $filename;
    } else {
        $response['message'] = 'Failed to move uploaded file';
    }

    return $response;
}

function deleteImage($imagePath) {
    if (empty($imagePath)) return true;
    $filePath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}
?>