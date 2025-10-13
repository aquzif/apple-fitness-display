const express = require('express');
const { workoutTypes, defaultType } = require('../config/workoutTypes');

const router = express.Router();

/**
 * Responds with the configured workout type metadata used by the frontend.
 *
 * @param {import('express').Request} req Incoming request object.
 * @param {import('express').Response} res Express response instance.
 * @returns {void}
 */
function handleWorkoutTypesRequest(req, res) {
  res.json({ workoutTypes, defaultType });
}

router.get('/', handleWorkoutTypesRequest);

module.exports = router;
