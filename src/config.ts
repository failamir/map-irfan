export const WP_BASE_URL = import.meta.env.VITE_WP_BASE_URL || 'https://sozo.treonstudio.com';
export const CLINICS_ENDPOINT = `${WP_BASE_URL.replace(/\/$/, '')}/wp-json/sozo/v1/clinics`;
export const REGIONS_ENDPOINT = `${WP_BASE_URL.replace(/\/$/, '')}/wp-json/sozo/v1/regions`;
