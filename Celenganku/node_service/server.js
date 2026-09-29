const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

// Endpoint to trigger notification from PHP
app.post('/notify', (req, res) => {
    const { user_id, message, amount } = req.body;
    io.emit(`notification_${user_id}`, { message, amount });
    res.json({ success: true });
});

io.on('connection', (socket) => {
  console.log('A user connected');
  socket.on('disconnect', () => {
    console.log('User disconnected');
  });
});

server.listen(3000, () => {
  console.log('Node.js WebSocket Server running on port 3000');
});
