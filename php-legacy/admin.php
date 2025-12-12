<?php
// admin.php - пример вызова SOAP сервера (использует WSDL)
$wsdl = 'http://localhost:8080/php-legacy/wsdl/library.wsdl';
try {
    $client = new SoapClient($wsdl, ['trace'=>1, 'exceptions'=>1]);

    // пример getBookByInventory
    $resp = $client->getBookByInventory(['inventory_number' => 'LIB-2024-001']);
    echo "<h2>getBookByInventory</h2><pre>"; print_r($resp); echo "</pre>";

    // пример регистрации выдачи
    $loan = $client->registerLoan(['inventory_number' => 'LIB-2024-001', 'reader_card' => 'R-12345']);
    echo "<h2>registerLoan</h2><pre>"; print_r($loan); echo "</pre>";

    // пример поиска по автору
    $xmlList = $client->searchBooksByAuthor(['author_name' => 'Robert']);
    echo "<h2>searchBooksByAuthor (XML)</h2><pre>"; echo htmlspecialchars($xmlList); echo "</pre>";

} catch (Exception $e) {
    echo "SOAP error: " . $e->getMessage();
}
