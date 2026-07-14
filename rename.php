<?php

declare(strict_types=1);

$directory = __DIR__ . '/../parts';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        http_response_code(400);
        exit('Données invalides');
    }

    // Étape 1 : renommage temporaire
    foreach ($data as $index => $file) {

        $oldPath = $directory . '/' . $file['oldName'];
        $tmpPath = $directory . '/__tmp_' . $index . '.tmp';

        if (file_exists($oldPath)) {
            rename($oldPath, $tmpPath);
        }
    }

    // Étape 2 : renommage final
    foreach ($data as $index => $file) {

        $name = trim($file['newName']);

        if ($name === '') {
            $name = 'chapitre';
        }

        // slugification simple
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name);
        $name = trim($name, '-');

        $newFileName = sprintf(
            '%02d-%s.md',
            $index,
            $name
        );

        rename(
            $directory . '/__tmp_' . $index . '.tmp',
            $directory . '/' . $newFileName
        );
    }

    echo json_encode([
        'success' => true
    ]);

    exit;
}

$files = glob($directory . '/*.md');

natcasesort($files);

?>
<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<title>Réorganisation des cours</title>

<style>

body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 40px auto;
}

h1 {
    margin-bottom: 25px;
}

.item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    margin-bottom: 8px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fff;
}

.item:hover {
    background: #f8f8f8;
}

.handle {
    cursor: move;
    font-size: 20px;
    color: #666;
    width: 30px;
}

.original {
    min-width: 280px;
    color: #666;
    font-family: monospace;
}

.filename {
    flex: 1;
    padding: 8px;
    font-size: 14px;
}

button {
    margin-top: 20px;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 15px;
}

.success {
    color: green;
    margin-top: 10px;
}

</style>

</head>

<body>

<h1>Réorganisation et renommage des cours</h1>

<div id="list">

<?php foreach ($files as $file):

    $basename = basename($file);

    $filename = pathinfo($basename, PATHINFO_FILENAME);

    $label = preg_replace(
        '/^\d+-/',
        '',
        $filename
    );
?>

<div
    class="item"
    data-file="<?= htmlspecialchars($basename, ENT_QUOTES) ?>"
>

    <div class="handle">☰</div>

    <div class="original">
        <?= htmlspecialchars($basename) ?>
    </div>

    <input
        class="filename"
        type="text"
        value="<?= htmlspecialchars($label, ENT_QUOTES) ?>"
    >

</div>

<?php endforeach; ?>

</div>

<button id="save">
    Enregistrer les modifications
</button>

<div id="message"></div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>

<script>

new Sortable(
    document.getElementById('list'),
    {
        animation: 150,
        handle: '.handle'
    }
);

document
    .getElementById('save')
    .addEventListener('click', async () => {

        const files = Array.from(
            document.querySelectorAll('.item')
        ).map(item => ({
            oldName: item.dataset.file,
            newName: item
                .querySelector('.filename')
                .value
                .trim()
        }));

        try {

            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(files)
            });

            if (!response.ok) {
                throw new Error();
            }

            document.getElementById('message').innerHTML =
                '<p class="success">✅ Modifications enregistrées.</p>';

            setTimeout(() => {
                location.reload();
            }, 1000);

        } catch {

            alert('Erreur lors de l\'enregistrement.');

        }
    });

</script>

</body>
</html>