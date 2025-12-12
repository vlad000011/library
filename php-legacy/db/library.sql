-- Schema for physical_books and physical_loans (MySQL / SQLite compatible)
CREATE TABLE IF NOT EXISTS physical_books (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,
  inventory_number VARCHAR(50) UNIQUE NOT NULL,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  year INTEGER,
  location VARCHAR(100),
  status VARCHAR(20) -- values: available, borrowed, lost
);

CREATE TABLE IF NOT EXISTS physical_loans (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,
  book_id INTEGER NOT NULL,
  reader_card VARCHAR(50) NOT NULL,
  date_taken DATE NOT NULL,
  date_returned DATE NULL,
  FOREIGN KEY (book_id) REFERENCES physical_books(id)
);

-- Insert ~20 sample books
INSERT INTO physical_books (inventory_number, title, author, year, location, status) VALUES
('LIB-2024-001','Мастер и Маргарита','Булгаков Михаил Афанасьевич',1966,'Секция А, стеллаж 5, полка 3','available'),
('LIB-2024-002','Война и мир','Толстой Лев Николаевич',1869,'Секция А, стеллаж 2, полка 1','borrowed'),
('LIB-2024-003','Преступление и наказание','Достоевский Фёдор',1866,'Секция Б, стеллаж 1','available'),
('LIB-2024-004','Clean Code','Robert C. Martin',2008,'Зал тех.литературы, стеллаж 12','available'),
('LIB-2024-005','The Pragmatic Programmer','Andrew Hunt',1999,'Зал тех.литературы, стеллаж 13','available'),
('LIB-2024-006','JavaScript: The Good Parts','Douglas Crockford',2008,'Зал тех.литературы, стеллаж 14','lost'),
('LIB-2024-007','Коллекция стихов','А. С. Пушкин',1836,'Секция А, стеллаж 8','available'),
('LIB-2024-008','Design Patterns','Erich Gamma',1994,'Зал тех.литературы, стеллаж 12','available'),
('LIB-2024-009','HTML и CSS','Jon Duckett',2011,'Зал тех.литературы, стеллаж 15','available'),
('LIB-2024-010','Algorithms','Robert Sedgewick',2011,'Зал тех.литературы, стеллаж 17','available'),
('LIB-2024-011','Малыш и Карлсон','Астрид Линдгрен',1955,'Детская секция, стеллаж 3','available'),
('LIB-2024-012','Сказки','Шарль Перро',1697,'Детская секция, стеллаж 2','available'),
('LIB-2024-013','История России','Иванов И.И.',2001,'Секция В, стеллаж 4','available'),
('LIB-2024-014','Экономика','Петров П.П.',2010,'Секция Г, стеллаж 1','available'),
('LIB-2024-015','Философия','Сократ',-400,'Секция Д, стеллаж 1','available'),
('LIB-2024-016','Основы программирования','Сидоров С.С.',2018,'Зал тех.литературы, стеллаж 11','available'),
('LIB-2024-017','Python Cookbook','David Beazley',2013,'Зал тех.литературы, стеллаж 16','available'),
('LIB-2024-018','Deep Learning','Ian Goodfellow',2016,'Зал тех.литературы, стеллаж 18','available'),
('LIB-2024-019','Clean Architecture','Robert C. Martin',2017,'Зал тех.литературы, стеллаж 12','available'),
('LIB-2024-020','Refactoring','Martin Fowler',1999,'Зал тех.литературы, стеллаж 19','available');

-- Optional: sample loan to reflect borrowed state
INSERT INTO physical_loans (book_id, reader_card, date_taken, date_returned) VALUES
((SELECT id FROM physical_books WHERE inventory_number='LIB-2024-002'),'R-00001','2024-03-10',NULL);
