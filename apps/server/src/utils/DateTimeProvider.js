const dayjs = require('dayjs');
const utc = require('dayjs/plugin/utc');
const timezone = require('dayjs/plugin/timezone');
const localizedFormat = require('dayjs/plugin/localizedFormat');

/**
 * Configures and exposes a shared Day.js instance with timezone awareness.
 * The instance is extended with UTC, timezone, and localized format plugins
 * to support parsing Apple Health timestamps with metadata-provided timezones.
 *
 * @returns {typeof dayjs} The configured Day.js factory function.
 */
function createDateTimeProvider() {
  dayjs.extend(utc);
  dayjs.extend(timezone);
  dayjs.extend(localizedFormat);
  return dayjs;
}

module.exports = {
  createDateTimeProvider,
};
