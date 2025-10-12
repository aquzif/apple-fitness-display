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

let workoutTypes = {};
let defaultType = { icon: '💪', accentColor: '#30d158', iconBackground: 'rgba(48, 209, 88, 0.18)' };
let allWorkouts = [];
let activeFilter = 'all';

init();

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
}

async function loadWorkoutTypes() {
  try {
    const response = await fetch('/api/workout-types');
    if (!response.ok) {
      throw new Error('Nie udało się pobrać konfiguracji.');
    }
    const payload = await response.json();
    workoutTypes = payload.workoutTypes || {};
    defaultType = payload.defaultType || defaultType;
  } catch (error) {
    console.warn('Workout type configuration could not be loaded.', error);
  }
}

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

function createWorkoutCard(workout) {
  const template = document.getElementById('workout-card-template');
  const card = template.content.firstElementChild.cloneNode(true);

  const config = workoutTypes[workout.activityKey] || workout;
  const iconEl = card.querySelector('.workout-card__icon');
  iconEl.textContent = config.icon || workout.icon || defaultType.icon;
  iconEl.style.color = config.accentColor || workout.accentColor || defaultType.accentColor;
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

function createMetricPill(text, className) {
  const pill = document.createElement('div');
  pill.className = className;
  pill.textContent = text;
  return pill;
}

function updateSummary(stats) {
  if (!stats) {
    return;
  }

  summaryValues.count.textContent = stats.totalCount;
  summaryValues.duration.textContent = formatSummaryDuration(stats.totalDurationSeconds);
  summaryValues.energy.textContent = `${formatNumber(stats.totalEnergy)} ${stats.totalEnergyUnit || 'kcal'}`;
  summaryValues.distance.textContent = `${formatNumber(stats.totalDistance)} ${stats.totalDistanceUnit || 'km'}`;
}

function formatSummaryDuration(seconds) {
  const total = Math.max(0, Math.round(seconds));
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  return hours ? `${hours}h ${minutes}m` : `${minutes}m`;
}

function formatNumber(value) {
  return new Intl.NumberFormat('pl-PL', {
    maximumFractionDigits: value >= 10 ? 1 : 2,
  }).format(value || 0);
}

function formatGoal(goalType) {
  if (!goalType) return '';
  const normalized = goalType.toLowerCase();
  if (normalized.includes('open')) return 'Cel: otwarty';
  if (normalized.includes('time')) return 'Cel: czas';
  if (normalized.includes('distance')) return 'Cel: dystans';
  if (normalized.includes('calorie')) return 'Cel: kalorie';
  return `Cel: ${goalType}`;
}

function formatActivityKey(key) {
  if (!key) return 'Trening';
  return key.replace('HKWorkoutActivityType', '').replace(/([a-z])([A-Z])/g, '$1 $2');
}

function setUploadStatus(message) {
  uploadStatus.textContent = message;
}

function toggleSummary(show) {
  summarySection.classList.toggle('hidden', !show);
}

function showLoadingState() {
  workoutsContainer.innerHTML = `
    <div class="empty-state">
      <h2>Przetwarzanie treningów…</h2>
      <p>To może chwilę potrwać w zależności od rozmiaru pliku.</p>
    </div>
  `;
}

function capitalize(value) {
  if (!value) return value;
  return value.charAt(0).toUpperCase() + value.slice(1);
}
