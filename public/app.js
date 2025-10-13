import { ICON_ASSETS } from './iconAssets.js';

const fileInput = document.getElementById('workoutFile');
const workoutsContainer = document.getElementById('workouts');
const summarySection = document.getElementById('summary');
const summaryValues = {
  count: summarySection.querySelector('[data-summary="count"]'),
  duration: summarySection.querySelector('[data-summary="duration"]'),
  energy: summarySection.querySelector('[data-summary="energy"]'),
  distance: summarySection.querySelector('[data-summary="distance"]'),
};
const uploadStatus = document.getElementById('uploadStatus');
const uploadSymbolImage = document.querySelector('[data-icon-symbol]');
const filters = Array.from(document.querySelectorAll('.segmented__item'));

const dateFormatter = new Intl.DateTimeFormat('pl-PL', {
  month: 'long',
  year: 'numeric',
});
const dayFormatter = new Intl.DateTimeFormat('pl-PL', {
  weekday: 'long',
});
const dateTimeFormatter = new Intl.DateTimeFormat('pl-PL', {
  hour: '2-digit',
  minute: '2-digit',
});
const fullDateFormatter = new Intl.DateTimeFormat('pl-PL', {
  day: 'numeric',
  month: 'short',
});

const FALLBACK_SYMBOL = 'bolt.circle.fill';
const iconLibrary = {
  '💪': 'bolt.circle.fill',
  '🏈': 'sportscourt.fill',
  '🏹': 'scope',
  '🏉': 'sportscourt.fill',
  '🏸': 'sportscourt',
  '🩰': 'sparkles',
  '⚾': 'sportscourt.fill',
  '🏀': 'sportscourt.fill',
  '🎳': 'sportscourt',
  '🥊': 'waveform.path.ecg',
  '💃': 'music.note',
  '🧗': 'flag.circle.fill',
  '🧊': 'snow',
  '🧘': 'leaf.arrow.circlepath',
  '🏏': 'sportscourt',
  '🎿': 'snow',
  '🏋️': 'bolt.circle.fill',
  '🥌': 'snow',
  '🚴': 'wind',
  '🕺': 'music.note',
  '🥏': 'sportscourt',
  '⛷️': 'snow',
  '🐎': 'hare.fill',
  '🤺': 'scope',
  '🎣': 'drop.triangle.fill',
  '🎮': 'gamecontroller.fill',
  '🤸': 'hare.fill',
  '⛳': 'flag.circle.fill',
  '♿': 'person.circle.fill',
  '🤾': 'sportscourt.fill',
  '⚡': 'bolt.fill',
  '🥾': 'flag.circle.fill',
  '🏒': 'sportscourt',
  '🎯': 'scope',
  '🪢': 'leaf.arrow.circlepath',
  '🥍': 'sportscourt',
  '🥋': 'bolt.circle.fill',
  '❤️': 'heart.fill',
  '🔥': 'flame.fill',
  '⭐': 'sparkles',
  '🛶': 'drop.triangle.fill',
  '🥒': 'leaf.arrow.circlepath',
  '🎲': 'gamecontroller.fill',
  '🎾': 'sportscourt',
  '🚣': 'drop.triangle.fill',
  '🏃': 'hare.fill',
  '⛵': 'wind',
  '⛸️': 'snow',
  '🏂': 'snow',
  '⚽': 'sportscourt.fill',
  '🥎': 'sportscourt',
  '🚶': 'tortoise.fill',
  '🧎': 'leaf.arrow.circlepath',
  '🏄': 'drop.triangle.fill',
  '🏊': 'drop.triangle.fill',
  '🏓': 'sportscourt',
  '🤿': 'drop.triangle.fill',
  '🏐': 'sportscourt',
  '🤽': 'drop.triangle.fill',
  '🤼': 'bolt.circle.fill',
};

const SYMBOL_NAME_PATTERN = /^[a-z0-9.]+$/i;

/**
 * Resolves a usable SF Symbol asset name from provided candidates.
 *
 * @param {...string|undefined|null} candidates Potential symbol identifiers or emoji fallbacks.
 * @returns {string} Chosen symbol identifier.
 */
function resolveSymbol(...candidates) {
  for (const candidate of candidates) {
    if (typeof candidate !== 'string') {
      continue;
    }

    const trimmed = candidate.trim();
    if (!trimmed) {
      continue;
    }

    if (iconLibrary[trimmed]) {
      return iconLibrary[trimmed];
    }

    if (SYMBOL_NAME_PATTERN.test(trimmed)) {
      return trimmed;
    }
  }

  return FALLBACK_SYMBOL;
}

/**
 * Resolves a data URI for a given symbol, falling back to the default icon when necessary.
 *
 * @param {string} symbol Symbol identifier.
 * @returns {string} Base64 encoded data URI ready to be used as an image source.
 */
function getIconDataUri(symbol) {
  if (ICON_ASSETS[symbol]) {
    return ICON_ASSETS[symbol];
  }

  if (ICON_ASSETS[FALLBACK_SYMBOL]) {
    return ICON_ASSETS[FALLBACK_SYMBOL];
  }

  return '';
}

/**
 * Applies an icon image element into the provided container.
 *
 * @param {HTMLElement} container Target element to host the image.
 * @param {string} symbol Symbol identifier to render.
 * @param {string|undefined} label Accessible label describing the workout.
 * @returns {void}
 */
function setIconImage(container, symbol, label) {
  const resolvedSymbol = resolveSymbol(symbol);
  container.innerHTML = '';

  const img = document.createElement('img');
  img.src = getIconDataUri(resolvedSymbol);
  img.alt = label ? `Ikona aktywności ${label}` : 'Ikona treningu';
  img.loading = 'lazy';
  img.decoding = 'async';
  img.classList.add('workout-card__icon-image');

  container.appendChild(img);
}

if (uploadSymbolImage) {
  uploadSymbolImage.src = getIconDataUri(
    uploadSymbolImage.dataset.iconSymbol || FALLBACK_SYMBOL,
  );
}

let workoutTypes = {};
let defaultType = {
  icon: '💪',
  iconSymbol: 'bolt.circle.fill',
  accentColor: '#30d158',
  iconBackground: 'rgba(48, 209, 88, 0.18)',
};
defaultType.iconSymbol = resolveSymbol(defaultType.iconSymbol, defaultType.icon);
let allWorkouts = [];
let activeFilter = 'all';

init();

/**
 * Initializes the client-side application by wiring event listeners and
 * loading the workout type configuration from the API.
 *
 * @returns {Promise<void>} Resolves once configuration has been loaded.
 */
async function init() {
  await loadWorkoutTypes();
  fileInput.addEventListener('change', handleUpload);
  filters.forEach((button) =>
    button.addEventListener('click', () => {
      filters.forEach((b) => b.classList.toggle('is-active', b === button));
      activeFilter = button.dataset.filter;
      renderWorkouts(filterWorkouts(allWorkouts));
    })
  );


  if(localStorage.getItem('payload')){
    const payload = JSON.parse(localStorage.getItem('payload'));
    allWorkouts = payload.workouts || [];
    if (allWorkouts.length) {
      renderWorkouts(filterWorkouts(allWorkouts));
      updateSummary(payload.stats);
      toggleSummary(true);
      setUploadStatus(`Załadowano ${allWorkouts.length} treningów.`);
    }
  }else{
      console.log('No payload in localStorage');
  }

}

/**
 * Fetches the workout type configuration to style rendered workouts.
 *
 * @returns {Promise<void>} Resolves when the configuration request completes.
 */
async function loadWorkoutTypes() {
  try {
    const response = await fetch('/api/workout-types');
    if (!response.ok) {
      throw new Error('Nie udało się pobrać konfiguracji.');
    }
    const payload = await response.json();
    workoutTypes = payload.workoutTypes || {};
    defaultType = payload.defaultType || defaultType;
    defaultType.iconSymbol = resolveSymbol(defaultType.iconSymbol, defaultType.icon);
    Object.values(workoutTypes).forEach((type) => {
      if (type && typeof type === 'object') {
        type.iconSymbol = resolveSymbol(type.iconSymbol, type.icon);
      }
    });
  } catch (error) {
    console.warn('Workout type configuration could not be loaded.', error);
  }
}

/**
 * Handles selection of an Apple Health export file and triggers upload.
 *
 * @param {Event} event Change event emitted by the file input.
 * @returns {Promise<void>} Resolves after the upload flow completes.
 */
async function handleUpload(event) {
  const file = event.target.files?.[0];
  if (!file) {
    return;
  }

  setUploadStatus(`Przesyłanie pliku: ${file.name}`);
  toggleSummary(false);
  showLoadingState();

  try {
    const formData = new FormData();
    formData.append('workoutFile', file);

    const response = await fetch('/api/upload', {
      method: 'POST',
      body: formData,
    });

    if (!response.ok) {
      const message = await response.json().catch(() => ({ error: 'Nie udało się przetworzyć pliku.' }));
      throw new Error(message.error || 'Nie udało się przetworzyć pliku.');
    }

    const payload = await response.json();
    allWorkouts = payload.workouts || [];


    localStorage.setItem('payload', JSON.stringify(payload));

    if (!allWorkouts.length) {
      setUploadStatus('Nie znaleziono treningów w tym eksporcie.');
      renderWorkouts([]);
      toggleSummary(false);
      return;
    }

    renderWorkouts(filterWorkouts(allWorkouts));
    updateSummary(payload.stats);
    toggleSummary(true);
    setUploadStatus(`Załadowano ${allWorkouts.length} treningów.`);
  } catch (error) {
    console.error(error);
    setUploadStatus(error.message || 'Nieznany błąd.');
    renderWorkouts([]);
    toggleSummary(false);
  } finally {
    fileInput.value = '';
  }
}

/**
 * Applies the currently selected filter to the workout collection.
 *
 * @param {Array<object>} workouts Normalized workout list.
 * @returns {Array<object>} Filtered workouts matching the active filter.
 */
function filterWorkouts(workouts) {
  if (activeFilter === 'all') {
    return workouts;
  }

  if (activeFilter === 'mind') {
    return workouts.filter((workout) => workout.activityKey?.toLowerCase().includes('mind'));
  }

  if (activeFilter === 'cycling') {
    return workouts.filter((workout) => workout.activityKey?.toLowerCase().includes('cycle'));
  }

  return workouts.filter((workout) => !workout.activityKey || !workout.activityKey.toLowerCase().includes('mind'));
}

/**
 * Renders workout cards grouped by month and day in the DOM container.
 *
 * @param {Array<object>} workouts Collection of workouts to present.
 * @returns {void}
 */
function renderWorkouts(workouts) {
  workoutsContainer.innerHTML = '';

  if (!workouts.length) {
    workoutsContainer.innerHTML = `
      <div class="empty-state">
        <h2>Brak danych treningowych</h2>
        <p>Załaduj plik exportu, aby zobaczyć listę treningów.</p>
      </div>
    `;
    return;
  }

  const groups = groupByMonth(workouts);
  for (const { key, month, items } of groups) {
    const monthBlock = document.createElement('section');
    monthBlock.className = 'workout-month';

    const title = document.createElement('div');
    title.className = 'workout-month__title';
    title.textContent = month;
    monthBlock.appendChild(title);

    const dayGroups = groupByDay(items);
    for (const { dayKey, label, items: dayItems } of dayGroups) {
      const dayBlock = document.createElement('div');
      dayBlock.className = 'workout-day';

      const heading = document.createElement('div');
      heading.className = 'workout-day__heading';
      heading.innerHTML = `<span>${label.weekday}</span> <span>${label.date}</span>`;
      dayBlock.appendChild(heading);

      dayItems.forEach((workout) => {
        const card = createWorkoutCard(workout);
        dayBlock.appendChild(card);
      });

      monthBlock.appendChild(dayBlock);
    }

    workoutsContainer.appendChild(monthBlock);
  }
}

/**
 * Groups workouts by month and returns ordered grouping metadata.
 *
 * @param {Array<object>} workouts Collection of workouts to group.
 * @returns {Array<{ key: string, month: string, items: Array<object> }>} Month group descriptors.
 */
function groupByMonth(workouts) {
  const map = new Map();
  workouts.forEach((workout) => {
    const date = workout.startDate ? new Date(workout.startDate) : null;
    const key = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}` : 'unknown';
    if (!map.has(key)) {
      map.set(key, []);
    }
    map.get(key).push(workout);
  });

  return Array.from(map.entries())
    .sort(([a], [b]) => (a < b ? 1 : -1))
    .map(([key, items]) => {
      const reference = items[0]?.startDate ? new Date(items[0].startDate) : null;
      return {
        key,
        month: reference ? capitalize(dateFormatter.format(reference)) : 'Bez daty',
        items,
      };
    });
}

/**
 * Groups workouts for a single month into day buckets.
 *
 * @param {Array<object>} workouts Workouts that belong to the same month.
 * @returns {Array<{ dayKey: string, label: { weekday: string, date: string }, items: Array<object> }>} Day group data.
 */
function groupByDay(workouts) {
  const map = new Map();
  workouts.forEach((workout) => {
    const date = workout.startDate ? new Date(workout.startDate) : null;
    const key = date ? date.toISOString().split('T')[0] : 'unknown';
    if (!map.has(key)) {
      map.set(key, []);
    }
    map.get(key).push(workout);
  });

  return Array.from(map.entries())
    .sort(([a], [b]) => (a < b ? 1 : -1))
    .map(([dayKey, items]) => {
      const reference = items[0]?.startDate ? new Date(items[0].startDate) : null;
      return {
        dayKey,
        label: {
          weekday: reference ? capitalize(dayFormatter.format(reference)) : 'Bez daty',
          date: reference ? fullDateFormatter.format(reference) : '',
        },
        items,
      };
    });
}

/**
 * Creates a DOM node representing a single workout card styled like Apple Fitness.
 *
 * @param {object} workout Normalized workout data.
 * @returns {HTMLElement} Rendered workout card element.
 */
function createWorkoutCard(workout) {
  const template = document.getElementById('workout-card-template');
  const card = template.content.firstElementChild.cloneNode(true);

  const config = workoutTypes[workout.activityKey] || workout;
  const iconEl = card.querySelector('.workout-card__icon');
  const symbol = resolveSymbol(
    config.iconSymbol,
    config.icon,
    workout.iconSymbol,
    workout.icon,
    defaultType.iconSymbol,
    defaultType.icon
  );
  setIconImage(iconEl, symbol, workout.label || config.label);
  iconEl.style.background = config.iconBackground || workout.iconBackground || defaultType.iconBackground;

  const title = card.querySelector('.workout-card__title');
  title.textContent = workout.label || config.label || formatActivityKey(workout.activityKey);

  const meta = card.querySelector('.workout-card__meta');
  const metaBits = [];
  if (workout.startDate) {
    const start = new Date(workout.startDate);
    metaBits.push(dateTimeFormatter.format(start));
  }
  if (workout.metadata?.HKWorkoutGoalType) {
    metaBits.push(formatGoal(workout.metadata.HKWorkoutGoalType));
  }
  if (workout.sourceName) {
    metaBits.push(workout.sourceName);
  }
  meta.textContent = metaBits.join(' • ');

  const details = card.querySelector('.workout-card__details');
  const detailItems = [];
  if (workout.metadata?.HKOutdoorWorkoutRoute) {
    detailItems.push('Outdoor');
  }
  if (workout.metadata?.HKIndoorWorkout) {
    detailItems.push('Indoor');
  }
  if (workout.totalFlightsClimbed) {
    detailItems.push(`${workout.totalFlightsClimbed} pięter`);
  }
  if (workout.totalElevationGain && workout.totalElevationGainUnit) {
    detailItems.push(`Wzniesienie ${workout.totalElevationGain}${workout.totalElevationGainUnit}`);
  }
  if (workout.metadata?.HKWeatherCondition) {
    detailItems.push(`Pogoda: ${workout.metadata.HKWeatherCondition}`);
  }
  details.textContent = detailItems.join(' • ');

  const metrics = card.querySelector('.workout-card__metrics');
  metrics.innerHTML = '';

  metrics.appendChild(createMetricPill(workout.durationText || '0:00', 'metric-pill metric-pill--duration'));

  if (workout.energy?.value) {
    const value = formatNumber(workout.energy.value);
    metrics.appendChild(createMetricPill(`${value} ${workout.energy.unit || 'kcal'}`, 'metric-pill metric-pill--calories'));
  }

  if (workout.distance?.value) {
    const value = formatNumber(workout.distance.value);
    metrics.appendChild(createMetricPill(`${value} ${workout.distance.unit || 'km'}`, 'metric-pill metric-pill--distance'));
  }

  return card;
}

/**
 * Creates a metric pill element displaying workout statistics.
 *
 * @param {string} text Text content to display inside the pill.
 * @param {string} className CSS classes applied to the pill element.
 * @returns {HTMLDivElement} Configured pill element.
 */
function createMetricPill(text, className) {
  const pill = document.createElement('div');
  pill.className = className;
  pill.textContent = text;
  return pill;
}

/**
 * Renders aggregated workout statistics in the summary header.
 *
 * @param {{ totalCount: number, totalDurationSeconds: number, totalEnergy: number, totalEnergyUnit?: string, totalDistance: number, totalDistanceUnit?: string }} stats
 * Aggregated statistics returned by the backend.
 * @returns {void}
 */
function updateSummary(stats) {
  if (!stats) {
    return;
  }

  summaryValues.count.textContent = stats.totalCount;
  summaryValues.duration.textContent = formatSummaryDuration(stats.totalDurationSeconds);
  summaryValues.energy.textContent = `${formatNumber(stats.totalEnergy)} ${stats.totalEnergyUnit || 'kcal'}`;
  summaryValues.distance.textContent = `${formatNumber(stats.totalDistance)} ${stats.totalDistanceUnit || 'km'}`;
}

/**
 * Converts total workout seconds into a concise summary string.
 *
 * @param {number} seconds Total duration expressed in seconds.
 * @returns {string} Human-readable summary duration.
 */
function formatSummaryDuration(seconds) {
  const total = Math.max(0, Math.round(seconds));
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  return hours ? `${hours}h ${minutes}m` : `${minutes}m`;
}

/**
 * Formats numeric values according to Polish locale conventions.
 *
 * @param {number} value Number to format.
 * @returns {string} Localized number string.
 */
function formatNumber(value) {
  return new Intl.NumberFormat('pl-PL', {
    maximumFractionDigits: value >= 10 ? 1 : 2,
  }).format(value || 0);
}

/**
 * Maps Apple Health goal codes to localized descriptions.
 *
 * @param {string} goalType Raw goal identifier.
 * @returns {string} Localized goal description.
 */
function formatGoal(goalType) {
  if (!goalType) return '';
  const normalized = goalType.toLowerCase();
  if (normalized.includes('open')) return 'Cel: otwarty';
  if (normalized.includes('time')) return 'Cel: czas';
  if (normalized.includes('distance')) return 'Cel: dystans';
  if (normalized.includes('calorie')) return 'Cel: kalorie';
  return `Cel: ${goalType}`;
}

/**
 * Converts Apple Health workout activity keys into human-friendly labels.
 *
 * @param {string} key Raw activity key from the export.
 * @returns {string} Human-readable fallback label.
 */
function formatActivityKey(key) {
  if (!key) return 'Trening';
  return key.replace('HKWorkoutActivityType', '').replace(/([a-z])([A-Z])/g, '$1 $2');
}

/**
 * Updates the status banner shown above the workout grid.
 *
 * @param {string} message Status message to display.
 * @returns {void}
 */
function setUploadStatus(message) {
  uploadStatus.textContent = message;
}

/**
 * Shows or hides the workout summary header.
 *
 * @param {boolean} show When true the summary is visible.
 * @returns {void}
 */
function toggleSummary(show) {
  summarySection.classList.toggle('hidden', !show);
}

/**
 * Displays a placeholder state while workouts are being processed.
 *
 * @returns {void}
 */
function showLoadingState() {
  workoutsContainer.innerHTML = `
    <div class="empty-state">
      <h2>Przetwarzanie treningów…</h2>
      <p>To może chwilę potrwać w zależności od rozmiaru pliku.</p>
    </div>
  `;
}

/**
 * Capitalizes the first letter of the provided string.
 *
 * @param {string} value String to capitalize.
 * @returns {string} Capitalized string.
 */
function capitalize(value) {
  if (!value) return value;
  return value.charAt(0).toUpperCase() + value.slice(1);
}
