<?php
// soap-server.php
// Требует: php + ext-soap + PDO (MySQL/SQLite). Настройки БД берём из env или простого массива.

ini_set('display_errors', 1);
error_reporting(E_ALL);

class LibraryService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Вернуть BookInfo как ассоциативный массив (SOAP автоматически сериализует в XML по WSDL)
    public function getBookByInventory($params) {
        $inventory = is_array($params) && isset($params['inventory_number']) ? $params['inventory_number'] : $params;
        $stmt = $this->pdo->prepare("SELECT * FROM physical_books WHERE inventory_number = :inv LIMIT 1");
        $stmt->execute([':inv' => $inventory]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$book) {
            // вернуть пустой объект с сообщением (в пределах WSDL — здесь просто возвращаем null-equivalent)
            return [
                'id' => 0,
                'inventory_number' => '',
                'title' => '',
                'author' => '',
                'year' => 0,
                'location' => '',
                'status' => 'not_found'
            ];
        }
        return $book;
    }

    // Возвращаем XML-строку списка книг по автору (по ТЗ — возвращаем XML)
    public function searchBooksByAuthor($params) {
        $author = is_array($params) && isset($params['author_name']) ? $params['author_name'] : $params;
        $stmt = $this->pdo->prepare("SELECT * FROM physical_books WHERE author LIKE :a");
        $stmt->execute([':a' => "%$author%"]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Сформируем XML вручную
        $xml = new SimpleXMLElement('<books/>');
        foreach ($rows as $r) {
            $b = $xml->addChild('book');
            foreach ($r as $k => $v) $b->addChild($k, htmlspecialchars($v));
        }
        return $xml->asXML();
    }

    // registerLoan($inventory_number, $reader_card) -> LoanResult
    public function registerLoan($params) {
        $inv = is_array($params) && isset($params['inventory_number']) ? $params['inventory_number'] : $params;
        $reader = is_array($params) && isset($params['reader_card']) ? $params['reader_card'] : null;
        if (is_array($params)) {
            $inv = $params['inventory_number'] ?? '';
            $reader = $params['reader_card'] ?? '';
        }

        // Проверки
        $stmt = $this->pdo->prepare("SELECT * FROM physical_books WHERE inventory_number = :inv LIMIT 1");
        $stmt->execute([':inv' => $inv]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$book) {
            return ['success' => false, 'message' => 'Книга не найдена', 'loan_id' => null];
        }
        if ($book['status'] !== 'available') {
            return ['success' => false, 'message' => 'Книга уже выдана или недоступна', 'loan_id' => null];
        }
        if (empty($reader)) {
            return ['success' => false, 'message' => 'Неверный номер чит. билета', 'loan_id' => null];
        }

        // Создать выдачу
        $this->pdo->beginTransaction();
        try {
            $dateTaken = date('Y-m-d');
            $ins = $this->pdo->prepare("INSERT INTO physical_loans (book_id, reader_card, date_taken, date_returned) VALUES (:book_id, :rc, :dt, NULL)");
            $ins->execute([':book_id' => $book['id'], ':rc' => $reader, ':dt' => $dateTaken]);
            $loanId = $this->pdo->lastInsertId();

            $upd = $this->pdo->prepare("UPDATE physical_books SET status='borrowed' WHERE id = :id");
            $upd->execute([':id' => $book['id']]);

            $this->pdo->commit();
            return [
                'success' => true,
                'message' => "Книга успешно выдана читателю $reader",
                'loan_id' => (int)$loanId,
                'inventory_number' => $inv,
                'date_taken' => $dateTaken
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success'=>false, 'message'=>'Ошибка при регистрации выдачи: '.$e->getMessage(), 'loan_id' => null];
        }
    }

    // returnBook($inventory_number) -> ReturnResult
    public function returnBook($params) {
        $inv = is_array($params) && isset($params['inventory_number']) ? $params['inventory_number'] : $params;
        $stmt = $this->pdo->prepare("SELECT * FROM physical_books WHERE inventory_number = :inv LIMIT 1");
        $stmt->execute([':inv' => $inv]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$book) {
            return ['success'=>false, 'message'=>'Книга не найдена'];
        }
        // Найти активную выдачу
        $loanStmt = $this->pdo->prepare("SELECT * FROM physical_loans WHERE book_id = :bid AND date_returned IS NULL LIMIT 1");
        $loanStmt->execute([':bid' => $book['id']]);
        $loan = $loanStmt->fetch(PDO::FETCH_ASSOC);
        if (!$loan) {
            return ['success'=>false, 'message'=>'Активных выдач не найдено'];
        }

        $this->pdo->beginTransaction();
        try {
            $dateReturned = date('Y-m-d');
            $upLoan = $this->pdo->prepare("UPDATE physical_loans SET date_returned = :dr WHERE id = :id");
            $upLoan->execute([':dr' => $dateReturned, ':id' => $loan['id']]);
            $upBook = $this->pdo->prepare("UPDATE physical_books SET status='available' WHERE id = :id");
            $upBook->execute([':id' => $book['id']]);
            $this->pdo->commit();
            return ['success'=>true, 'message'=>'Книга успешно возвращена'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success'=>false, 'message'=>'Ошибка при возврате: '.$e->getMessage()];
        }
    }
}

// ---- bootstrap ----
// Настройки DB — можно менять под MySQL/SQLite.
$db_dsn = getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/db/library.db';
$db_user = getenv('DB_USER') ?: null;
$db_pass = getenv('DB_PASS') ?: null;

try {
    $pdo = new PDO($db_dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die("DB connect error: " . $e->getMessage());
}

// Создаём SoapServer с WSDL
$wsdl = __DIR__ . '/wsdl/library.wsdl';
$server = new SoapServer($wsdl);
$server->setObject(new LibraryService($pdo));
$server->handle();
