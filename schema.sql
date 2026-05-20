-- Pokemon Game Save Slots Schema
-- Run this once to set up your database

CREATE DATABASE IF NOT EXISTS pokemon_game;
USE pokemon_game;

CREATE TABLE IF NOT EXISTS save_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_number INT NOT NULL UNIQUE CHECK (slot_number BETWEEN 1 AND 3),
    player_x FLOAT NOT NULL,
    player_y FLOAT NOT NULL,
    city_name VARCHAR(100) NOT NULL DEFAULT 'Pallet City',
    play_time_seconds INT NOT NULL DEFAULT 0,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert empty placeholder rows for slots 1-3
INSERT IGNORE INTO save_slots (slot_number, player_x, player_y, city_name, play_time_seconds)
VALUES
    (1, 0, 0, '', 0),
    (2, 0, 0, '', 0),
    (3, 0, 0, '', 0);

-- Mark empty slots with NULL to distinguish from actual saves
ALTER TABLE save_slots ADD COLUMN is_empty BOOLEAN DEFAULT TRUE;
UPDATE save_slots SET is_empty = TRUE;
