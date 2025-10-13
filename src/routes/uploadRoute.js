const express = require('express');
const fs = require('fs');
const { createUploadMiddleware } = require('../middleware/uploadMiddleware');
const { WorkoutXmlParser } = require('../utils/WorkoutXmlParser');
const { WorkoutDataNormalizer } = require('../utils/WorkoutDataNormalizer');
const { WorkoutSummaryBuilder } = require('../utils/WorkoutSummaryBuilder');

const router = express.Router();
const upload = createUploadMiddleware();
const parser = new WorkoutXmlParser();

/**
 * Processes an uploaded Apple Health XML file and responds with normalized workouts.
 *
 * @param {import('express').Request} req Incoming request containing the uploaded file.
 * @param {import('express').Response} res Express response instance used to return JSON payloads.
 * @returns {Promise<void>} Resolves when the response has been sent.
 */
async function handleUpload(req, res) {
  if (!req.file) {
    res.status(400).json({ error: 'Nie przesłano pliku exportu.' });
    return;
  }

  const filePath = req.file.path;

  try {
    const rawWorkouts = await parser.parseWorkoutsFromFile(filePath);
    const workouts = WorkoutDataNormalizer.normalizeWorkouts(rawWorkouts);
    const stats = WorkoutSummaryBuilder.buildSummary(workouts);

    res.json({ workouts, stats });
  } catch (error) {
    console.error('Upload parsing failed:', error);
    res.status(500).json({
      error: 'Nie udało się przetworzyć pliku XML. Upewnij się, że to plik exportu Apple Health.',
    });
  } finally {
    fs.unlink(filePath, () => {});
  }
}

router.post('/', upload.single('workoutFile'), handleUpload);

module.exports = router;
