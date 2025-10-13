const { createApp } = require('./app');

const PORT = process.env.PORT || 9987;

/**
 * Boots the Express HTTP server and begins listening for requests.
 *
 * @returns {import('http').Server} Node HTTP server instance.
 */
function startServer() {
  const app = createApp();
  return app.listen(PORT, () => {
    console.log(`Apple Fitness Display listening on port ${PORT}`);
  });
}

if (require.main === module) {
  startServer();
}

module.exports = {
  startServer,
};
