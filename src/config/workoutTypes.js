const palette = {
  performance: {
    accentColor: "#ffd60a",
    iconBackground: "rgba(255, 214, 10, 0.18)",
  },
  strength: {
    accentColor: "#bf5af2",
    iconBackground: "rgba(191, 90, 242, 0.18)",
  },
  mindful: {
    accentColor: "#a87ffb",
    iconBackground: "rgba(168, 127, 251, 0.18)",
  },
  aqua: {
    accentColor: "#64d2ff",
    iconBackground: "rgba(100, 210, 255, 0.18)",
  },
  outdoor: {
    accentColor: "#30d158",
    iconBackground: "rgba(48, 209, 88, 0.18)",
  },
  intense: {
    accentColor: "#ff3b30",
    iconBackground: "rgba(255, 59, 48, 0.18)",
  },
  play: {
    accentColor: "#ff9f0a",
    iconBackground: "rgba(255, 159, 10, 0.18)",
  },
  neutral: {
    accentColor: "#8e8e93",
    iconBackground: "rgba(142, 142, 147, 0.18)",
  },
};

const defaultType = {
  label: "Workout",
  icon: "💪",
  ...palette.outdoor,
};

const workoutTypes = {
  HKWorkoutActivityTypeAmericanFootball: {
    label: "American Football",
    icon: "🏈",
    ...palette.play,
  },
  HKWorkoutActivityTypeArchery: {
    label: "Archery",
    icon: "🏹",
    ...palette.play,
  },
  HKWorkoutActivityTypeAustralianFootball: {
    label: "Australian Football",
    icon: "🏉",
    ...palette.play,
  },
  HKWorkoutActivityTypeBadminton: {
    label: "Badminton",
    icon: "🏸",
    ...palette.performance,
  },
  HKWorkoutActivityTypeBarre: {
    label: "Barre",
    icon: "🩰",
    ...palette.mindful,
  },
  HKWorkoutActivityTypeBaseball: {
    label: "Baseball",
    icon: "⚾",
    ...palette.play,
  },
  HKWorkoutActivityTypeBasketball: {
    label: "Basketball",
    icon: "🏀",
    ...palette.play,
  },
  HKWorkoutActivityTypeBowling: {
    label: "Bowling",
    icon: "🎳",
    ...palette.play,
  },
  HKWorkoutActivityTypeBoxing: {
    label: "Boxing",
    icon: "🥊",
    ...palette.intense,
  },
  HKWorkoutActivityTypeCardioDance: {
    label: "Cardio Dance",
    icon: "💃",
    ...palette.performance,
  },
  HKWorkoutActivityTypeClimbing: {
    label: "Climbing",
    icon: "🧗",
    ...palette.performance,
  },
  HKWorkoutActivityTypeCooldown: {
    label: "Cooldown",
    icon: "🧊",
    ...palette.neutral,
  },
  HKWorkoutActivityTypeCoreTraining: {
    label: "Core Training",
    icon: "🧘",
    ...palette.strength,
  },
  HKWorkoutActivityTypeCricket: {
    label: "Cricket",
    icon: "🏏",
    ...palette.play,
  },
  HKWorkoutActivityTypeCrossCountrySkiing: {
    label: "Cross-Country Skiing",
    icon: "🎿",
    ...palette.performance,
  },
  HKWorkoutActivityTypeCrossTraining: {
    label: "Cross Training",
    icon: "🏋️",
    ...palette.strength,
  },
  HKWorkoutActivityTypeCurling: {
    label: "Curling",
    icon: "🥌",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeCycling: {
    label: "Cycling",
    icon: "🚴",
    ...palette.performance,
  },
  HKWorkoutActivityTypeDance: {
    label: "Dance",
    icon: "🕺",
    ...palette.performance,
  },
  HKWorkoutActivityTypeDanceInspiredTraining: {
    label: "Dance Inspired",
    icon: "🩰",
    ...palette.performance,
  },
  HKWorkoutActivityTypeDiscSports: {
    label: "Disc Sports",
    icon: "🥏",
    ...palette.play,
  },
  HKWorkoutActivityTypeDownhillSkiing: {
    label: "Downhill Skiing",
    icon: "⛷️",
    ...palette.performance,
  },
  HKWorkoutActivityTypeElliptical: {
    label: "Elliptical",
    icon: "🚴",
    ...palette.performance,
  },
  HKWorkoutActivityTypeEquestrianSports: {
    label: "Equestrian",
    icon: "🐎",
    ...palette.play,
  },
  HKWorkoutActivityTypeFencing: {
    label: "Fencing",
    icon: "🤺",
    ...palette.play,
  },
  HKWorkoutActivityTypeFishing: {
    label: "Fishing",
    icon: "🎣",
    ...palette.outdoor,
  },
  HKWorkoutActivityTypeFitnessGaming: {
    label: "Fitness Gaming",
    icon: "🎮",
    ...palette.outdoor,
  },
  HKWorkoutActivityTypeFlexibility: {
    label: "Flexibility",
    icon: "🤸",
    ...palette.mindful,
  },
  HKWorkoutActivityTypeFunctionalStrengthTraining: {
    label: "Functional Strength",
    icon: "🏋️",
    ...palette.strength,
  },
  HKWorkoutActivityTypeGolf: {
    label: "Golf",
    icon: "⛳",
    ...palette.outdoor,
  },
  HKWorkoutActivityTypeGymnastics: {
    label: "Gymnastics",
    icon: "🤸",
    ...palette.performance,
  },
  HKWorkoutActivityTypeHandCycling: {
    label: "Hand Cycling",
    icon: "♿",
    ...palette.performance,
  },
  HKWorkoutActivityTypeHandball: {
    label: "Handball",
    icon: "🤾",
    ...palette.intense,
  },
  HKWorkoutActivityTypeHighIntensityIntervalTraining: {
    label: "HIIT",
    icon: "⚡",
    ...palette.intense,
  },
  HKWorkoutActivityTypeHiking: {
    label: "Hiking",
    icon: "🥾",
    ...palette.outdoor,
  },
  HKWorkoutActivityTypeHockey: {
    label: "Hockey",
    icon: "🏒",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeHunting: {
    label: "Hunting",
    icon: "🎯",
    ...palette.outdoor,
  },
  HKWorkoutActivityTypeIceHockey: {
    label: "Ice Hockey",
    icon: "🏒",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeJumpRope: {
    label: "Jump Rope",
    icon: "🪢",
    ...palette.intense,
  },
  HKWorkoutActivityTypeKickboxing: {
    label: "Kickboxing",
    icon: "🥊",
    ...palette.intense,
  },
  HKWorkoutActivityTypeLacrosse: {
    label: "Lacrosse",
    icon: "🥍",
    ...palette.play,
  },
  HKWorkoutActivityTypeMartialArts: {
    label: "Martial Arts",
    icon: "🥋",
    ...palette.intense,
  },
  HKWorkoutActivityTypeMindAndBody: {
    label: "Mind & Body",
    icon: "🧘",
    ...palette.mindful,
  },
  HKWorkoutActivityTypeMixedCardio: {
    label: "Mixed Cardio",
    icon: "❤️",
    ...palette.performance,
  },
  HKWorkoutActivityTypeMixedMetabolicCardioTraining: {
    label: "Metabolic Cardio",
    icon: "🔥",
    ...palette.intense,
  },
  HKWorkoutActivityTypeOther: {
    label: "Other",
    icon: "⭐",
    ...palette.neutral,
  },
  HKWorkoutActivityTypePaddleSports: {
    label: "Paddle Sports",
    icon: "🛶",
    ...palette.aqua,
  },
  HKWorkoutActivityTypePickleball: {
    label: "Pickleball",
    icon: "🥒",
    ...palette.play,
  },
  HKWorkoutActivityTypePilates: {
    label: "Pilates",
    icon: "🧘",
    ...palette.mindful,
  },
  HKWorkoutActivityTypePlay: {
    label: "Play",
    icon: "🎲",
    ...palette.play,
  },
  HKWorkoutActivityTypePreparationAndRecovery: {
    label: "Recovery",
    icon: "🧊",
    ...palette.neutral,
  },
  HKWorkoutActivityTypeRacquetball: {
    label: "Racquetball",
    icon: "🎾",
    ...palette.play,
  },
  HKWorkoutActivityTypeRowing: {
    label: "Rowing",
    icon: "🚣",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeRugby: {
    label: "Rugby",
    icon: "🏉",
    ...palette.play,
  },
  HKWorkoutActivityTypeRunning: {
    label: "Running",
    icon: "🏃",
    ...palette.performance,
  },
  HKWorkoutActivityTypeSailing: {
    label: "Sailing",
    icon: "⛵",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeSkatingSports: {
    label: "Skating",
    icon: "⛸️",
    ...palette.performance,
  },
  HKWorkoutActivityTypeSnowSports: {
    label: "Snow Sports",
    icon: "🏂",
    ...palette.performance,
  },
  HKWorkoutActivityTypeSnowboarding: {
    label: "Snowboarding",
    icon: "🏂",
    ...palette.performance,
  },
  HKWorkoutActivityTypeSoccer: {
    label: "Soccer",
    icon: "⚽",
    ...palette.outdoor,
  },
  HKWorkoutActivityTypeSoftball: {
    label: "Softball",
    icon: "🥎",
    ...palette.play,
  },
  HKWorkoutActivityTypeSquash: {
    label: "Squash",
    icon: "🎾",
    ...palette.play,
  },
  HKWorkoutActivityTypeStairClimbing: {
    label: "Stair Climbing",
    icon: "🧗",
    ...palette.performance,
  },
  HKWorkoutActivityTypeStairs: {
    label: "Stairs",
    icon: "🧗",
    ...palette.performance,
  },
  HKWorkoutActivityTypeStepTraining: {
    label: "Step Training",
    icon: "🚶",
    ...palette.outdoor,
  },
  HKWorkoutActivityTypeStrengthTraining: {
    label: "Strength Training",
    icon: "🏋️",
    ...palette.strength,
  },
  HKWorkoutActivityTypeStretching: {
    label: "Stretching",
    icon: "🧎",
    ...palette.mindful,
  },
  HKWorkoutActivityTypeSurfingSports: {
    label: "Surfing",
    icon: "🏄",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeSwimming: {
    label: "Swimming",
    icon: "🏊",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeTableTennis: {
    label: "Table Tennis",
    icon: "🏓",
    ...palette.play,
  },
  HKWorkoutActivityTypeTaiChi: {
    label: "Tai Chi",
    icon: "🧘",
    ...palette.mindful,
  },
  HKWorkoutActivityTypeTennis: {
    label: "Tennis",
    icon: "🎾",
    ...palette.play,
  },
  HKWorkoutActivityTypeTrackAndField: {
    label: "Track & Field",
    icon: "🏃",
    ...palette.performance,
  },
  HKWorkoutActivityTypeTraditionalStrengthTraining: {
    label: "Traditional Strength",
    icon: "🏋️",
    ...palette.strength,
  },
  HKWorkoutActivityTypeUnderwaterDiving: {
    label: "Diving",
    icon: "🤿",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeVolleyball: {
    label: "Volleyball",
    icon: "🏐",
    ...palette.play,
  },
  HKWorkoutActivityTypeWalking: {
    label: "Walking",
    icon: "🚶",
    ...palette.outdoor,
  },
  HKWorkoutActivityTypeWaterFitness: {
    label: "Water Fitness",
    icon: "🤽",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeWaterPolo: {
    label: "Water Polo",
    icon: "🤽",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeWaterSports: {
    label: "Water Sports",
    icon: "🛶",
    ...palette.aqua,
  },
  HKWorkoutActivityTypeWheelchairRunPace: {
    label: "Wheelchair Run",
    icon: "♿",
    ...palette.performance,
  },
  HKWorkoutActivityTypeWheelchairWalkPace: {
    label: "Wheelchair Walk",
    icon: "♿",
    ...palette.outdoor,
  },
  HKWorkoutActivityTypeWrestling: {
    label: "Wrestling",
    icon: "🤼",
    ...palette.intense,
  },
  HKWorkoutActivityTypeYoga: {
    label: "Yoga",
    icon: "🧘",
    ...palette.mindful,
  },
  HKWorkoutActivityTypeMindfulness: {
    label: "Mindfulness",
    icon: "🧘",
    ...palette.mindful,
  },
};

module.exports = { workoutTypes, defaultType };
