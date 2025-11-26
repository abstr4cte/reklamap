/*
  # Add views counter and active status fields

  1. Changes to `advertisements` table
    - Add `views` (integer) - Number of times the ad was viewed
    - Add `is_active` (boolean) - Whether the ad is currently active/visible
    
  2. Important Notes
    - views defaults to 0 for new ads
    - is_active defaults to true for new ads
    - Using IF NOT EXISTS pattern to avoid errors on re-run
*/

-- Add views field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'views'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN views integer DEFAULT 0;
  END IF;
END $$;

-- Add is_active field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'is_active'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN is_active boolean DEFAULT true;
  END IF;
END $$;
