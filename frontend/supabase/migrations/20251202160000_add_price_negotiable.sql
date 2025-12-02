-- Add price_negotiable column to advertisements table
ALTER TABLE advertisements
ADD COLUMN price_negotiable BOOLEAN DEFAULT false;

-- Add comment to the column
COMMENT ON COLUMN advertisements.price_negotiable IS 'Indicates if the price is negotiable';
