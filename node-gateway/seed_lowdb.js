// seed_lowdb.js
const { Low, JSONFile } = require('lowdb');
const { nanoid } = require('nanoid');
const dbFile = __dirname + '/lowdb.json';
const adapter = new JSONFile(dbFile);
const db = new Low(adapter);

async function seed(){
  await db.read();
  db.data = db.data || { digitalResources: [], downloadLog: [] };
  if (db.data.digitalResources.length === 0) {
    const items = [
      { id: nanoid(), title: 'Clean Code', author:'Robert C. Martin', format:'pdf', fileSize: 1024, tags:['programming','best-practices'], downloadCount:0 },
      { id: nanoid(), title: 'Deep Learning', author:'Ian Goodfellow', format:'pdf', fileSize: 2048, tags:['ai','ml'], downloadCount:0 },
      { id: nanoid(), title: 'Eloquent JavaScript', author:'Marijn Haverbeke', format:'epub', fileSize: 800, tags:['javascript'], downloadCount:0 },
      { id: nanoid(), title: 'The Pragmatic Programmer', author:'Andrew Hunt', format:'pdf', fileSize: 900, tags:['programming'], downloadCount:0 }
    ];
    db.data.digitalResources.push(...items);
    await db.write();
    console.log('Seeded lowdb with', items.length, 'items');
  } else {
    console.log('lowdb already seeded');
  }
}
seed();
