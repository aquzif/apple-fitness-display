const uploadRoute = require('./uploadRoute');
const workoutTypesRoute = require('./workoutTypesRoute');

/**
 * Registers API routes on the provided Express application instance.
 *
 * @param {import('express').Express} app Express application instance.
 * @returns {void}
 */
function registerRoutes(app) {
  app.use('/api/upload', uploadRoute);
  app.use('/api/workout-types', workoutTypesRoute);
}

module.exports = {
  registerRoutes,
};
