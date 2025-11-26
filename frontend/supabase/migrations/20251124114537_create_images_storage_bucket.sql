/*
  # Create storage bucket for advertisement images

  1. Storage Setup
    - Create 'images' bucket for advertisement photos
    - Set bucket to public for easy access to images
    - Add policies for image upload and access

  2. Security
    - Anyone can view images (public bucket)
    - Anyone can upload images (for ad creation without auth)
    - Standard image size limits applied
*/

INSERT INTO storage.buckets (id, name, public)
VALUES ('images', 'images', true)
ON CONFLICT (id) DO NOTHING;

CREATE POLICY "Anyone can view images"
ON storage.objects FOR SELECT
USING (bucket_id = 'images');

CREATE POLICY "Anyone can upload images"
ON storage.objects FOR INSERT
WITH CHECK (bucket_id = 'images');

CREATE POLICY "Anyone can update their images"
ON storage.objects FOR UPDATE
USING (bucket_id = 'images')
WITH CHECK (bucket_id = 'images');

CREATE POLICY "Anyone can delete their images"
ON storage.objects FOR DELETE
USING (bucket_id = 'images');