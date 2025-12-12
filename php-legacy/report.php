<?php
// report.php
header('Content-Type: text/html; charset=utf-8');
$db_dsn = getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/db/library.db';
$pdo = new PDO($db_dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$type = $_GET['type'] ?? 'overdue';

// параметры: допустим срок выдачи 14 дней; просроченные — date_taken + 14 < today и date_returned is NULL
$overdueDays = 14;
$today = date('Y-m-d');

$stmt = $pdo->prepare("
  SELECT pl.id as loan_id, pb.inventory_number, pb.title, pb.author, pl.reader_card, pl.date_taken
  FROM physical_loans pl
  JOIN physical_books pb ON pl.book_id = pb.id
  WHERE pl.date_returned IS NULL
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Сформируем XML
$xml = new SimpleXMLElement('<overdue/>');
foreach ($rows as $r) {
    $taken = $r['date_taken'];
    $due = date('Y-m-d', strtotime("$taken +$overdueDays days"));
    if ($due < $today) {
        $item = $xml->addChild('item');
        foreach ($r as $k => $v) $item->addChild($k, htmlspecialchars($v));
        $item->addChild('due_date', $due);
        $item->addChild('days_overdue', (string)((strtotime($today)-strtotime($due))/86400));
    }
}

$xmlString = $xml->asXML();

// Если параметр ?raw=1 или заголовок Accept: application/xml — вернуть чистый XML
if (isset($_GET['raw']) && $_GET['raw'] == '1') {
    header('Content-Type: application/xml; charset=utf-8');
    echo $xmlString;
    exit;
}

// Иначе применяем XSLT для отображения
$xslFile = __DIR__ . '/report.xsl';
$xsl = new DOMDocument;
$xsl->load($xslFile);

$xmlDoc = new DOMDocument;
$xmlDoc->loadXML($xmlString);

$proc = new XSLTProcessor();
$proc->importStylesheet($xsl);
$html = $proc->transformToXML($xmlDoc);
echo $html;
