const express = require('express');
const multer = require('multer');
const fs = require('fs');
const fsp = require('fs/promises');
const os = require('os');
const path = require('path');
const { XMLParser } = require('fast-xml-parser');
const dayjs = require('dayjs');
const utc = require('dayjs/plugin/utc');
const timezone = require('dayjs/plugin/timezone');
const localizedFormat = require('dayjs/plugin/localizedFormat');
const { workoutTypes, defaultType } = require('./src/config/workoutTypes');

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(localizedFormat);

const app = express();
const PORT = process.env.PORT || 9987;
const MAX_FILE_SIZE = 10 * 1024 * 1024 * 1024; // 10 GB

const upload = multer({
  storage: multer.diskStorage({
    destination: (req, file, cb) => cb(null, os.tmpdir()),
    filename: (req, file, cb) => {
      const safeName = file.originalname.replace(/[^a-zA-Z0-9_.-]/g, '_');
      cb(null, `${Date.now()}-${safeName}`);
    },
  }),
  limits: {
    fileSize: MAX_FILE_SIZE,
  },
});

app.use(express.static(path.join(__dirname, 'public')));

app.get('/api/workout-types', (req, res) => {
  res.json({ workoutTypes, defaultType });
});

app.post('/api/upload', upload.single('workoutFile'), async (req, res) => {
  if (!req.file) {
    return res.status(400).json({ error: 'Nie przesłano pliku exportu.' });
  }

  const filePath = req.file.path;

  try {
    const xml = await fsp.readFile(filePath, 'utf8');
    const parser = new XMLParser({
      ignoreAttributes: false,
      attributeNamePrefix: '',
      allowBooleanAttributes: true,
      trimValues: true,
    });

    const parsed = parser.parse(xml);
    const workouts = normalizeWorkouts(parsed.HealthData?.Workout || []);

    res.json({
      workouts,
      stats: buildSummary(workouts),
    });
  } catch (error) {
    console.error('Upload parsing failed:', error);
    res.status(500).json({ error: 'Nie udało się przetworzyć pliku XML. Upewnij się, że to plik exportu Apple Health.' });
  } finally {
    fs.unlink(filePath, () => {});
  }
});

app.use((req, res) => {
  res.status(404).json({ error: 'Nie znaleziono zasobu.' });
});

app.listen(PORT, () => {
  console.log(`Apple Fitness Display listening on port ${PORT}`);
});

function normalizeWorkouts(workoutNodes) {
  const nodesArray = Array.isArray(workoutNodes)
    ? workoutNodes
    : workoutNodes
    ? [workoutNodes]
    : [];

  return nodesArray
    .map((node, index) => {
      if (!node || typeof node !== 'object') {
        return null;
      }

      const meta = normalizeMetadata(node.MetadataEntry);
      const statistics = normalizeStatistics(node.WorkoutStatistics);
      const events = normalizeEvents(node.WorkoutEvent);

      const start = parseDate(node.startDate, meta);
      const end = parseDate(node.endDate, meta);

      const durationSeconds = normalizeDuration(node.duration, node.durationUnit);
      const durationText = formatDuration(durationSeconds);

      const distance = parseQuantity(node.totalDistance, node.totalDistanceUnit || meta.HKQuantityTypeIdentifierDistanceWalkingRunningUnit);
      const energy = parseQuantity(node.totalEnergyBurned, node.totalEnergyBurnedUnit);
      const swim = parseQuantity(node.totalSwimmingStrokeCount, node.totalSwimmingStrokeCountUnit);

      const activityKey = node.workoutActivityType;
      const typeConfig = workoutTypes[activityKey] || defaultType;

      return {
        id: `${activityKey || 'workout'}-${index}`,
        activityKey,
        label: typeConfig.label,
        icon: typeConfig.icon,
        accentColor: typeConfig.accentColor,
        iconBackground: typeConfig.iconBackground,
        startDate: start ? start.toISOString() : null,
        endDate: end ? end.toISOString() : null,
        durationSeconds,
        durationText,
        distance,
        energy,
        swim,
        metadata: meta,
        statistics,
        events,
        sourceName: node.sourceName,
        totalFlightsClimbed: parseNumber(node.totalFlightsClimbed),
        totalElevationGain: parseNumber(node.totalElevationGain),
        totalElevationGainUnit: node.totalElevationGainUnit,
      };
    })
    .filter(Boolean)
    .sort((a, b) => {
      const dateA = a.startDate ? new Date(a.startDate).getTime() : 0;
      const dateB = b.startDate ? new Date(b.startDate).getTime() : 0;
      return dateB - dateA;
    });
}

function normalizeMetadata(metadata) {
  const entries = Array.isArray(metadata)
    ? metadata
    : metadata
    ? [metadata]
    : [];

  return entries.reduce((acc, entry) => {
    if (entry && entry.key) {
      acc[entry.key] = entry.value ?? entry.doubleValue ?? entry.intValue ?? entry.stringValue ?? null;
    }
    return acc;
  }, {});
}

function normalizeStatistics(statistics) {
  const statsArray = Array.isArray(statistics)
    ? statistics
    : statistics
    ? [statistics]
    : [];

  return statsArray.map((stat) => ({
    type: stat?.type,
    unit: stat?.unit,
    sum: parseNumber(stat?.sum),
    minimum: parseNumber(stat?.minimum),
    maximum: parseNumber(stat?.maximum),
    average: parseNumber(stat?.average),
  }));
}

function normalizeEvents(events) {
  const eventsArray = Array.isArray(events) ? events : events ? [events] : [];
  return eventsArray.map((event) => ({
    type: event?.type,
    startDate: event?.date ? new Date(event.date).toISOString() : null,
    duration: normalizeDuration(event?.duration, event?.durationUnit),
  }));
}

function normalizeDuration(durationValue, unit) {
  if (durationValue == null) {
    return 0;
  }
  const value = parseNumber(durationValue) || 0;
  if (!unit) {
    // Apple export uses minutes by default when the unit is missing
    return value * 60;
  }

  const normalizedUnit = unit.toLowerCase();
  if (normalizedUnit.startsWith('min')) {
    return value * 60;
  }
  if (normalizedUnit.startsWith('hr') || normalizedUnit.startsWith('hour')) {
    return value * 3600;
  }
  if (normalizedUnit.startsWith('sec')) {
    return value;
  }
  return value;
}

function parseQuantity(value, unit) {
  const numeric = parseNumber(value);
  if (numeric == null) {
    return null;
  }
  return {
    value: numeric,
    unit: unit || null,
  };
}

function parseNumber(value) {
  if (value == null) {
    return null;
  }
  const parsed = Number.parseFloat(value);
  return Number.isNaN(parsed) ? null : parsed;
}

function parseDate(value, metadata) {
  if (!value) {
    return null;
  }

  const tz = metadata?.HKTimeZone ?? metadata?.HKMetadataKeyTimeZone ?? undefined;
  if (tz) {
    return dayjs.tz(value, tz).toDate();
  }
  return new Date(value);
}

function formatDuration(totalSeconds) {
  const seconds = Math.max(0, Math.round(totalSeconds));
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${secs
      .toString()
      .padStart(2, '0')}`;
  }
  return `${minutes}:${secs.toString().padStart(2, '0')}`;
}

function buildSummary(workouts) {
  if (!Array.isArray(workouts) || workouts.length === 0) {
    return {
      totalCount: 0,
      totalEnergy: 0,
      totalEnergyUnit: 'kcal',
      totalDurationSeconds: 0,
      totalDistance: 0,
      totalDistanceUnit: 'km',
    };
  }

  return workouts.reduce(
    (acc, workout) => {
      acc.totalCount += 1;
      if (workout.energy?.value) {
        acc.totalEnergy += workout.energy.value;
        acc.totalEnergyUnit = workout.energy.unit || acc.totalEnergyUnit;
      }
      if (workout.distance?.value) {
        acc.totalDistance += workout.distance.value;
        acc.totalDistanceUnit = workout.distance.unit || acc.totalDistanceUnit;
      }
      acc.totalDurationSeconds += workout.durationSeconds || 0;
      return acc;
    },
    {
      totalCount: 0,
      totalEnergy: 0,
      totalEnergyUnit: 'kcal',
      totalDurationSeconds: 0,
      totalDistance: 0,
      totalDistanceUnit: 'km',
    }
  );
}

module.exports = app;
