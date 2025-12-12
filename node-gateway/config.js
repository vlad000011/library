
module.exports = {
  SOAP_WSDL: 'http://localhost/library/backend-php-legacy/wsdl/library.wsdl',
  SOAP_ENDPOINT: 'http://localhost/library/backend-php-legacy/soap-server.php',
  REPORT_RAW_URL: 'http://localhost/library/backend-php-legacy/report.php?raw=1',
  PORT: process.env.PORT || 3000,
  LOWDB_FILE: __dirname + '/lowdb.json'
};
