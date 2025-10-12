const express = require('express');
const path = require('path');
const { registerRoutes } = require('./routes');
const { notFoundHandler } = require('./middleware/notFoundHandler');

/**
 * Creates and configures the Express application used by the server.
 *
 * @returns {import('express').Express} Configured Express application instance.
 */
function createApp() {
  const app = express();
  app.use(express.static(path.join(__dirname, '..', 'public')));
  registerRoutes(app);
  app.use(notFoundHandler);
  return app;
}

module.exports = {
  createApp,
};
