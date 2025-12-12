const express = require('express');
const bodyParser = require('body-parser');
const soap = require('soap');
const xml2js = require('xml2js');
const { Low, JSONFile } = require('lowdb');
const { nanoid } = require('nanoid');
const config = require('./config');

const app = express();
app.use(bodyParser.json());
app.use(express.static(__dirname + '/../frontend')); // serve frontend

// lowdb init
const adapter = new JSONFile(config.LOWDB_FILE);
const db = new Low(adapter);

async function initDB(){
  await db.read();
  db.data = db.data || { digitalResources: [], downloadLog: [] };
  await db.write();
}
initDB();

// Проверка доступности SOAP сервера на старте (выполняется при первом обращении)
let soapClient = null;
async function ensureSoapClient(){
  if (soapClient) return soapClient;
  try {
    soapClient = await soap.createClientAsync(config.SOAP_WSDL, { endpoint: config.SOAP_ENDPOINT });
    return soapClient;
  } catch (e) {
    console.error('SOAP client error:', e.message);
    throw e;
  }
}

// GET /api/physical/books?author=...&inventory=...
app.get('/api/physical/books', async (req, res) => {
  try {
    const client = await ensureSoapClient();
    if (req.query.inventory) {
      const resp = await client.getBookByInventoryAsync({ inventory_number: req.query.inventory });
      // resp is [result, rawResponse, soapHeader, rawRequest]
      const result = resp[0];
      return res.json({ success: true, book: result });
    } else if (req.query.author) {
      const resp = await client.searchBooksByAuthorAsync({ author_name: req.query.author });
      const xml = resp[0];
      // xml is a string -> parse
      xml2js.parseString(xml, { explicitArray:false }, (err, parsed) => {
        if (err) return res.status(500).json({ success:false, error: err.message });
        // build array
        const books = [];
        if (parsed && parsed.books && parsed.books.book) {
          const raw = parsed.books.book;
          if (Array.isArray(raw)) books.push(...raw);
          else books.push(raw);
        }
        return res.json({ success:true, books });
      });
    } else {
      // default: return all? Not available via SOAP; return error
      return res.status(400).json({ success:false, message: 'Specify inventory or author' });
    }
  } catch (e) {
    return res.status(500).json({ success:false, message: e.message });
  }
});

// POST /api/physical/loan { inventory_number, reader_card }
app.post('/api/physical/loan', async (req,res) => {
  const { inventory_number, reader_card } = req.body;
  if (!inventory_number || !reader_card) return res.status(400).json({ success:false, message:'inventory_number and reader_card required' });
  try {
    const client = await ensureSoapClient();
    const resp = await client.registerLoanAsync({ inventory_number, reader_card });
    // resp[0] is object matching LoanResult
    return res.json({ success:true, result: resp[0] });
  } catch (e) {
    return res.status(500).json({ success:false, message: e.message });
  }
});

// GET /api/internal/overdue-report -> fetch raw XML from report.php and parse to JSON
const fetch = require('node-fetch');
app.get('/api/internal/overdue-report', async (req,res) => {
  try {
    const r = await fetch(config.REPORT_RAW_URL);
    if (!r.ok) return res.status(500).json({ success:false, message: 'Report fetch failed' });
    const xml = await r.text();
    xml2js.parseString(xml, { explicitArray:false }, (err, parsed) => {
      if (err) return res.status(500).json({ success:false, error:err.message });
      // parsed.overdue.item may be array or single object
      const items = parsed.overdue && parsed.overdue.item ? (Array.isArray(parsed.overdue.item) ? parsed.overdue.item : [parsed.overdue.item]) : [];
      return res.json({ success:true, items });
    });
  } catch (e) {
    return res.status(500).json({ success:false, message: e.message });
  }
});

// GET /api/digital/resources
app.get('/api/digital/resources', async (req,res) => {
  await db.read();
  res.json({ success:true, resources: db.data.digitalResources });
});

// POST /api/digital/download { resourceId, userId }
app.post('/api/digital/download', async (req,res) => {
  const { resourceId, userId } = req.body;
  if (!resourceId || !userId) return res.status(400).json({ success:false, message:'resourceId and userId required' });
  await db.read();
  const resource = db.data.digitalResources.find(r => r.id === resourceId);
  if (!resource) return res.status(404).json({ success:false, message:'Resource not found' });

  // increment downloadCount
  resource.downloadCount = (resource.downloadCount || 0) + 1;
  db.data.downloadLog.push({
    id: nanoid(),
    resourceId,
    userId,
    timestamp: new Date().toISOString()
  });
  await db.write();

  // Return placeholder link
  return res.json({ success:true, downloadLink: `/static/files/${resourceId}.${resource.format}` });
});

// Start server
app.listen(config.PORT, () => {
  console.log(`Node gateway listening on port ${config.PORT}`);
  // optional: try connecting SOAP once
  ensureSoapClient().then(()=>console.log('SOAP client ready')).catch(err=>console.error('SOAP init failed:', err.message));
});
