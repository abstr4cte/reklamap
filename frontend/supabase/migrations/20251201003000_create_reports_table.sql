/*
  # Create reports table

  1. New Tables
    - `reports`
      - `id` (uuid, primary key)
      - `advertisement_id` (uuid, references advertisements)
      - `reason` (text)
      - `details` (text)
      - `created_at` (timestamp)
      - `status` (text) - pending, reviewed, resolved

  2. Security
    - Enable RLS on `reports` table
    - Add policy for public to insert reports
*/

CREATE TABLE IF NOT EXISTS reports (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  advertisement_id uuid REFERENCES advertisements(id) ON DELETE CASCADE,
  reason text NOT NULL,
  details text,
  created_at timestamptz DEFAULT now(),
  status text DEFAULT 'pending'
);

ALTER TABLE reports ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Anyone can create reports"
  ON reports
  FOR INSERT
  TO public
  WITH CHECK (true);
