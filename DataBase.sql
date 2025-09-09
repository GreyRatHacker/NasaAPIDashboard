
CREATE TABLE `apod` (
  `date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `explanation` text NOT NULL,
  `media_type` varchar(20) NOT NULL,
  `nasa_url` text DEFAULT NULL,
  `local_path` varchar(255) DEFAULT NULL,
  `copyright` varchar(255) DEFAULT NULL,
  `download_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
CREATE TABLE `asteroids` (
  `neo_reference_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_hazardous` tinyint(1) NOT NULL,
  `diameter_min_m` decimal(10,2) NOT NULL,
  `diameter_max_m` decimal(10,2) NOT NULL,
  `close_approach_date` datetime NOT NULL,
  `miss_distance_km` decimal(20,2) NOT NULL,
  `nasa_jpl_url` text NOT NULL,
  `download_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `exoplanets_nearby` (
  `pl_name` varchar(100) NOT NULL,
  `hostname` varchar(100) NOT NULL,
  `sy_pnum` int(11) NOT NULL,
  `discoverymethod` varchar(100) DEFAULT NULL,
  `disc_year` int(11) DEFAULT NULL,
  `pl_rade` decimal(8,3) DEFAULT NULL,
  `sy_dist` decimal(10,3) DEFAULT NULL,
  `mass_earth` decimal(10,3) DEFAULT NULL,
  `density_gcc` decimal(10,3) DEFAULT NULL,
  `temp_kelvin` int(11) DEFAULT NULL,
  `orbital_period_days` decimal(12,5) DEFAULT NULL,
  `distance_from_star_au` decimal(10,5) DEFAULT NULL,
  `orbit_eccentricity` decimal(8,5) DEFAULT NULL,
  `star_spectral_type` varchar(50) DEFAULT NULL,
  `star_age_gyr` decimal(8,3) DEFAULT NULL,
  `atmosphere_signals` int(11) DEFAULT NULL,
  `star_radius_solar` decimal(8,3) DEFAULT NULL,
  `star_temp_k` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `mars_photos` (
  `id` int(11) NOT NULL,
  `rover_name` varchar(50) NOT NULL,
  `camera_name` varchar(50) NOT NULL,
  `camera_full_name` varchar(255) NOT NULL,
  `img_src_nasa` text NOT NULL,
  `local_path` varchar(255) NOT NULL,
  `sol` int(11) NOT NULL,
  `earth_date` date NOT NULL,
  `download_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
