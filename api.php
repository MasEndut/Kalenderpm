<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function validDate(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

try {
    require __DIR__ . '/config.php';
    $pdo = database();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['month'])) {
            $month = (string) $_GET['month'];
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                respond(['error' => 'Periode bulan tidak valid.'], 422);
            }
            $start = $month . '-01';
            $end = (new DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');
            $query = $pdo->prepare(
                'SELECT r.report_date, r.status, COALESCE(SUM(d.quantity), 0) AS total
                 FROM daily_reports r
                 LEFT JOIN beneficiary_details d ON d.daily_report_id = r.id
                 WHERE r.report_date BETWEEN :start AND :end
                 GROUP BY r.id, r.report_date, r.status'
            );
            $query->execute(['start' => $start, 'end' => $end]);
            $reports = [];
            foreach ($query->fetchAll() as $row) {
                $reports[$row['report_date']] = [
                    'status' => $row['status'],
                    'total' => (int) $row['total'],
                    'saved' => true,
                ];
            }
            respond(['reports' => $reports]);
        }

        $date = (string) ($_GET['date'] ?? '');
        if (!validDate($date)) {
            respond(['error' => 'Tanggal tidak valid.'], 422);
        }
        $query = $pdo->prepare('SELECT id, status FROM daily_reports WHERE report_date = :date');
        $query->execute(['date' => $date]);
        $report = $query->fetch();
        if (!$report) {
            respond(['date' => $date, 'status' => null, 'saved' => false, 'details' => []]);
        }

        $detailsQuery = $pdo->prepare(
            'SELECT name, quantity FROM beneficiary_details WHERE daily_report_id = :id ORDER BY sort_order, id'
        );
        $detailsQuery->execute(['id' => $report['id']]);
        $details = array_map(static fn(array $row): array => [
            'name' => $row['name'],
            'quantity' => (int) $row['quantity'],
        ], $detailsQuery->fetchAll());
        respond(['date' => $date, 'status' => $report['status'], 'saved' => true, 'details' => $details]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        respond(['error' => 'Metode permintaan tidak didukung.'], 405);
    }

    $csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        respond(['error' => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.'], 419);
    }

    try {
        $payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        respond(['error' => 'Isi permintaan tidak valid.'], 400);
    }
    if (!is_array($payload)) {
        respond(['error' => 'Isi permintaan tidak valid.'], 400);
    }

    $date = (string) ($payload['date'] ?? '');
    $status = $payload['status'] ?? null;
    $details = $payload['details'] ?? null;
    if (!validDate($date)) {
        respond(['error' => 'Tanggal laporan tidak valid.'], 422);
    }
    if (!in_array($status, ['Buka', 'Tutup'], true)) {
        respond(['error' => 'Pilih status Buka atau Tutup sebelum menyimpan.'], 422);
    }
    if (!is_array($details)) {
        respond(['error' => 'Rincian penerima tidak valid.'], 422);
    }

    $cleanDetails = [];
    foreach ($details as $index => $detail) {
        if (!is_array($detail)) {
            respond(['error' => 'Salah satu baris rincian tidak valid.'], 422);
        }
        $name = trim((string) ($detail['name'] ?? ''));
        $quantity = $detail['quantity'] ?? null;
        if ($name === '' || mb_strlen($name) > 255) {
            respond(['error' => 'Nama pada setiap baris wajib diisi dan maksimal 255 karakter.'], 422);
        }
        if (!(is_int($quantity) || (is_string($quantity) && preg_match('/^\d+$/', $quantity)))) {
            respond(['error' => 'Jumlah PM harus berupa bilangan bulat nol atau lebih.'], 422);
        }
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($quantity === false) {
            respond(['error' => 'Jumlah PM harus berupa bilangan bulat nol atau lebih.'], 422);
        }
        $cleanDetails[] = ['name' => $name, 'quantity' => $quantity, 'sort_order' => $index];
    }

    $pdo->beginTransaction();
    $saveReport = $pdo->prepare(
        'INSERT INTO daily_reports (report_date, status)
         VALUES (:date, :status)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), status = VALUES(status), updated_at = CURRENT_TIMESTAMP'
    );
    $saveReport->execute(['date' => $date, 'status' => $status]);
    $reportId = (int) $pdo->lastInsertId();

    $deleteDetails = $pdo->prepare('DELETE FROM beneficiary_details WHERE daily_report_id = :id');
    $deleteDetails->execute(['id' => $reportId]);
    if ($cleanDetails !== []) {
        $insertDetail = $pdo->prepare(
            'INSERT INTO beneficiary_details (daily_report_id, name, quantity, sort_order)
             VALUES (:report_id, :name, :quantity, :sort_order)'
        );
        foreach ($cleanDetails as $detail) {
            $insertDetail->execute([
                'report_id' => $reportId,
                'name' => $detail['name'],
                'quantity' => $detail['quantity'],
                'sort_order' => $detail['sort_order'],
            ]);
        }
    }
    $pdo->commit();

    respond([
        'message' => 'Laporan berhasil disimpan.',
        'date' => $date,
        'status' => $status,
        'saved' => true,
        'details' => array_map(static fn(array $row): array => [
            'name' => $row['name'],
            'quantity' => $row['quantity'],
        ], $cleanDetails),
        'total' => array_sum(array_column($cleanDetails, 'quantity')),
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($error->getMessage());
    respond(['error' => 'Layanan database belum siap. Pastikan MySQL aktif dan skema database sudah diimpor.'], 503);
}