const { workoutTypes, defaultType } = require('../config/workoutTypes');
const { createDateTimeProvider } = require('./DateTimeProvider');

const dayjs = createDateTimeProvider();

/**
 * Converts raw Apple Health workout nodes into normalized domain objects
 * ready for consumption by the client application.
 */
class WorkoutDataNormalizer {
  /**
   * Normalizes a collection of workout nodes exported from Apple Health.
   *
   * @param {Array<object>|object|undefined} workoutNodes Raw workout node(s) from the XML export.
   * @returns {Array<object>} Cleaned workout models sorted by start date descending.
   */
  static normalizeWorkouts(workoutNodes) {
    const nodesArray = Array.isArray(workoutNodes)
      ? workoutNodes
      : workoutNodes
      ? [workoutNodes]
      : [];

    return nodesArray
      .map((node, index) => this.normalizeWorkout(node, index))
      .filter(Boolean)
      .sort((a, b) => {
        const dateA = a.startDate ? new Date(a.startDate).getTime() : 0;
        const dateB = b.startDate ? new Date(b.startDate).getTime() : 0;
        return dateB - dateA;
      });
  }

  /**
   * Produces a normalized workout object from a single raw node.
   *
   * @param {object} node Raw workout node.
   * @param {number} index Index of the workout in the original collection.
   * @returns {object|null} Normalized workout or null when the node is invalid.
   */
  static normalizeWorkout(node, index) {
    if (!node || typeof node !== 'object') {
      return null;
    }

    const metadata = this.normalizeMetadata(node.MetadataEntry);
    const statistics = this.normalizeStatistics(node.WorkoutStatistics);
    const events = this.normalizeEvents(node.WorkoutEvent);

    const start = this.parseDate(node.startDate, metadata);
    const end = this.parseDate(node.endDate, metadata);

    const durationSeconds = this.normalizeDuration(node.duration, node.durationUnit);
    const durationText = this.formatDuration(durationSeconds);

    const distance = this.parseQuantity(
      node.totalDistance,
      node.totalDistanceUnit || metadata.HKQuantityTypeIdentifierDistanceWalkingRunningUnit
    );
    const energy = this.parseQuantity(node.totalEnergyBurned, node.totalEnergyBurnedUnit);
    const swim = this.parseQuantity(node.totalSwimmingStrokeCount, node.totalSwimmingStrokeCountUnit);

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
      metadata,
      statistics,
      events,
      sourceName: node.sourceName,
      totalFlightsClimbed: this.parseNumber(node.totalFlightsClimbed),
      totalElevationGain: this.parseNumber(node.totalElevationGain),
      totalElevationGainUnit: node.totalElevationGainUnit,
    };
  }

  /**
   * Normalizes metadata entries into a key-value map.
   *
   * @param {Array<object>|object|undefined} metadata Raw metadata entry or entries.
   * @returns {Record<string, string|number|null>} Parsed metadata keyed by entry name.
   */
  static normalizeMetadata(metadata) {
    const entries = Array.isArray(metadata) ? metadata : metadata ? [metadata] : [];
    return entries.reduce((acc, entry) => {
      if (entry && entry.key) {
        acc[entry.key] = entry.value ?? entry.doubleValue ?? entry.intValue ?? entry.stringValue ?? null;
      }
      return acc;
    }, {});
  }

  /**
   * Standardizes workout statistics blocks.
   *
   * @param {Array<object>|object|undefined} statistics Raw statistics block(s).
   * @returns {Array<object>} Normalized statistics describing workout aggregates.
   */
  static normalizeStatistics(statistics) {
    const statsArray = Array.isArray(statistics) ? statistics : statistics ? [statistics] : [];
    return statsArray.map((stat) => ({
      type: stat?.type,
      unit: stat?.unit,
      sum: this.parseNumber(stat?.sum),
      minimum: this.parseNumber(stat?.minimum),
      maximum: this.parseNumber(stat?.maximum),
      average: this.parseNumber(stat?.average),
    }));
  }

  /**
   * Normalizes workout events, retaining durations in seconds.
   *
   * @param {Array<object>|object|undefined} events Raw event node(s).
   * @returns {Array<object>} Simplified workout events with ISO date strings.
   */
  static normalizeEvents(events) {
    const eventsArray = Array.isArray(events) ? events : events ? [events] : [];
    return eventsArray.map((event) => ({
      type: event?.type,
      startDate: event?.date ? new Date(event.date).toISOString() : null,
      duration: this.normalizeDuration(event?.duration, event?.durationUnit),
    }));
  }

  /**
   * Converts Apple Health duration values to seconds.
   *
   * @param {string|number|null|undefined} durationValue Raw duration value as provided in the export.
   * @param {string|null|undefined} unit Unit descriptor, e.g. minutes or hours.
   * @returns {number} Duration expressed in seconds.
   */
  static normalizeDuration(durationValue, unit) {
    if (durationValue == null) {
      return 0;
    }
    const value = this.parseNumber(durationValue) || 0;
    if (!unit) {
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

  /**
   * Creates a structured measurement object when numeric values are present.
   *
   * @param {string|number|null|undefined} value The numeric value exported from Apple Health.
   * @param {string|null|undefined} unit The unit assigned to the numeric value.
   * @returns {{ value: number, unit: string|null }|null} Normalized measurement or null if missing.
   */
  static parseQuantity(value, unit) {
    const numeric = this.parseNumber(value);
    if (numeric == null) {
      return null;
    }
    return {
      value: numeric,
      unit: unit || null,
    };
  }

  /**
   * Parses a numeric input into a floating-point number.
   *
   * @param {string|number|null|undefined} value Value to parse.
   * @returns {number|null} Parsed number or null when not numeric.
   */
  static parseNumber(value) {
    if (value == null) {
      return null;
    }
    const parsed = Number.parseFloat(value);
    return Number.isNaN(parsed) ? null : parsed;
  }

  /**
   * Converts Apple Health date strings to JavaScript Date instances.
   *
   * @param {string|null|undefined} value Date string from the export.
   * @param {Record<string, string|number|null>} metadata Metadata associated with the workout.
   * @returns {Date|null} JavaScript Date respecting timezone metadata when available.
   */
  static parseDate(value, metadata) {
    if (!value) {
      return null;
    }

    const tz = metadata?.HKTimeZone ?? metadata?.HKMetadataKeyTimeZone ?? undefined;
    if (tz) {
      return dayjs.tz(value, tz).toDate();
    }
    return new Date(value);
  }

  /**
   * Formats a duration expressed in seconds to a human-readable clock string.
   *
   * @param {number} totalSeconds Duration in seconds.
   * @returns {string} Formatted duration string (HH:MM:SS or MM:SS).
   */
  static formatDuration(totalSeconds) {
    const seconds = Math.max(0, Math.round(totalSeconds));
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
      return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
  }
}

module.exports = {
  WorkoutDataNormalizer,
};
