const WebSocket = require('ws');
const { setupWSConnection } = require('y-websocket/bin/utils.js');

const port = 1234;
const server = new WebSocket.Server({ port });

server.on('connection', (conn, req) => {
  setupWSConnection(conn, req);
});

console.log(`✅ Yjs WebSocket server running at ws://localhost:${port}`);
