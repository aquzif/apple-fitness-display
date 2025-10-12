const fs = require('fs');
const { XMLParser } = require('fast-xml-parser');
const sax = require('sax');

const SKIPPED_ELEMENTS = new Set(['WorkoutRoute']);

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
   * Streams a file from disk and extracts workout nodes without loading
   * the entire XML payload into memory. This prevents "Invalid string length"
   * errors for large Apple Health exports.
   *
   * @param {string} filePath Absolute path to the uploaded XML file on disk.
   * @param {import('fs').ReadStreamOptions} [readStreamOptions]
   *  Optional overrides for the underlying {@link fs.createReadStream} call.
   * @returns {Promise<Array<object>>} Promise resolving to the raw workout nodes.
   */
  async parseWorkoutsFromFile(filePath, readStreamOptions = {}) {
    return new Promise((resolve, reject) => {
      let settled = false;
      const resolveOnce = (value) => {
        if (settled) {
          return;
        }
        settled = true;
        resolve(value);
      };
      const rejectOnce = (error) => {
        if (settled) {
          return;
        }
        settled = true;
        reject(error);
      };

      const workouts = [];
      let currentWorkout = null;
      const stack = [];

      const stream = fs.createReadStream(filePath, {
        encoding: 'utf8',
        highWaterMark: 1024 * 1024,
        ...readStreamOptions,
      });

      const saxStream = sax.createStream(true, {
        trim: false,
        normalize: false,
      });

      saxStream.on('error', (error) => {
        stream.destroy(error);
        rejectOnce(error);
      });

      stream.on('error', rejectOnce);

      saxStream.on('opentag', (node) => {
        if (!currentWorkout) {
          if (node.name === 'Workout') {
            currentWorkout = { ...node.attributes };
            stack.push({ name: node.name, node: currentWorkout, skip: false });
          }
          return;
        }

        const parentFrame = stack[stack.length - 1];
        if (!parentFrame || parentFrame.skip) {
          stack.push({ name: node.name, node: null, skip: true });
          return;
        }

        const parent = parentFrame.node;
        const shouldSkip = SKIPPED_ELEMENTS.has(node.name);
        if (shouldSkip) {
          stack.push({ name: node.name, node: null, skip: true });
          return;
        }

        const element = { ...node.attributes };
        const key = node.name;

        if (parent[key]) {
          if (!Array.isArray(parent[key])) {
            parent[key] = [parent[key]];
          }
          parent[key].push(element);
        } else {
          parent[key] = element;
        }

        if (!node.isSelfClosing) {
          stack.push({ name: key, node: element, skip: false });
        }
      });

      saxStream.on('text', (text) => {
        if (!currentWorkout) {
          return;
        }

        const trimmed = text.trim();
        if (!trimmed) {
          return;
        }

        const target = stack[stack.length - 1];
        if (!target || !target.node || target.skip) {
          return;
        }

        const existing = target.node['#text'];
        target.node['#text'] = existing ? `${existing}${trimmed}` : trimmed;
      });

      saxStream.on('closetag', (tagName) => {
        if (!currentWorkout) {
          return;
        }

        const last = stack[stack.length - 1];
        if (last && last.name === tagName) {
          stack.pop();
        }

        if (tagName === 'Workout') {
          workouts.push(currentWorkout);
          currentWorkout = null;
          stack.length = 0;
        }
      });

      saxStream.on('end', () => {
        resolveOnce(workouts);
      });

      stream.pipe(saxStream);
    });
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
