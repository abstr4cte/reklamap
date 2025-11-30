/*
  # Add images column to advertisements table

  1. Changes to `advertisements` table
    - Add `images` (text[]) - Array of image URLs
    
  2. Important Notes
    - Defaults to empty array
    - Used for storing multiple images and their order
*/

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'images'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN images text[] DEFAULT '{}';
  END IF;
END $$;
