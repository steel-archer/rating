-- Country
INSERT INTO country (id, name) VALUES
  (1, 'Україна')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Town
INSERT INTO town (id, name, country_id) VALUES
  (1, 'Львів', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Player
INSERT INTO player (id, last_name, first_name, patronymic, town_id) VALUES
  (1, 'Сокульський', 'Богдан', 'Євгенович', 1),
  (2, 'Марков', 'Владислав', 'Олегович', 1)
ON DUPLICATE KEY UPDATE last_name = VALUES(last_name), first_name = VALUES(first_name), patronymic = VALUES(patronymic);

-- Team
INSERT INTO team (id, name, town_id) VALUES
  (1, 'Highlander', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Season
INSERT INTO season (id, name) VALUES
  (1, '2024-2025')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Team ↔ Player ↔ Season
INSERT INTO team_player (id, team_id, player_id, season_id, is_captain) VALUES
  (1, 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE team_id = VALUES(team_id), is_captain = VALUES(is_captain);

-- Venue
INSERT INTO venue (id, name, town_id) VALUES
  (1, 'Сага квіз', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Venue Representative
INSERT INTO venue_representative (id, venue_id, player_id) VALUES
  (1, 1, 2)
ON DUPLICATE KEY UPDATE player_id = VALUES(player_id);
