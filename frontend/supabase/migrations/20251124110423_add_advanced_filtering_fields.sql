/*
  # Add advanced filtering fields to advertisements table

  1. Changes to `advertisements` table
    - Add `region` (text) - Województwo
    - Add `orientation` (text) - Orientacja: vertical/horizontal
    - Add `width` (numeric) - Szerokość w metrach
    - Add `height` (numeric) - Wysokość w metrach
    - Add `traffic_intensity` (text) - Natężenie ruchu: low/medium/high
    - Add `rental_period` (text) - Czas wynajmu: short_term/long_term
    - Add `price_unit` (text) - Jednostka ceny: day/week/month/year/sqm
    - Add `has_lighting` (boolean) - Podświetlenie
    - Add `has_image` (boolean) - Ma zdjęcie
    - Add `price_includes_print` (boolean) - Cena zawiera druk i montaż
    - Add `graphic_design_help` (boolean) - Pomoc przy projekcie graficznym
    - Add `offer_type` (text) - Rodzaj oferty: owner/agency
    - Add `has_vat_invoice` (boolean) - Faktura VAT

  2. Important Notes
    - Using IF NOT EXISTS pattern to avoid errors if columns already exist
    - Setting sensible default values for existing records
*/

-- Add region field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'region'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN region text DEFAULT '';
  END IF;
END $$;

-- Add orientation field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'orientation'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN orientation text DEFAULT 'horizontal';
  END IF;
END $$;

-- Add width field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'width'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN width numeric DEFAULT 0;
  END IF;
END $$;

-- Add height field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'height'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN height numeric DEFAULT 0;
  END IF;
END $$;

-- Add traffic_intensity field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'traffic_intensity'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN traffic_intensity text DEFAULT 'medium';
  END IF;
END $$;

-- Add rental_period field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'rental_period'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN rental_period text DEFAULT 'long_term';
  END IF;
END $$;

-- Add price_unit field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'price_unit'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN price_unit text DEFAULT 'month';
  END IF;
END $$;

-- Add has_lighting field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'has_lighting'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN has_lighting boolean DEFAULT false;
  END IF;
END $$;

-- Add has_image field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'has_image'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN has_image boolean DEFAULT true;
  END IF;
END $$;

-- Add price_includes_print field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'price_includes_print'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN price_includes_print boolean DEFAULT false;
  END IF;
END $$;

-- Add graphic_design_help field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'graphic_design_help'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN graphic_design_help boolean DEFAULT false;
  END IF;
END $$;

-- Add offer_type field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'offer_type'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN offer_type text DEFAULT 'owner';
  END IF;
END $$;

-- Add has_vat_invoice field
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'advertisements' AND column_name = 'has_vat_invoice'
  ) THEN
    ALTER TABLE advertisements ADD COLUMN has_vat_invoice boolean DEFAULT false;
  END IF;
END $$;

-- Create indexes for commonly filtered fields
CREATE INDEX IF NOT EXISTS idx_advertisements_region ON advertisements(region);
CREATE INDEX IF NOT EXISTS idx_advertisements_orientation ON advertisements(orientation);
CREATE INDEX IF NOT EXISTS idx_advertisements_traffic_intensity ON advertisements(traffic_intensity);
CREATE INDEX IF NOT EXISTS idx_advertisements_rental_period ON advertisements(rental_period);
CREATE INDEX IF NOT EXISTS idx_advertisements_price_unit ON advertisements(price_unit);
CREATE INDEX IF NOT EXISTS idx_advertisements_offer_type ON advertisements(offer_type);