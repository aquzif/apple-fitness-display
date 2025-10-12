const { XMLParser } = require('fast-xml-parser');

/**
 * Provides XML parsing utilities tailored to Apple Health export files.
 */
class WorkoutXmlParser {
  /**
   * Creates a new parser instance with sensible defaults for Apple Health data.
   *
   * @param {import('fast-xml-parser').X2jOptionsOptional} [options] Optional overrides for the XML parser.
   */
  constructor(options = {}) {
    this.parser = new XMLParser({
      ignoreAttributes: false,
      attributeNamePrefix: '',
      allowBooleanAttributes: true,
      trimValues: true,
      ...options,
    });
  }

  /**
   * Parses an XML payload into a JavaScript object.
   *
   * @param {string} xmlPayload Raw XML string exported from Apple Health.
   * @returns {object} Parsed XML document represented as nested objects.
   */
  parseFromString(xmlPayload) {
    return this.parser.parse(xmlPayload);
  }

  /**
   * Extracts workout nodes from a parsed Apple Health document.
   *
   * @param {object} parsedDocument Parsed output produced by {@link parseFromString}.
   * @returns {Array<object>|object|undefined} The workout node collection, if present.
   */
  extractWorkoutNodes(parsedDocument) {
    return parsedDocument?.HealthData?.Workout;
  }
}

module.exports = {
  WorkoutXmlParser,
};
