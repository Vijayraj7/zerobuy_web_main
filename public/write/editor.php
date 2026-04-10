<?php

declare(strict_types=1);

/*
 * Simple PHP file editor for this directory only.
 * Security notes:
 * - Only .php files are allowed
 * - Path traversal is blocked
 * - Optional access key gate (set ACCESS_KEY to a non-empty string)
 */

const ACCESS_KEY = ''; // Example: 'my-strong-key-123'
const PROTECTED_FILE = 'editor.php';

$baseDir = __DIR__;
$message = '';
$error = '';
$currentFile = '';
$currentContent = '';

function isAuthorized(): bool
{
    if (ACCESS_KEY === '') {
        return true;
    }

    $given = $_POST['access_key'] ?? $_GET['access_key'] ?? '';

    return hash_equals(ACCESS_KEY, (string) $given);
}

function sanitizePhpFileName(string $name): string
{
    $name = trim($name);
    $name = str_replace('\\', '/', $name);
    $name = basename($name);

    if ($name === '' || !preg_match('/^[A-Za-z0-9._-]+\\.php$/', $name)) {
        return '';
    }

    return $name;
}

function listPhpFiles(string $dir): array
{
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [];
    $names = array_map('basename', $files);
    $names = array_values(array_filter($names, static fn(string $name): bool => strtolower($name) !== strtolower(PROTECTED_FILE)));
    sort($names, SORT_NATURAL | SORT_FLAG_CASE);

    return $names;
}

if (!isAuthorized()) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileFromPost = (string)($_POST['file_name'] ?? '');
    $contentFromPost = (string)($_POST['content'] ?? '');

    $safeFileName = sanitizePhpFileName($fileFromPost);
    if ($safeFileName === '') {
        $error = 'Invalid file name. Use only letters, numbers, dot, underscore, dash and end with .php';
    } elseif (strtolower($safeFileName) === strtolower(PROTECTED_FILE)) {
        $error = 'editor.php is protected and cannot be edited.';
    } else {
        $targetPath = $baseDir . DIRECTORY_SEPARATOR . $safeFileName;

        $bytes = @file_put_contents($targetPath, $contentFromPost, LOCK_EX);
        if ($bytes === false) {
            $error = 'Failed to write file.';
        } else {
            $message = 'Saved: ' . $safeFileName;
            $currentFile = $safeFileName;
            $currentContent = $contentFromPost;
        }
    }
}

if ($currentFile === '') {
    $fileFromGet = (string)($_GET['file'] ?? '');
    $safeFileName = sanitizePhpFileName($fileFromGet);

    if ($safeFileName !== '') {
        if (strtolower($safeFileName) === strtolower(PROTECTED_FILE)) {
            $safeFileName = '';
        }
    }

    if ($safeFileName !== '') {
        $path = $baseDir . DIRECTORY_SEPARATOR . $safeFileName;
        if (is_file($path)) {
            $currentFile = $safeFileName;
            $loaded = @file_get_contents($path);
            $currentContent = $loaded === false ? '' : $loaded;
        }
    }
}

$files = listPhpFiles($baseDir);
if ($currentFile === '' && !empty($files)) {
    $currentFile = $files[0];
    $loaded = @file_get_contents($baseDir . DIRECTORY_SEPARATOR . $currentFile);
    $currentContent = $loaded === false ? '' : $loaded;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Editor</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }
        .wrap {
            max-width: 1100px;
            margin: 20px auto;
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            padding: 16px;
        }
        h1 {
            margin: 0 0 14px;
            font-size: 20px;
        }
        .row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        label {
            font-size: 14px;
            color: #374151;
        }
        input[type="text"], select {
            height: 36px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0 10px;
            min-width: 220px;
        }
        textarea {
            width: 100%;
            min-height: 560px;
            margin-top: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            font-family: Consolas, monospace;
            font-size: 14px;
            line-height: 1.45;
            resize: vertical;
            box-sizing: border-box;
        }
        button {
            height: 36px;
            border: 0;
            border-radius: 6px;
            padding: 0 14px;
            background: #0f766e;
            color: #fff;
            cursor: pointer;
            font-weight: 600;
        }
        .message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
        }
        .ok {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .hint {
            margin-top: 8px;
            color: #6b7280;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>PHP Editor (write folder)</h1>

    <form method="get" class="row">
        <label for="file">Open:</label>
        <select name="file" id="file">
            <?php foreach ($files as $file): ?>
                <option value="<?= htmlspecialchars($file) ?>" <?= $file === $currentFile ? 'selected' : '' ?>>
                    <?= htmlspecialchars($file) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (ACCESS_KEY !== ''): ?>
            <input type="text" name="access_key" placeholder="Access key" value="<?= htmlspecialchars((string)($_GET['access_key'] ?? '')) ?>">
        <?php endif; ?>
        <button type="submit">Load</button>
    </form>

    <form method="post">
        <div class="row" style="margin-top: 12px;">
            <label for="file_name">File Name:</label>
            <input type="text" id="file_name" name="file_name" value="<?= htmlspecialchars($currentFile) ?>" placeholder="example.php" required>
            <?php if (ACCESS_KEY !== ''): ?>
                <input type="text" name="access_key" placeholder="Access key" value="<?= htmlspecialchars((string)($_POST['access_key'] ?? $_GET['access_key'] ?? '')) ?>">
            <?php endif; ?>
            <button type="submit">Save (Create/Update)</button>
        </div>

        <textarea name="content" spellcheck="false"><?= htmlspecialchars($currentContent) ?></textarea>
    </form>

    <?php if ($message !== ''): ?>
        <div class="message ok"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="message err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="hint">Allowed: .php files in this folder only. editor.php is protected.</div>
</div>
</body>
</html>
