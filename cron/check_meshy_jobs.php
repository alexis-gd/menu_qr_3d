<?php
// Cron script que se ejecuta cada 2 minutos desde cPanel.
// Consulta trabajos pendientes/processing en meshy_jobs, llama al API de Meshy y
// descarga el glb cuando estén listos.

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/helpers.php';
// no auth necesario para cron

$pdo->exec("SET NAMES utf8mb4");

$stmt = $pdo->prepare("SELECT * FROM meshy_jobs WHERE status IN ('pending','processing') ORDER BY intentos ASC LIMIT 10");
$stmt->execute();
$jobs = $stmt->fetchAll();

foreach ($jobs as $job) {
    $taskId = $job['meshy_task_id'];
    $productId = $job['producto_id'];
    $jobId = $job['id'];

    // aumentar contador de intentos
    $pdo->prepare("UPDATE meshy_jobs SET intentos = intentos + 1 WHERE id = :id")
        ->execute([':id' => $jobId]);

    $ch = curl_init("https://api.meshy.ai/openapi/v1/image-to-3d/$taskId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . MESHY_API_KEY
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        error_log("Meshy poll failed for $taskId: $err\n", 3, __DIR__.'/check.log');
        continue;
    }
    $data = json_decode($resp, true);
    if (isset($data['status'])) {
        $status = $data['status'];
        $pdo->prepare("UPDATE meshy_jobs SET status = :s WHERE id = :id")
            ->execute([':s' => $status, ':id' => $jobId]);

        if ($status === 'succeeded' && !empty($data['result']['download_url'])) {
            // descargar glb
            $url = $data['result']['download_url'];
            $modelDir = __DIR__ . '/../uploads/modelos';
            if (!is_dir($modelDir)) mkdir($modelDir, 0755, true);
            $filename = sprintf('modelo_%d_%d.glb', $productId, time());
            $destPath = $modelDir . '/' . $filename;
            $fileData = file_get_contents($url);
            if ($fileData !== false) {
                file_put_contents($destPath, $fileData);
                // actualizar producto
                $pdo->prepare('UPDATE productos SET modelo_glb_path = :path, tiene_ar = 1 WHERE id = :pid')
                    ->execute([':path' => "$filename", ':pid' => $productId]);
            }
        }
    }
}
