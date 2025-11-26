/*
  # Add additional fields for advertisement creation form

  1. Changes to `advertisements` table
    - Add `phone` (text) - Phone number (optional)
    - Add `contact_preference` (text) - Contact preference: email/phone/both
    - Add `available_from` (timestamptz) - Date when the ad space becomes available
    - Add `base_price_per_day` (numeric) - Base price always per day for calculations
    
  2. Important Notes
    - Using IF NOT EXISTS pattern to avoid errors
    - base_price_per_day will be the source of truth for price calculations
    - price field remains for backward compatibility (will store monthly equivalent)
*/

-- Add phone field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'phone'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN phone text DEFAULT '';
  END IF;
END $$;

-- Add contact_preference field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'contact_preference'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN contact_preference text DEFAULT 'email';
  END IF;
END $$;

-- Add available_from field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'available_from'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN available_from timestamptz DEFAULT now();
  END IF;
END $$;

-- Add base_price_per_day field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'base_price_per_day'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN base_price_per_day numeric DEFAULT 0;
  END IF;
END $$;