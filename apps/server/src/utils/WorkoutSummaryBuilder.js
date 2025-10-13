/**
 * Aggregates summary statistics for a collection of normalized workouts.
 */
class WorkoutSummaryBuilder {
  /**
   * Builds cumulative summary information for the provided workouts.
   *
   * @param {Array<object>} workouts Collection of normalized workout objects.
   * @returns {{ totalCount: number, totalEnergy: number, totalEnergyUnit: string, totalDurationSeconds: number, totalDistance: number, totalDistanceUnit: string }}
   * Aggregate statistics summarizing the provided workouts.
   */
  static buildSummary(workouts) {
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
}

module.exports = {
  WorkoutSummaryBuilder,
};
