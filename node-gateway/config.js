module.exports = {
  SOAP_WSDL: process.env.SOAP_WSDL || 'http://localhost:8080/php-legacy/wsdl/library.wsdl',
  SOAP_ENDPOINT: process.env.SOAP_ENDPOINT || 'http://localhost:8080/php-legacy/soap-server.php',
  REPORT_RAW_URL: process.env.REPORT_RAW_URL || 'http://localhost:8080/php-legacy/report.php?raw=1',
  PORT: process.env.PORT || 3000,
  LOWDB_FILE: process.env.LOWDB_FILE || __dirname + '/lowdb.json'
}
