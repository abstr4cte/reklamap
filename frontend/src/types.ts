export interface LocationCoords {
    lat: number
    lng: number
}

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
    display_status?: string
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
    price_includes_mounting?: boolean
    graphic_design_help: boolean
    offer_type: string
    has_vat_invoice: boolean
    views: number
    is_active: boolean
    phone?: string
    contact_preference?: string
    images?: string[]
    available_from?: string
    price_negotiable?: boolean
    // Nowe pola specyficzne dla typów
    variant?: string
    road_class?: string
    traffic_direction?: string[]
    environment?: string
    spot_duration?: number
    loop_duration?: number
    transport_scope?: string
    vehicle_count?: number
    mobile_exposure_mode?: string
    operating_hours?: string
    route_area?: string
    campaign_duration?: number
}
