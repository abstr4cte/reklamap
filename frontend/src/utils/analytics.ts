
/**
 * Utility for Google Analytics 4 (GA4) Event Tracking
 */

declare global {
    interface Window {
        gtag: (...args: any[]) => void;
    }
}

export const trackEvent = (eventName: string, params: Record<string, any> = {}) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', eventName, params);
    } else {
        console.warn(`GA4: Cannot track event "${eventName}". gtag not found.`);
    }
};

/**
 * Common events for ReklaMap
 */
export const analytics = {
    // Advertisement Interactions
    viewAd: (adId: string, title: string, city: string) =>
        trackEvent('view_item', { item_id: adId, item_name: title, location_id: city }),

    clickPhone: (adId: string, title: string) =>
        trackEvent('contact_phone_click', { ad_id: adId, ad_title: title }),

    clickEmail: (adId: string, title: string) =>
        trackEvent('contact_email_click', { ad_id: adId, ad_title: title }),

    sendAdMessage: (adId: string) =>
        trackEvent('contact_form_submit', { ad_id: adId }),

    // Conversion / Listing Creation
    startAddAd: () =>
        trackEvent('add_listing_start'),

    finishAddAd: (type: string, city: string) =>
        trackEvent('add_listing_success', { ad_type: type, ad_city: city }),

    // Search & Filters
    search: (keyword: string) =>
        trackEvent('search', { search_term: keyword }),

    filterUsed: (filterName: string, value: any) =>
        trackEvent('filter_used', { filter_name: filterName, filter_value: value }),

    // Newsletter & Marketing
    newsletterSubscribe: () =>
        trackEvent('newsletter_subscribe'),

    searchAlertCreate: (city: string, type: string) =>
        trackEvent('search_alert_create', { alert_city: city, alert_type: type }),

    // General Contact
    mainContactFormSubmit: (subject: string) =>
        trackEvent('main_contact_submit', { contact_subject: subject }),

    // Comparison
    addToComparison: (adId: string) =>
        trackEvent('add_to_comparison', { ad_id: adId }),

    // General purpose tracking proxy
    trackEvent
};
