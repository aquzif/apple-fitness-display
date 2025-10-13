import { useEffect, useMemo, useRef, useState } from 'react';
import { ICON_ASSETS } from './data/iconAssets.js';

const FALLBACK_SYMBOL = 'bolt.circle.fill';
const FILTERS = [
  { id: 'all', label: 'Wszystkie' },
  { id: 'workout', label: 'Treningi' },
  { id: 'mind', label: 'Mindfulness' },
  { id: 'cycling', label: 'Kolarstwo' },
];

const DEFAULT_TYPE = {
  label: 'Workout',
  icon: '💪',
  iconSymbol: 'bolt.circle.fill',
  accentColor: '#30d158',
  iconBackground: 'rgba(48, 209, 88, 0.18)',
};

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

export default function App() {
  const fileInputRef = useRef(null);
  const [statusMessage, setStatusMessage] = useState('');
  const [workoutTypes, setWorkoutTypes] = useState({});
  const [defaultType, setDefaultType] = useState(resolveWorkoutType(DEFAULT_TYPE));
  const [workouts, setWorkouts] = useState([]);
  const [summary, setSummary] = useState(null);
  const [activeFilter, setActiveFilter] = useState('all');
  const [isSummaryVisible, setIsSummaryVisible] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    let cancelled = false;

    async function loadWorkoutTypes() {
      try {
        const response = await fetch('/api/workout-types');
        if (!response.ok) {
          throw new Error('Nie udało się pobrać konfiguracji.');
        }

        const payload = await response.json();
        if (cancelled) {
          return;
        }

        const resolvedDefault = resolveWorkoutType(payload.defaultType ?? DEFAULT_TYPE);
        const resolvedTypes = Object.entries(payload.workoutTypes ?? {}).reduce(
          (acc, [key, value]) => {
            if (!value || typeof value !== 'object') {
              return acc;
            }

            acc[key] = resolveWorkoutType(value);
            return acc;
          },
          {},
        );

        setDefaultType(resolvedDefault);
        setWorkoutTypes(resolvedTypes);
      } catch (error) {
        console.warn('Workout type configuration could not be loaded.', error);
      }
    }

    loadWorkoutTypes();

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    try {
      const stored = window.localStorage.getItem('payload');
      if (!stored) {
        return;
      }

      const payload = JSON.parse(stored);
      const storedWorkouts = Array.isArray(payload?.workouts) ? payload.workouts : [];

      if (!storedWorkouts.length) {
        return;
      }

      setWorkouts(storedWorkouts);
      setSummary(payload?.stats ?? null);
      setIsSummaryVisible(true);
      setStatusMessage(`Załadowano ${storedWorkouts.length} treningów.`);
    } catch (error) {
      console.warn('Could not restore workouts from storage.', error);
    }
  }, []);

  const filteredWorkouts = useMemo(
    () => filterWorkouts(workouts, activeFilter),
    [workouts, activeFilter],
  );

  const groupedWorkouts = useMemo(
    () => groupWorkouts(filteredWorkouts),
    [filteredWorkouts],
  );

  const handleFilterChange = (filterId) => {
    setActiveFilter(filterId);
  };

  const handleUpload = async (event) => {
    const file = event.target.files?.[0];
    if (!file) {
      return;
    }

    setStatusMessage(`Przesyłanie pliku: ${file.name}`);
    setIsSummaryVisible(false);
    setIsLoading(true);

    try {
      const formData = new FormData();
      formData.append('workoutFile', file);

      const response = await fetch('/api/upload', {
        method: 'POST',
        body: formData,
      });

      if (!response.ok) {
        const message = await response
          .json()
          .catch(() => ({ error: 'Nie udało się przetworzyć pliku.' }));
        throw new Error(message.error || 'Nie udało się przetworzyć pliku.');
      }

      const payload = await response.json();
      const uploadedWorkouts = Array.isArray(payload.workouts) ? payload.workouts : [];

      if (typeof window !== 'undefined') {
        window.localStorage.setItem('payload', JSON.stringify(payload));
      }

      if (!uploadedWorkouts.length) {
        setWorkouts([]);
        setSummary(null);
        setStatusMessage('Nie znaleziono treningów w tym eksporcie.');
        setIsSummaryVisible(false);
        return;
      }

      setWorkouts(uploadedWorkouts);
      setSummary(payload.stats ?? null);
      setIsSummaryVisible(true);
      setStatusMessage(`Załadowano ${uploadedWorkouts.length} treningów.`);
    } catch (error) {
      console.error(error);
      setWorkouts([]);
      setSummary(null);
      setIsSummaryVisible(false);
      setStatusMessage(error.message || 'Nieznany błąd.');

      if (typeof window !== 'undefined') {
        window.localStorage.removeItem('payload');
      }
    } finally {
      setIsLoading(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const uploadIcon = getIconDataUri('square.and.arrow.up');

  return (
    <main className="app-shell">
      <header className="app-header">
        <div className="header-meta">
          <div className="breadcrumbs">Sessions</div>
          <h1>Treningi</h1>
        </div>
        <div className="segmented" role="tablist" aria-label="Typ podglądu">
          {FILTERS.map((filter) => (
            <button
              key={filter.id}
              className={`segmented__item${filter.id === activeFilter ? ' is-active' : ''}`}
              data-filter={filter.id}
              role="tab"
              aria-selected={filter.id === activeFilter}
              type="button"
              onClick={() => handleFilterChange(filter.id)}
            >
              {filter.label}
            </button>
          ))}
        </div>
        <p className="header-hint">
          Wybierz export XML z Apple Health. Plik jest przetwarzany lokalnie na serwerze i nie jest nigdzie wysyłany.
        </p>
        <label className="upload-card" htmlFor="workoutFile">
          <input
            ref={fileInputRef}
            id="workoutFile"
            type="file"
            accept=".xml"
            onChange={handleUpload}
          />
          <div className="upload-card__icon" aria-hidden="true">
            <img alt="" src={uploadIcon} />
          </div>
          <div className="upload-card__body">
            <h2>Przeciągnij i upuść lub wybierz plik XML</h2>
            <p>Limit rozmiaru: 10 GB</p>
          </div>
        </label>
        <div id="uploadStatus" className="upload-status" aria-live="polite">
          {statusMessage}
        </div>
      </header>

      <Summary stats={summary} visible={isSummaryVisible} />

      <WorkoutList
        groups={groupedWorkouts}
        isLoading={isLoading}
        workoutTypes={workoutTypes}
        defaultType={defaultType}
      />
    </main>
  );
}

function Summary({ stats, visible }) {
  return (
    <section
      id="summary"
      className={`summary${visible ? '' : ' hidden'}`}
      aria-live="polite"
    >
      <div className="summary__tile">
        <div className="summary__label">Łączna liczba</div>
        <div className="summary__value" data-summary="count">
          {stats?.totalCount ?? 0}
        </div>
      </div>
      <div className="summary__tile">
        <div className="summary__label">Czas</div>
        <div className="summary__value" data-summary="duration">
          {formatSummaryDuration(stats?.totalDurationSeconds ?? 0)}
        </div>
      </div>
      <div className="summary__tile">
        <div className="summary__label">Kalorie</div>
        <div className="summary__value" data-summary="energy">
          {`${formatNumber(stats?.totalEnergy ?? 0)} ${stats?.totalEnergyUnit || 'kcal'}`}
        </div>
      </div>
      <div className="summary__tile">
        <div className="summary__label">Dystans</div>
        <div className="summary__value" data-summary="distance">
          {`${formatNumber(stats?.totalDistance ?? 0)} ${stats?.totalDistanceUnit || 'km'}`}
        </div>
      </div>
    </section>
  );
}

function WorkoutList({ groups, isLoading, workoutTypes, defaultType }) {
  if (isLoading) {
    return (
      <section className="workouts" aria-live="polite">
        <div className="empty-state">
          <h2>Przetwarzanie treningów…</h2>
          <p>To może chwilę potrwać w zależności od rozmiaru pliku.</p>
        </div>
      </section>
    );
  }

  if (!groups.length) {
    return (
      <section className="workouts" aria-live="polite">
        <div className="empty-state">
          <h2>Brak danych treningowych</h2>
          <p>Załaduj plik exportu, aby zobaczyć listę treningów.</p>
        </div>
      </section>
    );
  }

  return (
    <section id="workouts" className="workouts" aria-live="polite">
      {groups.map((monthGroup) => (
        <article key={monthGroup.key} className="workout-month">
          <div className="workout-month__title">{monthGroup.month}</div>
          {monthGroup.days.map((day) => (
            <div key={day.dayKey} className="workout-day">
              <div className="workout-day__heading">
                <span>{day.label.weekday}</span>
                <span>{day.label.date}</span>
              </div>
              {day.items.map((workout) => (
                <WorkoutCard
                  key={workout.uuid ?? `${workout.activityKey}-${workout.startDate}`}
                  workout={workout}
                  workoutTypes={workoutTypes}
                  defaultType={defaultType}
                />
              ))}
            </div>
          ))}
        </article>
      ))}
    </section>
  );
}

function WorkoutCard({ workout, workoutTypes, defaultType }) {
  const config = workoutTypes[workout.activityKey] ?? workout;
  const symbol = resolveSymbol(
    config?.iconSymbol,
    config?.icon,
    workout.iconSymbol,
    workout.icon,
    defaultType.iconSymbol,
    defaultType.icon,
  );

  const iconBackground = config?.iconBackground ?? workout.iconBackground ?? defaultType.iconBackground;
  const title = workout.label || config?.label || formatActivityKey(workout.activityKey);
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

  const details = [];
  if (workout.metadata?.HKOutdoorWorkoutRoute) {
    details.push('Outdoor');
  }
  if (workout.metadata?.HKIndoorWorkout) {
    details.push('Indoor');
  }
  if (workout.totalFlightsClimbed) {
    details.push(`${workout.totalFlightsClimbed} pięter`);
  }
  if (workout.totalElevationGain && workout.totalElevationGainUnit) {
    details.push(`Wzniesienie ${workout.totalElevationGain}${workout.totalElevationGainUnit}`);
  }
  if (workout.metadata?.HKWeatherCondition) {
    details.push(`Pogoda: ${workout.metadata.HKWeatherCondition}`);
  }

  return (
    <article className="workout-card">
      <div className="workout-card__icon" style={{ background: iconBackground }}>
        <img
          className="workout-card__icon-image"
          src={getIconDataUri(symbol)}
          alt={title ? `Ikona aktywności ${title}` : 'Ikona treningu'}
          loading="lazy"
          decoding="async"
        />
      </div>
      <div className="workout-card__content">
        <div className="workout-card__header">
          <h3 className="workout-card__title">{title}</h3>
          <div className="workout-card__meta">{metaBits.join(' • ')}</div>
        </div>
        <div className="workout-card__details">{details.join(' • ')}</div>
      </div>
      <div className="workout-card__metrics">
        <MetricPill className="metric-pill metric-pill--duration" text={workout.durationText || '0:00'} />
        {workout.energy?.value ? (
          <MetricPill
            className="metric-pill metric-pill--calories"
            text={`${formatNumber(workout.energy.value)} ${workout.energy.unit || 'kcal'}`}
          />
        ) : null}
        {workout.distance?.value ? (
          <MetricPill
            className="metric-pill metric-pill--distance"
            text={`${formatNumber(workout.distance.value)} ${workout.distance.unit || 'km'}`}
          />
        ) : null}
      </div>
    </article>
  );
}

function MetricPill({ className, text }) {
  return <div className={className}>{text}</div>;
}

function filterWorkouts(workouts, activeFilter) {
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

function groupWorkouts(workouts) {
  return groupByMonth(workouts).map((monthGroup) => ({
    ...monthGroup,
    days: groupByDay(monthGroup.items),
  }));
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

function getIconDataUri(symbol) {
  if (ICON_ASSETS[symbol]) {
    return ICON_ASSETS[symbol];
  }

  if (ICON_ASSETS[FALLBACK_SYMBOL]) {
    return ICON_ASSETS[FALLBACK_SYMBOL];
  }

  return '';
}

function resolveWorkoutType(type) {
  const base = !type || typeof type !== 'object' ? DEFAULT_TYPE : type;
  const merged = {
    ...DEFAULT_TYPE,
    ...base,
  };

  return {
    ...merged,
    iconSymbol: resolveSymbol(merged.iconSymbol, merged.icon),
  };
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

function capitalize(value) {
  if (!value) return value;
  return value.charAt(0).toUpperCase() + value.slice(1);
}
