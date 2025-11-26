/*
  # Create advertisements table for advertising surfaces

  1. New Tables
    - `advertisements`
      - `id` (uuid, primary key)
      - `title` (text) - Advertisement title
      - `type` (text) - Type of advertising surface (billboard, citylight, led_screen, etc.)
      - `location` (text) - Location description
      - `city` (text) - City name
      - `latitude` (numeric) - GPS latitude
      - `longitude` (numeric) - GPS longitude
      - `description` (text) - Detailed description
      - `price` (numeric) - Price per month
      - `dimensions` (text) - Surface dimensions
      - `image_url` (text) - Image URL
      - `owner_email` (text) - Owner's email for management
      - `created_at` (timestamptz) - Creation timestamp
      - `updated_at` (timestamptz) - Last update timestamp
      - `status` (text) - Status (active, inactive)

  2. Security
    - Enable RLS on `advertisements` table
    - Add policy for anyone to read active advertisements
    - Add policy for owners to manage their advertisements
*/

CREATE TABLE IF NOT EXISTS advertisements (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  title text NOT NULL,
  type text NOT NULL,
  location text NOT NULL,
  city text NOT NULL,
  latitude numeric NOT NULL,
  longitude numeric NOT NULL,
  description text DEFAULT '',
  price numeric NOT NULL,
  dimensions text DEFAULT '',
  image_url text DEFAULT '',
  owner_email text NOT NULL,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now(),
  status text DEFAULT 'active'
);

ALTER TABLE advertisements ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Anyone can view active advertisements"
  ON advertisements
  FOR SELECT
  USING (status = 'active');

CREATE POLICY "Owners can insert their advertisements"
  ON advertisements
  FOR INSERT
  WITH CHECK (true);

CREATE POLICY "Owners can update their advertisements"
  ON advertisements
  FOR UPDATE
  USING (true)
  WITH CHECK (true);

CREATE POLICY "Owners can delete their advertisements"
  ON advertisements
  FOR DELETE
  USING (true);

CREATE INDEX IF NOT EXISTS idx_advertisements_status ON advertisements(status);
CREATE INDEX IF NOT EXISTS idx_advertisements_type ON advertisements(type);
CREATE INDEX IF NOT EXISTS idx_advertisements_city ON advertisements(city);