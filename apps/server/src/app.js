const express = require('express');
const fs = require('fs');
const path = require('path');
const { registerRoutes } = require('./routes');
const { notFoundHandler } = require('./middleware/notFoundHandler');

const CLIENT_BUILD_DIR = path.resolve(__dirname, '../../client/dist');
const CLIENT_INDEX_FILE = path.join(CLIENT_BUILD_DIR, 'index.html');

/**
 * Creates and configures the Express application used by the server.
 *
 * @returns {import('express').Express} Configured Express application instance.
 */
function createApp() {
  const app = express();
  const hasClientBuild = fs.existsSync(CLIENT_INDEX_FILE);

  if (hasClientBuild) {
    app.use(express.static(CLIENT_BUILD_DIR));
  }

  registerRoutes(app);

  app.use('/api', notFoundHandler);

  if (hasClientBuild) {
    app.get('*', (req, res, next) => {
      if (req.path.startsWith('/api')) {
        next();
        return;
      }

      res.sendFile(CLIENT_INDEX_FILE, (error) => {
        if (error) {
          next(error);
        }
      });
    });
  }

  app.use(notFoundHandler);

  return app;
}

module.exports = {
  createApp,
};
