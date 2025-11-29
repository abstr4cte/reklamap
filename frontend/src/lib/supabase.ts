import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

export const supabase = createClient(supabaseUrl, supabaseAnonKey)

export interface Advertisement {
  id: string
  title: string
  type: string
  location: string
  city: string
  latitude: number
  longitude: number
  description: string
  price: number
  dimensions: string
  image_url: string
  owner_email: string
  created_at: string
  updated_at: string
  status: string
  region: string
  orientation: string
  width: number
  height: number
  traffic_intensity: string
  rental_period: string
  price_unit: string
  has_lighting: boolean
  has_image: boolean
  price_includes_print: boolean
  graphic_design_help: boolean
  offer_type: string
  has_vat_invoice: boolean
  views: number
  is_active: boolean
  phone?: string
  contact_preference?: string
  images?: string[]
}
