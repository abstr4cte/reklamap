# ReklaMap - Project Context

## 📋 Overview

**ReklaMap** is a Polish advertising surface marketplace platform that connects surface owners/managers with individuals and companies seeking advertising space. Think of it as "OLX for advertising surfaces" - users can list and search for various types of outdoor and mobile advertising opportunities.

**Status**: Production-ready platform with advanced features

---

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 10.x (PHP 8.1+)
- **Database**: MySQL
- **API**: RESTful JSON API
- **PDF Generation**: Blade templates with DomPDF
- **Security**: reCAPTCHA v3, CORS, API key authentication

### Frontend
- **Framework**: Vue 3 (Composition API)
- **Language**: TypeScript
- **Build Tool**: Vite
- **State Management**: Pinia
- **Routing**: Vue Router
- **Maps**: Leaflet.js
- **Charts**: Chart.js
- **Styling**: Custom CSS (no framework)

### Key Libraries
- `axios` - HTTP client
- `leaflet` - Interactive maps
- `chart.js` - Statistics charts
- `vue-recaptcha-v3` - Bot protection

---

## 📁 Project Structure

```
/var/www/html/reklamap/
├── backend/                    # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/  # API endpoints
│   │   ├── Models/            # Eloquent models
│   │   └── Middleware/        # Custom middleware
│   ├── database/
│   │   └── migrations/        # Database schema
│   ├── resources/
│   │   └── views/pdf/         # PDF templates (Blade)
│   └── routes/
│       └── api.php            # API routes
│
└── frontend/                   # Vue 3 application
    ├── src/
    │   ├── components/        # Reusable Vue components
    │   ├── views/             # Page components
    │   ├── stores/            # Pinia stores
    │   ├── services/          # API service layer
    │   ├── types.ts           # TypeScript interfaces
    │   └── router.ts          # Vue Router config
    └── public/                # Static assets
```

---

## 🎯 Core Features

### 1. Advertisement Types
The platform supports **9 distinct advertisement types**, each with specific fields:

- **billboard** - Large outdoor advertising boards
- **banner** - Flexible fabric/mesh banners
- **wall** - Building wall advertisements
- **citylight** - Illuminated street displays
- **led_screen** - Digital LED displays
- **totem** - Standalone vertical displays
- **transport** - Public transport advertising (bus, tram, metro, stops)
- **mobile** - Mobile advertising (trailers, cars, bikes)
- **other** - Custom/other types

### 2. Type-Specific Fields
Different types have different visible fields. Key logic:

**Variants** (physical configurations):
- billboard: Standardowy, Trójstronny, Backlit
- citylight: Pojedynczy, Podwójny, Cyfrowy
- led_screen: Standardowy, Interaktywny
- totem: Jednostronny, Dwustronny, Wielostronny
- transport: Autobus, Tramwaj, Metro, Przystanek
- mobile: Przyczepka, Samochód, Rower, Inne
- banner/wall: NO variants (removed - variants describe materials, not configs)

**Dimensions**:
- **LED screens**: Input/display in **mm**, stored in database as **meters**
- **All other types**: Always in **meters**

**Traffic Fields** (for all outdoor types: billboard, banner, wall, totem):
- `traffic_intensity`: low/medium/high (REQUIRED for outdoor)
- `traffic_direction`: entry/exit/both
- `traffic_type`: foot/car/both

**LED-specific fields**:
- `resolution`: e.g., "1920x1080"
- `pixel_pitch`: e.g., 3.9 (mm)
- `brightness`: e.g., 5000 (nits)
- `ambient_light_control`: boolean

**Transport-specific**:
- `transport_scope`: interior/exterior/full_vehicle
- `vehicle_count`: number of vehicles
- `daily_passengers`: passenger count

**Mobile-specific**:
- `mobile_exposure_mode`: static/route/event
- `operating_hours`: e.g., "8:00-20:00"
- `route_area`: description
- `operating_zone`: center/periphery/agglomeration

### 3. Price System
**Flexible pricing with 6 units**:
- `/dzień` - per day
- `/tydzień` - per week
- `/miesiąc` - per month
- `/rok` - per year
- `/m²` - per square meter
- `/kampania` - per campaign

**Price Display Logic**:
- Ads store price in ONE unit (`price` + `price_unit`)
- Frontend can display in ANY unit (conversion)
- Estimated prices marked with "~" and "(szacunkowo)"
- Missing data shows "Brak danych"

### 4. Statistics System
**Daily stats tracking** (`advertisement_daily_stats` table):
- Views, phone clicks, email clicks tracked daily
- NO columns in `advertisements` table (removed for normalization)
- Backend sums from daily_stats
- 30-day trends in charts
- Engagement rate calculation

### 5. Search & Filters
**Advanced filtering**:
- Keyword search (title, description, city)
- Type and variant
- Location (region, city, coordinates with radius)
- Price range (any unit)
- Dimensions (width, height, surface area)
- Traffic intensity, direction, type
- Environment, lighting, etc.

**Last search saved** in localStorage for UX

### 6. Interactive Map
- Leaflet.js with OpenStreetMap
- Clustered markers for performance
- Click marker → show ad card
- Optional traffic heatmap overlay

### 7. User Features
- **Favorites**: Save ads to localStorage
- **Comparison**: Compare up to 5 ads of same type
- **PDF Export**: Single ad or comparison table
- **Statistics Dashboard**: For ad owners (views/clicks charts)
- **Contact Forms**: Protected by reCAPTCHA v3

---

## 🔑 Key Technical Decisions

### 1. Dimension Normalization
**Problem**: LED screens traditionally measured in mm, others in meters.

**Solution**:
- **Database**: ALL dimensions stored in **meters** (normalized)
- **Input**: LED screens accept mm, convert to meters before save
- **Display**: LED screens convert meters → mm for display
- **Filtering**: Convert mm → meters before comparison

**Flow**:
```
User Input (LED: mm, Others: m) 
  → Convert to meters 
  → Database (meters) 
  → Display (LED: mm, Others: m)
```

### 2. Views/Clicks Tracking
**Old approach** (removed): Columns in `advertisements` table

**New approach**:
- Separate `advertisement_daily_stats` table
- Daily granularity for analytics
- Backend sums when needed
- Frontend displays 30-day trends

### 3. Variant Logic
**Why some types have NO variants**:
- Banner/Wall: Materials are advertiser's choice, not surface owner's
- Variants describe **physical configurations**, not materials
- Only types with meaningful physical variations have variants

### 4. Traffic Fields Extension
**Originally**: Only billboard had traffic fields

**Now**: ALL outdoor types (billboard, banner, wall, totem)
- Traffic intensity affects pricing
- Direction/type help targeting

### 5. Price Display
**Challenge**: Show prices in different units without storing duplicates

**Solution**:
- Store ONE price + unit
- Calculate conversions on-the-fly
- Mark estimated prices with "~"
- Handle missing data (e.g., no dimensions for /m² calculation)

---

## 🔐 Security & Privacy

### reCAPTCHA v3 Protection
Protected forms:
1. Contact owner form (AdDetailPage)
2. General contact form (ContactPage)
3. Management link request (EmailModal)
4. Feedback form (FeedbackModal)

**Environment Variables**:
- Frontend: `VITE_RECAPTCHA_SITE_KEY`
- Backend: `RECAPTCHA_SECRET_KEY`

### Admin Privacy
- Administrator name: **"ReklaMap"** only
- No personal data in legal pages
- Contact: `kontakt@reklamap.pl`

---

## 🎨 Design System

### Colors
- **Primary Gradient**: `linear-gradient(135deg, #667eea 0%, #764ba2 100%)`
- **Text**: `#111827` (dark gray)
- **Background**: `#ffffff` (white)
- **Borders**: `#e5e7eb` (light gray)

### Buttons
- Primary: Purple gradient with shadow
- Hover: `translateY(-2px)` + enhanced shadow
- Transitions: `cubic-bezier(0.4, 0, 0.2, 1)`

### Forms
- Border radius: 8-12px
- Focus: Purple border + shadow
- Validation: Red error messages

---

## 📝 Important Conventions

### 1. File Naming
- **Components**: PascalCase (e.g., `AdCard.vue`, `HeroBanner.vue`)
- **Views**: PascalCase with "Page" suffix (e.g., `HomePage.vue`)
- **Stores**: camelCase with "use" prefix (e.g., `useSearchStore.ts`)
- **Types**: camelCase (e.g., `types.ts`)

### 2. Component Structure
```vue
<script setup lang="ts">
// 1. Imports
// 2. Props/Emits
// 3. Stores
// 4. Refs/Reactive
// 5. Computed
// 6. Functions
// 7. Lifecycle hooks
</script>

<template>
  <!-- Template -->
</template>

<style scoped>
/* Styles */
</style>
```

### 3. API Calls
- All API calls through `/frontend/src/services/api.ts`
- Authentication via `INTERNAL_APP_KEY` header
- Error handling with try-catch
- TypeScript interfaces for responses

### 4. State Management
- **Pinia stores** for global state:
  - `useSearchStore` - search/filters/listings
  - `usePreferencesStore` - favorites/comparison
  - `useAuthStore` - authentication
- **Local refs** for component-specific state

### 5. Toast Notifications
- Global toast system via `useToast` composable
- Set in `App.vue` on mount
- Used in Pinia stores for consistency
- Types: success, error, info

---

## 🚀 Development Workflow

### Frontend Commands
```bash
cd /var/www/html/reklamap/frontend
npm run dev      # Dev server (localhost:5173)
npm run build    # Production build
npm run preview  # Preview production build
```

### Backend Commands
```bash
cd /var/www/html/reklamap/backend
php artisan serve              # Dev server
php artisan migrate            # Run migrations
php artisan migrate:fresh      # Fresh migration (wipes DB!)
```

### Common Tasks

**Adding a new field to advertisements**:
1. Create migration: `php artisan make:migration add_field_to_advertisements`
2. Update model: `app/Models/Advertisement.php` (fillable, casts)
3. Update controller validation: `app/Http/Controllers/AdvertisementController.php`
4. Update TypeScript interface: `frontend/src/types.ts`
5. Update forms: `AddAdPage.vue`, `ManagementPage.vue`
6. Update display: `AdDetailPage.vue`, `ComparisonPage.vue`
7. Update PDFs: `resources/views/pdf/*.blade.php`

**Adding a new advertisement type**:
1. Database: Enum in migration
2. Frontend: Add to type lists in all relevant components
3. Define type-specific fields logic
4. Add variant options (if applicable)
5. Update comparison fields: `frontend/src/config/comparisonFields.ts`
5. Test filtering, display, and PDF export

---

## 🐛 Common Pitfalls

### 1. LED Dimension Conversion
❌ **Wrong**: Storing LED dimensions in mm
✅ **Right**: Convert to meters before saving, convert back for display

### 2. Variant Logic
❌ **Wrong**: Adding variants for materials
✅ **Right**: Variants only for physical configurations

### 3. Traffic Fields
❌ **Wrong**: Only showing for billboard
✅ **Right**: Show for ALL outdoor types (billboard, banner, wall, totem)

### 4. Price Display
❌ **Wrong**: Storing prices in multiple units
✅ **Right**: Store ONE price+unit, calculate conversions

### 5. Views/Clicks
❌ **Wrong**: Reading from `advertisements.views` column
✅ **Right**: Sum from `advertisement_daily_stats` table

---

## 📚 Key Files Reference

### Frontend
- **Main entry**: `/frontend/src/main.ts`
- **Router**: `/frontend/src/router.ts`
- **API service**: `/frontend/src/services/api.ts`
- **Types**: `/frontend/src/types.ts`
- **Search store**: `/frontend/src/stores/useSearchStore.ts`
- **Home page**: `/frontend/src/views/HomePage.vue`
- **Listings**: `/frontend/src/views/ListingsPage.vue`
- **Ad details**: `/frontend/src/views/AdDetailPage.vue`
- **Add ad**: `/frontend/src/views/AddAdPage.vue`
- **Management**: `/frontend/src/views/ManagementPage.vue`

### Backend
- **API routes**: `/backend/routes/api.php`
- **Ad controller**: `/backend/app/Http/Controllers/AdvertisementController.php`
- **Ad model**: `/backend/app/Models/Advertisement.php`
- **Daily stats model**: `/backend/app/Models/AdvertisementDailyStat.php`
- **PDF templates**: `/backend/resources/views/pdf/`

---

## 🎓 Learning Resources

### For new contributors
1. Read this file first
2. Check memories in chat history (they contain specific implementation details)
3. Explore type definitions in `types.ts`
4. Review `comparisonFields.ts` to understand type-specific fields
5. Test the platform locally to understand UX

### When making changes
1. Check if similar logic exists elsewhere (consistency!)
2. Update ALL relevant files (form, display, PDF, validation)
3. Test with different advertisement types
4. Verify mobile responsiveness
5. Check TypeScript compilation (`npm run build`)

---

## 💡 Pro Tips

1. **Search memories first** - Many implementation details are documented in chat memories
2. **Use code_search tool** - For finding where specific logic is implemented
3. **Check comparison fields** - `comparisonFields.ts` defines what's visible per type
4. **Follow existing patterns** - Consistency is key
5. **Test with real data** - Create ads of different types to verify changes
6. **Mobile-first** - Always check responsive design (≤768px breakpoint)
7. **Toast notifications** - Use global system via `useToast`, not local instances

---

## 📞 Contact & Support

- **Email**: kontakt@reklamap.pl
- **Platform Name**: ReklaMap
- **Admin/Owner**: ReklaMap (name only, no personal data)

---

**Last Updated**: February 2026
**Version**: 1.0 (Production)
